<?php

declare (strict_types=1);

namespace App\Entity;

use App\Repository\WhatsappQueueRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: WhatsappQueueRepository::class)]
class WhatsappQueue
{
    public const SUCCESS_TRY_NAME = 'Успешно';
    public const MAX_TRY_COUNT = 3;

    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn]
    private ?User $user = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $phone;

    #[ORM\Column(type: Types::STRING)]
    private string $subject;

    #[ORM\Column(type: Types::TEXT, length: 1000)]
    private string $content;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $status;

    #[ORM\Column(nullable: true)]
    private ?DateTime $sendedAt = null;

    #[ORM\Column]
    private int $attempts = 1;

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

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

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

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setAttempts(int $attempts): self
    {
        $this->attempts = $attempts;

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
