<?php

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
        /**@var Loader $loader */
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

            $this->queryUserRepository->save($queryUser, true);
        }

        $this->bus->dispatch(new Query1CUploadMessage($loader->getOrderNo(), $user->getId()));

        return ['success' => true, 'message' => 'Пользователи успешно добавлены в очередь'];
    }

    public function createUsersAndPermissions(int $userId): void
    {
        $createdBy = $this->userRepository->find($userId);

        if (! $createdBy instanceof User) {
            throw new RuntimeException('User id: ' . $userId . ' not found');
        }

        $userData = $this->queryUserRepository->getUserQueryNew($createdBy);

        /** @var QueryUser $queryUser */
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
            if (!$user instanceof User) {
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
                $this->userRepository->save($user, true);
            }

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
                            ->setUser($user);

                        $this->permissionRepository->save($permission, true);

                        if('' !== $courseName) {
                            $courseName .= ', ';
                        }
        
                        $courseName.= $course->getShortName();
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

                if('' !== $name) {
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
            // Удалим BOM символ на первой строке.
            if ($firstLine) {
                $bom = pack('H*', 'EFBBBF');
                $str[0] = preg_replace("/^$bom/", '', $str[0]);
                $firstLine = false;
            }

            $tmp['orderNo'] =  $str[0];
            $tmp['lastName'] =  $str[1];
            $tmp['firstName'] =  $str[2];
            $tmp['patronymic'] =  $str[3];
            $tmp['x3'] =  $str[4];
            $tmp['position'] =  $str[5];
            $tmp['organization'] =  $str[6];
            $tmp['x3_3'] =  $str[7];
            $tmp['courseName'] =  $str[8];

            $userData[] = $tmp;
        }

        fclose($userFile);
        return $userData;
    }

    private function saveToLoader(array $userList, User $user): void
    {
        foreach($userList as $item) {
            $loader = new Loader();

            $loader
                ->setUser(null)
                ->setCreatedBy($user)
                ->setOrderNo($item['orderNo'])
                ->setFirstName($item['firstName'])
                ->setLastName($item['lastName'])
                ->setPatronymic($item['patronymic'])
                ->setPosition($item['position'])
                ->setOrganization($item['organization'])
                ->setChecked(false);

            $this->loaderRepository->save($loader, true);
        }
    }
}
