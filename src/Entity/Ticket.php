<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    #[ORM\Column]
    private ?int $nom = null;

    #[ORM\Column]
    private array $text = [];

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $errCnt = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNom(): ?int
    {
        return $this->nom;
    }

    public function setNom(int $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getText(): array
    {
        return $this->text;
    }

    public function setText(array $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getErrCnt(): ?int
    {
        return $this->errCnt;
    }

    public function setErrCnt(?int $errCnt): self
    {
        $this->errCnt = $errCnt;

        return $this;
    }
}
