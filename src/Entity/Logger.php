<?php

namespace App\Entity;

use App\Repository\LoggerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoggerRepository::class)]
class Logger
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $errorAllowed = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $errorActually = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $beginAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $endAt = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $result = null;

    #[ORM\Column(nullable: true)]
    private array $protokol = [];

    #[ORM\ManyToOne]
    private ?Ticket $ticket = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getErrorAllowed(): ?int
    {
        return $this->errorAllowed;
    }

    public function setErrorAllowed(?int $errorAllowed): self
    {
        $this->errorAllowed = $errorAllowed;

        return $this;
    }

    public function getErrorActually(): ?int
    {
        return $this->errorActually;
    }

    public function setErrorActually(?int $errorActually): self
    {
        $this->errorActually = $errorActually;

        return $this;
    }

    public function getBeginAt(): ?\DateTime
    {
        return $this->beginAt;
    }

    public function setBeginAt(?\DateTime $beginAt): self
    {
        $this->beginAt = $beginAt;

        return $this;
    }

    public function getEndAt(): ?\DateTime
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTime $endAt): self
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getResult(): ?int
    {
        return $this->result;
    }

    public function setResult(?int $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getProtokol(): array
    {
        return $this->protokol;
    }

    public function setProtokol(?array $protokol): self
    {
        $this->protokol = $protokol;

        return $this;
    }

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(?Ticket $ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }
}
