<?php

declare (strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CourseRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints\Length;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
class Course
{
    use TimestampableEntity;

    public const CLASSIC = 1;
    public const INTERACTIVE = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Length(max: 150)]
    private ?string $shortName = null;

    #[ORM\Column(length: 1000)]
    #[Length(max: 1000)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'course')]
    private ?Profile $profile = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $type = self::CLASSIC;

    #[ORM\Column]
    private bool $forDemo = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\OneToMany(mappedBy: 'course', targetEntity: Ticket::class, orphanRemoval: true)]
    private Collection $tickets;

    #[ORM\Column]
    private bool $autonumerationCompleted = false;

    #[ORM\Column]
    private bool $hidden = false;

    public function __construct()
    {
        $this->tickets = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->shortName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function setShortName(string $shortName): self
    {
        $this->shortName = $shortName;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function isForDemo(): bool
    {
        return $this->forDemo;
    }

    public function setForDemo(bool $forDemo): self
    {
        $this->forDemo = $forDemo;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): self
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setCourse($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): self
    {
        if ($this->tickets->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getCourse() === $this) {
                $ticket->setCourse(null);
            }
        }

        return $this;
    }

    public function isAutonumerationCompleted(): bool
    {
        return $this->autonumerationCompleted;
    }

    public function setAutonumerationCompleted(bool $autonumerationCompleted): self
    {
        $this->autonumerationCompleted = $autonumerationCompleted;

        return $this;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function getCourseType(): string
    {
        return self::CLASSIC === $this->type ? '(К) ' : '(И) ';
    }
}
