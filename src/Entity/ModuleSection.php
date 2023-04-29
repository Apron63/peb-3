<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ModuleSectionRepository;

#[ORM\Entity(repositoryClass: ModuleSectionRepository::class)]
class ModuleSection
{
    public const TYPE_NORMAL = 1;
    public const TYPE_INTERMEDIATE = 2;
    public const TYPE_TESTING = 3;

    public const PAGE_TYPES = [
        'Стандартная' => self::TYPE_NORMAL,
        'Промежуточное тестирование' => self::TYPE_INTERMEDIATE,
        'Подготовка к аттестации' => self::TYPE_TESTING,
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

    #[ORM\Column(type: Types::SMALLINT )]
    private int $type = self::TYPE_NORMAL;

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
    
    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }
}
