<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\String\ByteString;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserService
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly UserPasswordHasherInterface $passwordEncoder,
        private readonly TokenStorageInterface $token,
        private readonly UserRepository $userRepository
    ) {}

    public function checkUserExist(array $criteria): ?User
    {
        $lastName = $this->slugger->slug($criteria['lastName'])->lower()->toString();
        $firstName = $this->slugger->slug($criteria['firstName'])->lower()->toString();

        if (! empty($criteria['patronymic'])) {
            $suluggedPatronimic = $this->slugger->slug($criteria['patronymic'])->lower()->toString();

            if (! empty($suluggedPatronimic)) {
                $login = substr($lastName, 0, 8) . $firstName[0] . $suluggedPatronimic[0];
            } else {
                $login = substr($lastName, 0, 9) . $firstName[0];
            }
        } else {
            $login = substr($lastName, 0, 9) . $firstName[0];
        }

        // if (null === $criteria['patronymic']) {
        //     $login = substr($lastName, 0, 9) . $firstName[0];
        // } else {
        //     $patronymic = $this->slugger->slug($criteria['patronymic'])->lower()->toString();
        //     $login = substr($lastName, 0, 8) . $firstName[0] . $patronymic[0] ?? '';
        // }

        return $this->userRepository
            ->findOneBy([
                'login' => $login,
                'organization' => $criteria['organization'],
            ]
        );
    }

    public function setNewUser(User $user): User
    {
        if (null === $user->getLogin() || '' === $user->getLogin()) {
            $user = $this->getNewLoginForUser($user);
        }

        if (null === $user->getPassword() || '' === $user->getPassword()) {
            $user = $this->getNewPasswordForUser($user);
        }

        if (null === $user->getCreatedBy()) {
            $user->setCreatedBy($this->token->getToken()->getUser());
        }

        if (null === $user->getFullName() || '' === $user->getFullName()) {
            $user->setFullName(
                implode(' ', [
                        $user->getLastName(),
                        $user->getFirstName(),
                        $user->getPatronymic(),
                    ]
                ));
        }

        return $user;
    }

    public function getNewLoginForUser(User $user): User
    {
        $lastName = $this->slugger->slug($user->getLastName())->lower()->toString();
        $firstName = $this->slugger->slug($user->getFirstName())->lower()->toString();
        $patronymic = $user->getPatronymic()
            ? $this->slugger->slug($user->getPatronymic())->lower()->toString()
            : null;

        if (empty($patronymic)) {
            $login = substr($lastName, 0, 9) . $firstName[0];
        } else {
            $login = substr($lastName, 0, 8) . $firstName[0] . $patronymic[0];
        }

        $user->setFullName($user->getLastName() . ' ' . $user->getFirstName() . ' ' . $user->getPatronymic());

        $attempt = 1;
        while ($attempt < 1000) {
            $ue = $this->userRepository
                ->getUserExistsByLoginAndOrganization($login, $user->getOrganization());

            if ($ue) {
                $login = substr($login, 0, 7) . sprintf('%03s', $attempt);
            } else {
                break;
            }

            $attempt++;
        }

        $user->setLogin($login);
        return $user;
    }

    public function getNewPasswordForUser(User $user): User
    {
        $newPassword = $user->getPlainPassword();
        if (null === $newPassword || '' === $newPassword) {
            $newPassword = ByteString::fromRandom(8)->toString();

            if ('0' === $newPassword[0]) {
                $newPassword[0] = chr(rand(ord('A'), ord('Z')));
            }
        }

        $user->setPassword($this->passwordEncoder->hashPassword($user, $newPassword));
        $user->setPlainPassword($newPassword);
        return $user;
    }
}
