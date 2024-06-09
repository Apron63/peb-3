<?php

namespace App\Entity;

use App\Repository\MailingQueueRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: MailingQueueRepository::class)]
class MailingQueue
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?string $reciever = null;

    #[ORM\Column]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, length: 10000)]
    private ?string $attachment = null;

    #[ORM\Column(nullable: true)]
    private ?DateTime $sendedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'created_by')]
    private ?User $createdBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        // Fake method, do nothing
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getReciever(): ?string
    {
        return $this->reciever;
    }

    public function setReciever(?string $reciever): self
    {
        $this->reciever = $reciever;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setAttachment(string $attachment): self
    {
        $this->attachment = $attachment;

        return $this;
    }

    public function getSendedAt(): ?DateTime
    {
        return $this->sendedAt;
    }

    public function setSendedAt(?DateTime $sendedAt): self
    {
        $this->sendedAt = $sendedAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
