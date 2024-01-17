<?php

namespace App\Controller;

use App\Entity\Invitation;
use App\Entity\User;
use App\Repository\InvitationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InvitationController extends AbstractController
{
    /**
     * @Route("/api/invitation/send", methods={"POST"})
     */
    public function sendInvitation(Request $request, InvitationRepository $invitationRepository): Response
    {
        // Parse JSON request data
        $data = json_decode($request->getContent(), true);

        // Validate the data
        if (!isset($data['invitedEmail']) || !isset($data['invitedName']) || empty($data['senderId'])) {
            return $this->json(['message' => 'Missing invitedEmail, invitedName or senderId'], Response::HTTP_BAD_REQUEST);
        }

        // Find the User entity by ID
        $sender = $this->getDoctrine()->getRepository(User::class)->find($data['senderId']);

        if (!$sender) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the invited email is already registered or invited
        $existingUser = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $data['invitedEmail']]);
        $existingInvitation = $this->getDoctrine()->getRepository(Invitation::class)->findOneBy(['invitedEmail' => $data['invitedEmail']]);
        if ($existingUser || $existingInvitation) {
            return $this->json(['message' => 'User already registered or invited'], Response::HTTP_BAD_REQUEST);
        }

        // Create a new Invitation entity
        $invitation = new Invitation();
        $invitation->setSender($sender);
        $invitation->setInvitedEmail($data['invitedEmail']);
        $invitation->setInvitedName($data['invitedName']);
        $invitation->setStatus(true); // false indicates that the invitation is not yet accepted

        // Persist the Invitation entity to the database
        $invitationRepository->add($invitation);

        // Return a JSON response indicating success
        return $this->json([
            'message' => 'Invitation sent successfully',
            'invitation' => [
                'id' => $invitation->getId(),
                'senderId' => $invitation->getSender()->getId(),
                'senderName' => $invitation->getSender()->getName(),
                'invitedEmail' => $invitation->getInvitedEmail(),
                'invitedName' => $invitation->getInvitedName(),
                'status' => $invitation->getStatus(),
            ]
        ]);
    }

    /**
     * On cancel event the original sender of the invitation can cancel the invitation
     * 
     * @Route("/api/invitation/cancel/{invitationId}", methods={"DELETE"})
     */
    public function cancelInvitation(int $invitationId): Response
    {
        // Find the Invitation entity by ID and cancel it (delete it)
        $invitation = $this->getDoctrine()->getRepository(Invitation::class)->find($invitationId);
        if (!$invitation) {
            return $this->json(['message' => 'Invitation not found'], Response::HTTP_NOT_FOUND);
        }

        // Todo: check the logged in senderId to make sure the user is allowed to cancel the invitation

        $this->getDoctrine()->getRepository(Invitation::class)->remove($invitation);
        $this->getDoctrine()->getManager()->flush();

        // Return a JSON response indicating success
        return $this->json(['message' => 'Invitation cancelled successfully']);
    }

    /**
     * On respond event the invited user can respond to the invitation
     * If its accepted a new user will be created and the original invitation will be deleted
     * With both cases the invitation status will be updated to false
     * 
     * @Route("/api/invitation/respond/{invitationId}/{response}", methods={"PUT"})
     */
    public function respondToInvitation(int $invitationId, string $response): Response
    {
        // Find the Invitation entity by ID and update its status based on the response (accept/decline)
        $invitation = $this->getDoctrine()->getRepository(Invitation::class)->find($invitationId);
        if (!$invitation) {
            return $this->json(['message' => 'Invitation not found'], Response::HTTP_NOT_FOUND);
        }
        if (!$invitation->getStatus()) {
            return $this->json(['message' => 'Invitation already responded'], Response::HTTP_BAD_REQUEST);
        }

        // Validate the response
        if (!in_array($response, [Invitation::RESPONSE_ACCEPTED, Invitation::RESPONSE_DECLINED])) {
            return $this->json(['message' => 'Invalid response'], Response::HTTP_BAD_REQUEST);
        }

        if ($response === Invitation::RESPONSE_ACCEPTED) {
            // Create a new User entity
            $user = new User();
            $user->setName($invitation->getInvitedName());
            $user->setEmail($invitation->getInvitedEmail());

            // Persist the User entity to the database
            $this->getDoctrine()->getRepository(User::class)->add($user);

            // Delete the Invitation entity from the database
            $this->getDoctrine()->getRepository(Invitation::class)->remove($invitation);
            $this->getDoctrine()->getManager()->flush();

            // Return a JSON response indicating success
            return $this->json([
                'message' => "Invitation {$response} successfully",
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                ]
            ]);
        } else {
            $invitation->setStatus(false);
            $this->getDoctrine()->getManager()->flush();

            // Return a JSON response indicating success
            return $this->json([
                'message' => "Invitation {$response} successfully"
            ]);
        }
    }

    /**
     * @Route("/api/invitations", methods={"GET"})
     */
    public function listInvitations(): Response
    {
        // Retrieve a list of invitations from the database
        $invitations = $this->getDoctrine()->getRepository(Invitation::class)->findAll();

        // Transform the list into an array of data suitable for JSON serialization
        $invitationsAsArray = [];
        foreach ($invitations as $invitation) {
            $invitationsAsArray[] = [
                'id' => $invitation->getId(),
                'senderId' => $invitation->getSender()->getId(),
                'senderName' => $invitation->getSender()->getName(),
                'invitedEmail' => $invitation->getInvitedEmail(),
                'invitedName' => $invitation->getInvitedName(),
                'status' => $invitation->getStatus(),
            ];
        }

        // Return a JSON response with the list of invitations
        return $this->json($invitationsAsArray);
    }
}
