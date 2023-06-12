<?php

namespace App\Entity;

use DateTime;
use App\Entity\Course;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\DemoLoggerRepository;

#[ORM\Entity(repositoryClass: DemoLoggerRepository::class)]
class DemoLogger
{
    public const DEFAULT_TIME_LEFT_IN_SECONDS = 20 * 60;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $loggerId = null;
    
    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $errorAllowed = 0;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $errorActually = 0;

    #[ORM\Column(nullable: true)]
    private ?DateTime $beginAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTime $endAt = null;

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
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Course $course = null;

    #[ORM\Column(nullable: true)]
    private ?DateTime $timeLastQuestion = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLoggerId(): ?string
    {
        return $this->loggerId;
    }

    public function setLoggerId(string $loggerId): self
    {
        $this->loggerId = $loggerId;

        return $this;
    }
    
    public function getErrorAllowed(): int
    {
        return $this->errorAllowed;
    }

    public function setErrorAllowed(int $errorAllowed): self
    {
        $this->errorAllowed = $errorAllowed;

        return $this;
    }

    public function getErrorActually(): int
    {
        return $this->errorActually;
    }

    public function setErrorActually(int $errorActually): self
    {
        $this->errorActually = $errorActually;

        return $this;
    }

    public function getBeginAt(): ?DateTime
    {
        return $this->beginAt;
    }

    public function setBeginAt(?DateTime $beginAt): self
    {
        $this->beginAt = $beginAt;

        return $this;
    }

    public function getEndAt(): ?DateTime
    {
        return $this->endAt;
    }

    public function setEndAt(?DateTime $endAt): self
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

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;

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
