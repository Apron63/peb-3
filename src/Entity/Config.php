<?php

declare (strict_types=1);

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

    #[ORM\Column(type: Types::TEXT, length: 50000, nullable: true)]
    private ?string $emailAttachmentStatisticText = null;

    #[ORM\Column(type: Types::TEXT, length: 50000, nullable: true)]
    private ?string $emailAttachmentResultText = null;

    #[ORM\Column(type: Types::TEXT, length: 50000, nullable: true)]
    private ?string $userHasNewPermission = null;

    #[ORM\Column(type: Types::TEXT, length: 50000, nullable: true)]
    private ?string $userHasActivatedPermission = null;

    #[ORM\Column(type: Types::TEXT, length: 50000, nullable: true)]
    private ?string $permissionWillEndSoon = null;

    #[ORM\Column(type: Types::TEXT, length: 50000, nullable: true)]
    private ?string $userHasNewPermissionWhatsapp = null;

    #[ORM\Column(type: Types::TEXT, length: 50000, nullable: true)]
    private ?string $userHasActivatedPermissionWhatsapp = null;

    #[ORM\Column(type: Types::TEXT, length: 50000, nullable: true)]
    private ?string $permissionWillEndSoonWhatsapp = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmailAttachmentStatisticText(): ?string
    {
        return $this->emailAttachmentStatisticText;
    }

    public function setEmailAttachmentStatisticText($emailAttachmentStatisticText): self
    {
        $this->emailAttachmentStatisticText = $emailAttachmentStatisticText;

        return $this;
    }

    public function getEmailAttachmentResultText(): ?string
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

    public function getUserHasNewPermissionWhatsapp(): ?string
    {
        return $this->userHasNewPermissionWhatsapp;
    }

    public function setUserHasNewPermissionwhatsapp(?string $userHasNewPermissionWhatsapp): self
    {
        $this->userHasNewPermissionWhatsapp = $userHasNewPermissionWhatsapp;

        return $this;
    }

    public function getUserHasActivatedPermissionWhatsapp(): ?string
    {
        return $this->userHasActivatedPermissionWhatsapp;
    }

    public function setUserHasActivatedPermissionWhatsapp(?string $userHasActivatedPermissionWhatsapp): self
    {
        $this->userHasActivatedPermissionWhatsapp = $userHasActivatedPermissionWhatsapp;

        return $this;
    }

    public function getPermissionWillEndSoonWhatsapp(): ?string
    {
        return $this->permissionWillEndSoonWhatsapp;
    }

    public function setPermissionWillEndSoonWhatsapp(?string $permissionWillEndSoonWhatsapp): self
    {
        $this->permissionWillEndSoonWhatsapp = $permissionWillEndSoonWhatsapp;

        return $this;
    }
}
