<?php

declare (strict_types=1);

namespace App\Service;

use DateTime;
use App\Entity\User;
use RuntimeException;
use App\Entity\Course;
use App\Entity\Loader;
use App\Entity\QueryUser;
use App\Entity\Permission;
use App\Repository\UserRepository;
use App\Repository\LoaderRepository;
use App\Message\Query1CUploadMessage;
use App\Repository\CourseRepository;
use App\Repository\QueryUserRepository;
use App\Repository\PermissionRepository;
use Exception;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class LoaderService
{
    private string $originalFilename;

    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly LoaderRepository $loaderRepository,
        private readonly UserRepository $userRepository,
        private readonly QueryUserRepository $queryUserRepository,
        private readonly UserService $userService,
        private readonly PermissionRepository $permissionRepository,
        private readonly CourseRepository $courseRepository,
        private readonly string $exchange1cUploadDirectory,
    ) {}

    public function setCheckBoxChange(int $id, string $value): void
    {
        $loader = $this->loaderRepository->find($id);

        if (! $loader instanceof Loader) {
            return;
        }

        $checked = false;

        if ($value === 'true') {
            $checked = true;
        }

        $loader->setChecked($checked);

        $this->loaderRepository->save($loader, true);
    }

    public function loadDataFrom1C(UploadedFile $file, User $user): void
    {
        $this->loaderRepository->clearLoaderForUser($user);

        $this->saveToLoader($this->getUsersList($file), $user);
    }

    public function sendUserDataToQuery(User $user, $courseIds, int $duration): array
    {
        foreach ($this->loaderRepository->getLoaderforCheckedUser($user) as $loader) {
            $queryUser = new QueryUser();

            $queryUser
                ->setCreatedBy($user)
                ->setOrderNom($loader->getOrderNo())
                ->setCourseIds(
                    is_array($courseIds)
                        ? implode(',', $courseIds)
                        : $courseIds
                )
                ->setDuration($duration)
                ->setLastName($loader->getLastName())
                ->setFirstName($loader->getFirstName())
                ->setPatronymic($loader->getPatronymic())
                ->setPosition($loader->getPosition())
                ->setOrganization($loader->getOrganization())
                ->setLoader($loader)
                ->setResult('new');

            if ($loader->isEmailChecked()) {
                $queryUser->setEmail($loader->getEmail());
            }

            if (! empty($loader->getPhone())) {
                $queryUser->setPhone($loader->getPhone());
            }

            $this->queryUserRepository->save($queryUser, true);
        }

        $this->bus->dispatch(new Query1CUploadMessage($loader->getOrderNo(), $user->getId()));

        return [
            'success' => true,
            'message' => 'Пользователи успешно добавлены в очередь'
        ];
    }

    public function createUsersAndPermissions(int $userId): void
    {
        $createdBy = $this->userRepository->find($userId);

        if (! $createdBy instanceof User) {
            throw new RuntimeException('Creating user - creator id: ' . $userId . ' not found');
        }

        $userData = $this->queryUserRepository->getUserQueryNew($createdBy);

        foreach ($userData as $queryUser) {
            // Ищем пользователя по логину и организации.
            $user = $this->userRepository
                ->findOneBy([
                    'fullName' => implode(
                        ' ',
                        [
                            $queryUser->getLastName(),
                            $queryUser->getFirstName(),
                            $queryUser->getPatronymic()
                        ]
                    ),
                    'organization' => $queryUser->getOrganization()
                ]);

            // Создадим нового если не нашлось.
            if (! $user instanceof User) {
                $user = (new User())
                    ->setOrganization($queryUser->getOrganization())
                    ->setLastName($queryUser->getLastName())
                    ->setFirstName($queryUser->getFirstName())
                    ->setPatronymic($queryUser->getPatronymic())
                    ->setPosition($queryUser->getPosition())
                    ->setPatronymic($queryUser->getPatronymic())
                    ->setCreatedAt($queryUser->getCreatedAt())
                    ->setCreatedBy($createdBy);

                $user = $this->userService->setNewUser($user);
            }

            if (null !== $queryUser->getEmail()) {
                $user->setEmail($queryUser->getEmail());
            }

            if (
                null !== $queryUser->getPhone()
                && $user->getMobilePhone() !== $queryUser->getPhone()
            ) {
                $user
                    ->setMobilePhone($queryUser->getPhone())
                    ->setWhatsappExists(false)
                    ->setWhatsappConfirmed(true);
            }

            $this->userRepository->save($user, true);

            // Проверяем доступы.
            $courseName = '';
            foreach (explode(',', $queryUser->getCourseIds()) as $courseId) {
                $course = $this->courseRepository->find($courseId);

                if ($course instanceof Course) {
                    $permission = $this->permissionRepository
                        ->getLastActivePermission($course, $user);

                    // Создаем новый доступ если нет активного.
                    if (!$permission instanceof Permission) {
                        $permission = (new Permission())
                            ->setCreatedAt(new DateTime())
                            ->setOrderNom($queryUser->getOrderNom())
                            ->setDuration($queryUser->getDuration())
                            ->setCourse($course)
                            ->setUser($user)
                            ->setLoader($queryUser->getLoader())
                            ->setCreatedBy($createdBy);

                        $this->permissionRepository->save($permission, true);

                        if ('' !== $courseName) {
                            $courseName .= ', ';
                        }

                        $courseName .= $course->getShortName();
                    }
                }
            }

            // Очередь.
            $queryUser->setResult('success');
            $this->queryUserRepository->save($queryUser, true);

            $loader = $queryUser->getLoader();
            if (! $loader instanceof Loader) {
                throw new RuntimeException('Loader for user not found');
            }

            if ('' !== $courseName) {
                $name = $loader->getCourseName();

                if ('' !== $name) {
                    $name .= ', ';
                }

                $name .= $courseName;

                $loader->setCourseName($name);
            }

            if (null === $loader->getUser()) {
                $loader->setUser($user);
            }

            $this->loaderRepository->save($loader, true);
        }
    }

    public function checkUserQueryIsEmpty(User $user): bool
    {
        return $this->queryUserRepository->checkUserQueryIsEmpty($user);
    }

    private function getUsersList(UploadedFile $data): array
    {
        $userData = [];
        $this->originalFilename = pathinfo($data->getClientOriginalName(), PATHINFO_BASENAME);
        $path = $this->exchange1cUploadDirectory;
        // Переносим файл
        try {
            $data->move($path, $this->originalFilename);
        } catch (FileException $e) {
            throw new RuntimeException('Невозможно переместить файл в каталог загрузки');
        }

        $firstLine = true;
        $userFile = fopen($path . '/' . $this->originalFilename, 'rb');
        while ($str = fgetcsv($userFile, null, ';')) {
            if ($firstLine) {
                $str[0] = $this->removeBOMSymbol($str[0]);
                $firstLine = false;
            }

            if (empty(current($str))) {
                continue;
            }

            if (12 === count($str)) {
                $tmp['orderNo'] =  $str[0];
                $tmp['lastName'] = $str[1];
                $tmp['firstName'] = $str[2];
                $tmp['patronymic'] = $str[3];
                $tmp['x3'] = $str[4];
                $tmp['position'] = $str[5];
                $tmp['email'] = $str[6];
                $tmp['phone'] = $str[7];
                $tmp['organization'] = $str[8];
                $tmp['courseName'] = $str[9];
            } else {
                throw new Exception('Произошла ошибка. Загрузите файл для СДО');
            }

            $userData[] = $this->validateLoader($tmp);
        }

        fclose($userFile);
        return $userData;
    }

    private function saveToLoader(array $userList, User $user): void
    {
        foreach ($userList as $item) {
            $email = trim($item['email']);
            $phone = trim($item['phone']);
            $emailChecked = false;

            if (
                $email !== ''
                && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            ) {
                $emailChecked = true;
            }

            $loader = new Loader()
                ->setUser(null)
                ->setCreatedBy($user)
                ->setOrderNo($item['orderNo'])
                ->setFirstName($item['firstName'])
                ->setLastName($item['lastName'])
                ->setPatronymic($item['patronymic'])
                ->setPosition($item['position'])
                ->setOrganization($item['organization'])
                ->setChecked(false)
                ->setEmail($email)
                ->setEmailChecked($emailChecked)
                ->setPhone($phone)
                ->setErors($item['errors']);

            $this->loaderRepository->save($loader, true);
        }
    }

    private function removeBOMSymbol(string $row): string
    {
        $bom = pack('H*', 'EFBBBF');
        return preg_replace("/^$bom/", '', $row);
    }

    private function validateLoader(array $validation): array
    {
        $errors = [];

        if (mb_strlen($validation['lastName']) > 50) {
            $errors[] = 'Слишком длинная фамилмя';
        }
        if (mb_strlen($validation['firstName']) > 50) {
            $errors[] = 'Слишком длинное имя';
        }
        if (mb_strlen($validation['patronymic']) > 50) {
            $errors[] = 'Слишком длинное отчество';
        }

        $validation['errors'] = implode('\n', $errors);
        return $validation;
    }
}
