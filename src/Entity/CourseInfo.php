<?php

namespace App\Entity;

use App\Repository\CourseInfoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

#[ORM\Entity(repositoryClass: CourseInfoRepository::class)]
class CourseInfo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    #[ORM\Column(length: 1000)]
    private ?string $name = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $fileName = null;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    #[ORM\PreRemove]
    public function removeAttachedFile(Filesystem $filesystem, string $courseUploadPath): void
    {
        try {
            $filesystem->remove(
                $courseUploadPath . $this->getCourse()->getId() . DIRECTORY_SEPARATOR . $this->fileName
            );
        } catch (IOException) {
        }
    }
}
