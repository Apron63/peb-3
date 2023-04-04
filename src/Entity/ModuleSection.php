<?php

namespace App\Entity;

use App\Repository\ModuleSectionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModuleSectionRepository::class)]
class ModuleSection
{
    public const URL_TYPE_INTERACTIVE = 1;
    public const URL_TYPE_LINK = 2;
    public const URL_TYPE_TEXT = 3;

    public const URL_TYPES = [
        'Интерактивные материалы' => self::URL_TYPE_INTERACTIVE,
        'Внешняя ссылка' => self::URL_TYPE_LINK,
        'Текст' => self::URL_TYPE_TEXT,
    ];
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Module $module = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $part = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $urlType = self::URL_TYPE_INTERACTIVE;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $textData = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModule(): ?Module
    {
        return $this->module;
    }

    public function setModule(?Module $module): self
    {
        $this->module = $module;

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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getPart(): ?int
    {
        return $this->part;
    }

    public function setPart(int $part): self
    {
        $this->part = $part;

        return $this;
    }

    public function getUrlType(): ?int
    {
        return $this->urlType;
    }

    public function setUrlType(int $urlType): self
    {
        $this->urlType = $urlType;

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
