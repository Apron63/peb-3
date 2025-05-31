<?php

declare (strict_types=1);

namespace App\Entity;

use App\Repository\PermissionHistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: PermissionHistoryRepository::class)]
class PermissionHistory
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $permissionId = null;

    #[ORM\Column]
    private ?int $duration = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, name: 'created_by')]
    private ?User $createdBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPermissionId(): ?int
    {
        return $this->permissionId;
    }

    public function setPermissionId(int $permissionId): static
    {
        $this->permissionId = $permissionId;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
