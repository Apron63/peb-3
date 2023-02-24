<?php

namespace App\Entity;

use App\Repository\ModuleInfoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModuleInfoRepository::class)]
class ModuleInfo
{
    public const URL_TYPE_INTERACTIVE = 1;
    public const URL_TYPE_LINK = 2;

    public const URL_TYPES = [
        'Интерактивные материалы' => self::URL_TYPE_INTERACTIVE,
        'Внешняя ссылка' => self::URL_TYPE_LINK,
    ];
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $urlType = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $part = null;

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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

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

    public function getPart(): ?int
    {
        return $this->part;
    }

    public function setPart(int $part): self
    {
        $this->part = $part;

        return $this;
    }
}
