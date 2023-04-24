<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\LoggerRepository;
use Doctrine\ORM\Mapping\JoinColumn;

#[ORM\Entity(repositoryClass: LoggerRepository::class)]
class Logger
{
    public const DEFAULT_TIME_LEFT_IN_SECONDS = 20 * 60;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

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
    private array $protocol = [];

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Ticket $ticket = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $questionNom = null;

    #[ORM\Column]
    private int $timeLeftInSeconds = 0;

    #[ORM\ManyToOne]
    private ?Permission $permission = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $timeLastQuestion = null;

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

    public function getProtocol(): array
    {
        return $this->protocol;
    }

    public function setProtocol(?array $protocol): self
    {
        $this->protocol = $protocol;

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

    public function getQuestionNom(): ?int
    {
        return $this->questionNom;
    }

    public function setQuestionNom(?int $questionNom): self
    {
        $this->questionNom = $questionNom;

        return $this;
    }

    public function getTimeLeftInSeconds(): int
    {
        return $this->timeLeftInSeconds;
    }

    public function setTimeLeftInSeconds(int $timeLeftInSeconds): self
    {
        $this->timeLeftInSeconds = $timeLeftInSeconds;

        return $this;
    }

    public function getPermission(): ?Permission
    {
        return $this->permission;
    }

    public function setPermission(?Permission $permission): self
    {
        $this->permission = $permission;

        return $this;
    }

    public function getTimeLastQuestion(): ?\DateTime
    {
        return $this->timeLastQuestion;
    }

    public function setTimeLastQuestion(?\DateTime $timeLastQuestion): self
    {
        $this->timeLastQuestion = $timeLastQuestion;

        return $this;
    }
}
