<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InvitationRepository::class)
 */
class Invitation
{
    public const RESPONSE_ACCEPTED = 'accepted';
    public const RESPONSE_DECLINED = 'declined';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="sentInvitations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $sender;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $invitedEmail;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $invitedName;

    /**
     * @ORM\Column(type="boolean")
     * 
     * true indicates that the invitation is active
     * false indicates that the invitation is declined or accepted
     */
    private $status;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(User $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getInvitedEmail(): ?string
    {
        return $this->invitedEmail;
    }

    public function setInvitedEmail(string $invitedEmail): self
    {
        $this->invitedEmail = $invitedEmail;

        return $this;
    }

    public function getInvitedName(): ?string
    {
        return $this->invitedName;
    }

    public function setInvitedName(string $invitedName): self
    {
        $this->invitedName = $invitedName;

        return $this;
    }

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): self
    {
        $this->status = $status;

        return $this;
    }
}
