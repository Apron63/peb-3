<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PermissionRepository;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: PermissionRepository::class)]
class Permission
{
    use TimestampableEntity;

    public const STAGE_NOT_STARTED = 1;
    public const STAGE_IN_PROGRESS = 2;
    public const STAGE_FINISHED = 3;

    public const MAX_DURATION = 999;

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

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $orderNom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $activatedAt = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $duration = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $lastAccess = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $timeSpent = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $stage = self::STAGE_NOT_STARTED;

    #[ORM\Column(nullable: true)]
    private array $history = [];

    #[ORM\ManyToOne(inversedBy: 'permissions')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Loader $loader = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $surveyEnabled = false;

    #[ORM\Column(options: ['default' => true])]
    private bool $greetingEnabled = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $firstTimeEnabled = true;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'created_by')]
    private ?User $createdBy = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'checked_by')]
    private ?User $checkedBy = null;

    #[ORM\Column(nullable: true)]
    private ?array $favorites = null;

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

    public function getOrderNom(): ?string
    {
        return $this->orderNom;
    }

    public function setOrderNom(?string $orderNom): self
    {
        $this->orderNom = $orderNom;

        return $this;
    }

    public function getActivatedAt(): ?DateTimeInterface
    {
        return $this->activatedAt;
    }

    public function setActivatedAt(?DateTimeInterface $activatedAt): self
    {
        $this->activatedAt = $activatedAt;

        return $this;
    }

    public function getTimeSpent(): ?int
    {
        return $this->timeSpent;
    }

    public function setTimeSpent(?int $timeSpent): self
    {
        $this->timeSpent = $timeSpent;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getIsActive(): bool
    {
        $interval = new DateInterval("P{$this->duration}D");
        return $this->createdAt->add($interval) > new DateTime();
    }

    public function getLastAccess(): ?DateTimeInterface
    {
        return $this->lastAccess;
    }

    public function setLastAccess(?DateTimeInterface $lastAccess): self
    {
        $this->lastAccess = $lastAccess;

        return $this;
    }

    public function getStage(): ?int
    {
        return $this->stage;
    }

    public function setStage(int $stage): self
    {
        $this->stage = $stage;

        return $this;
    }

    public function getHistory(): array
    {
        return $this->history;
    }

    public function setHistory(?array $history): self
    {
        $this->history = $history;

        return $this;
    }

    public function getStageDescription(): string
    {
        $description = '';

        switch($this->stage) {
            case self::STAGE_NOT_STARTED:
                $description = 'Неактивно';
                break;
            case self::STAGE_IN_PROGRESS:
                $description = 'В процессе';
                break;
            case self::STAGE_FINISHED:
                $description = 'Окончено';
        }

        return $description;
    }

    public function getLoader(): ?Loader
    {
        return $this->loader;
    }

    public function setLoader(?Loader $loader): self
    {
        $this->loader = $loader;

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

    public function isSurveyEnabled(): bool
    {
        return $this->surveyEnabled;
    }

    public function setSurveyEnabled(bool $surveyEnabled): self
    {
        $this->surveyEnabled = $surveyEnabled;

        return $this;
    }

    public function isGreetingEnabled(): bool
    {
        return $this->greetingEnabled;
    }

    public function setGreetingEnabled(bool $greetingEnabled): self
    {
        $this->greetingEnabled = $greetingEnabled;

        return $this;
    }

    public function isFirstTimeEnabled(): bool
    {
        return $this->firstTimeEnabled;
    }

    public function setFirstTimeEnabled(bool $firstTimeEnabled): self
    {
        $this->firstTimeEnabled = $firstTimeEnabled;

        return $this;
    }

    public function getEndDate(): DateTimeInterface
    {
        $creationDate = $this->createdAt ?? new DateTime();

        return (clone $creationDate)->add(new DateInterval('P' . $this->duration . 'D'));
    }

    public function getCheckedBy(): ?User
    {
        return $this->checkedBy;
    }

    public function setCheckeddBy(?User $checkedBy): self
    {
        $this->checkedBy = $checkedBy;

        return $this;
    }

    public function getFavorites(): array
    {
        return $this->favorites ?? [];
    }

    public function setFavorites(?array $favorites): static
    {
        $this->favorites = $favorites;

        return $this;
    }
}
