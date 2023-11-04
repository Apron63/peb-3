<?php

namespace App\Entity;

use App\Repository\ModuleSectionPageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModuleSectionPageRepository::class)]
class ModuleSectionPage
{
    public const TYPE_SCORM = 1;
    public const TYPE_YOUTUBE = 2;
    public const TYPE_TEXT = 3;

    public const PAGE_TYPES = [
        'Курсы SCORM' => self::TYPE_SCORM,
        'Внешняя ссылка' => self::TYPE_YOUTUBE,
        'Текст' => self::TYPE_TEXT,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ModuleSection $section = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $textData = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSection(): ?ModuleSection
    {
        return $this->section;
    }

    public function setSection(?ModuleSection $section): self
    {
        $this->section = $section;

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

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getTextData(): ?string
    {
        return $this->textData;
    }

    public function setTextData(?string $textData): self
    {
        $this->textData = $textData;

        return $this;
    }
}
