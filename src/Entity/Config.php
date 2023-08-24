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

    #[ORM\Column(type: Types::TEXT, nullable: true, length: 50000)]
    private ?string $userHasNewPermission = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, length: 50000)]
    private ?string $userHasActivatedPermission = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, length: 50000)]
    private ?string $permissionWillEndSoon = null;

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

    public function getUserHasNewPermission(): ?string
    {
        return $this->userHasNewPermission;
    }

    public function setUserHasNewPermission(?string $userHasNewPermission): self
    {
        $this->userHasNewPermission = $userHasNewPermission;

        return $this;
    }

    public function getUserHasActivatedPermission(): ?string
    {
        return $this->userHasActivatedPermission;
    }

    public function setUserHasActivatedPermission(?string $userHasActivatedPermission): self
    {
        $this->userHasActivatedPermission = $userHasActivatedPermission;

        return $this;
    }

    public function getPermissionWillEndSoon(): ?string
    {
        return $this->permissionWillEndSoon;
    }

    public function setPermissionWillEndSoon(?string $permissionWillEndSoon): self
    {
        $this->permissionWillEndSoon = $permissionWillEndSoon;

        return $this;
    }
}
