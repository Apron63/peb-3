<?php

declare (strict_types=1);

namespace App\Entity;

use App\Repository\QueryUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: QueryUserRepository::class)]
class QueryUser
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "created_by", nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column]
    private ?string $courseIds = null;

    #[ORM\Column]
    private ?int $duration = null;

    #[ORM\Column(length: 50)]
    private ?string $lastName = null;

    #[ORM\Column(length: 50)]
    private ?string $firstName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $patronymic = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $position = null;

    #[ORM\Column(length: 500)]
    private ?string $organization = null;

    #[ORM\Column(length: 50)]
    private ?string $result = null;

    #[ORM\Column(length: 50)]
    private ?string $orderNom = null;

    #[ORM\Column(nullable: true)]
    private ?string $email = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Loader $loader = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCourseIds(): ?string
    {
        return $this->courseIds;
    }

    public function setCourseIds(string $courseIds): self
    {
        $this->courseIds = $courseIds;

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

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getPatronymic(): ?string
    {
        return $this->patronymic;
    }

    public function setPatronymic(?string $patronymic): self
    {
        $this->patronymic = $patronymic;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setOrganization(string $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(string $result): self
    {
        $this->result = $result;

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

    public function getLoader(): ?Loader
    {
        return $this->loader;
    }

    public function setLoader(?Loader $loader): self
    {
        $this->loader = $loader;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }
}
