<?php

namespace App\Entity;

use App\Repository\ConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigRepository::class)]
class Config
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, length: 50000)]
    private ?string $emailAttachmentStatisticText = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true, length: 50000)]
    private ?string $emailAttachmentResultText = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmailAttachmentStatisticText()
    {
        return $this->emailAttachmentStatisticText;
    }

    public function setEmailAttachmentStatisticText($emailAttachmentStatisticText): self
    {
        $this->emailAttachmentStatisticText = $emailAttachmentStatisticText;

        return $this;
    }

    public function getEmailAttachmentResultText()
    {
        return $this->emailAttachmentResultText;
    }

    public function setEmailAttachmentResultText($emailAttachmentResultText): self
    {
        $this->emailAttachmentResultText = $emailAttachmentResultText;

        return $this;
    }
}
