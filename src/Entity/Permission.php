<?php

namespace App\Entity;

use App\Repository\PermissionRepository;
use DateInterval;
use DateTime;
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
    private ?\DateTimeInterface $activatedAt = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $duration = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastAccess = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $stage = self::STAGE_NOT_STARTED;

    #[ORM\Column(nullable: true)]
    private array $history = [];

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

    public function setOrderNom(string $orderNom): self
    {
        $this->orderNom = $orderNom;

        return $this;
    }

    public function getActivatedAt(): ?\DateTimeInterface
    {
        return $this->activatedAt;
    }

    public function setActivatedAt(?\DateTimeInterface $activatedAt): self
    {
        $this->activatedAt = $activatedAt;

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

    public function getLastAccess(): ?\DateTimeInterface
    {
        return $this->lastAccess;
    }

    public function setLastAccess(?\DateTimeInterface $lastAccess): self
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
        switch($this->stage) {
            case self::STAGE_NOT_STARTED:
                return 'Неактивно';
            case self::STAGE_IN_PROGRESS:
                return 'В процессе';
            case self::STAGE_FINISHED:
                return 'Окончено';
        }
    }
}
