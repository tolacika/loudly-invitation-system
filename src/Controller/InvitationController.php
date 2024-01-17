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
        $data = json_decode($request->getContent(), true);

        if (!isset($data['invitedEmail']) || !isset($data['invitedName']) || empty($data['senderId'])) {
            return $this->json(['message' => 'Missing invitedEmail, invitedName or senderId'], Response::HTTP_BAD_REQUEST);
        }

        $sender = $this->getDoctrine()->getRepository(User::class)->find($data['senderId']);

        if (!$sender) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $existingUser = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $data['invitedEmail']]);
        $existingInvitation = $this->getDoctrine()->getRepository(Invitation::class)->findOneBy(['invitedEmail' => $data['invitedEmail']]);
        if ($existingUser || $existingInvitation) {
            return $this->json(['message' => 'User already registered or invited'], Response::HTTP_BAD_REQUEST);
        }

        $invitation = new Invitation();
        $invitation->setSender($sender);
        $invitation->setInvitedEmail($data['invitedEmail']);
        $invitation->setInvitedName($data['invitedName']);
        $invitation->setStatus(true); // false indicates that the invitation is not yet accepted

        $invitationRepository->add($invitation);

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
        $invitation = $this->getDoctrine()->getRepository(Invitation::class)->find($invitationId);
        if (!$invitation) {
            return $this->json(['message' => 'Invitation not found'], Response::HTTP_NOT_FOUND);
        }

        // Todo: check the logged in senderId to make sure the user is allowed to cancel the invitation

        $this->getDoctrine()->getRepository(Invitation::class)->remove($invitation);
        $this->getDoctrine()->getManager()->flush();

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
        $invitation = $this->getDoctrine()->getRepository(Invitation::class)->find($invitationId);
        if (!$invitation) {
            return $this->json(['message' => 'Invitation not found'], Response::HTTP_NOT_FOUND);
        }
        if (!$invitation->getStatus()) {
            return $this->json(['message' => 'Invitation already responded'], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($response, [Invitation::RESPONSE_ACCEPTED, Invitation::RESPONSE_DECLINED])) {
            return $this->json(['message' => 'Invalid response'], Response::HTTP_BAD_REQUEST);
        }

        if ($response === Invitation::RESPONSE_DECLINED) {
            $invitation->setStatus(false);
            $this->getDoctrine()->getManager()->flush();

            return $this->json([
                'message' => "Invitation {$response} successfully"
            ]);
        }

        $user = new User();
        $user->setName($invitation->getInvitedName());
        $user->setEmail($invitation->getInvitedEmail());

        $this->getDoctrine()->getRepository(User::class)->add($user);

        $this->getDoctrine()->getRepository(Invitation::class)->remove($invitation);
        $this->getDoctrine()->getManager()->flush();

        return $this->json([
            'message' => "Invitation {$response} successfully",
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
            ]
        ]);

    }

    /**
     * @Route("/api/invitations", methods={"GET"})
     */
    public function listInvitations(): Response
    {
        $invitations = $this->getDoctrine()->getRepository(Invitation::class)->findAll();

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

        return $this->json($invitationsAsArray);
    }
}
