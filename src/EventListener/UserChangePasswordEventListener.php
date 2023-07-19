<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\Events;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsDoctrineListener(event: Events::preUpdate)]
class UserChangePasswordEventListener
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordEncoder,
    ) {}

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        if ($args->hasChangedField('plainPassword')) {
            $entity->setPassword($this->passwordEncoder->hashPassword($entity, $entity->getPlainPassword()));
        }
    }
}
