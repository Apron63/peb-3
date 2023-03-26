<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Permission;
use App\Entity\QueryUser;
use App\Entity\User;
use App\Message\Query1CUploadMessage;
use App\Repository\CourseRepository;
use App\Repository\PermissionRepository;
use App\Repository\QueryUserRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

class Query1CUploadService
{
    private string $originalFilename;
    private string $reportUploadPath;
    private string $exchange1cUploadDirectory;

    public function __construct(
        readonly MessageBusInterface $bus,
        readonly QueryUserRepository $queryUserRepository,
        readonly UserRepository $userRepository,
        readonly UserService $userService,
        readonly CourseRepository $courseRepository,
        readonly PermissionRepository $permissionRepository,
        string $reportUploadPath,
        string $exchange1cUploadDirectory
    ) {
        $this->reportUploadPath = $reportUploadPath;
        $this->exchange1cUploadDirectory = $exchange1cUploadDirectory;
    }

    /**
     * @param UploadedFile $data
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getUsersList(UploadedFile $data): array
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

    /**
     * @param User $user
     * @param $courseIds
     * @param int $duration
     * @param array $data
     * @return array
     */

    public function sendUserDataToQuery(User $user, $courseIds, int $duration, array $data): array
    {
        if (0 === count($data)) {
            return ['success' => false, 'message' => 'Очередь пуста!'];
        }

        $courseName = '';
        foreach ($data as $row) {
            $queryUser = new QueryUser();
            $queryUser
                ->setCreatedBy($user)
                ->setOrderNom($row[0])
                ->setCourseIds(
                    is_array($courseIds)
                        ? implode(',', $courseIds)
                        : $courseIds
                )
                ->setDuration($duration)
                ->setLastName($row[1])
                ->setFirstName($row[2])
                ->setPatronymic($row[3])
                ->setPosition($row[4])
                ->setOrganization($row[5])
                ->setResult('new');

            if (' ' === $courseName) {
                $courseName = $queryUser->getCourseIds();
            }

            $this->queryUserRepository->save($queryUser, true);
        }

        $this->bus->dispatch(new Query1CUploadMessage($row[0], $user->getId()));

        return ['success' => true, 'message' => 'Пользователи успешно добавлены в очередь'];
    }

    /**
     * @return string|null
     * @throws NonUniqueResultException
     */
    public function createUsersAndPermissions(): ?string
    {
        $userData = $this->queryUserRepository->getUserQueryNew();

        $fileName = $this->reportUploadPath . '/' . (new DateTime())->format('d-m-Y_H_i_s') . '_' . uniqid() . '.csv';
        $file = fopen($fileName, 'w');
        // BOM для корректной работы с кодировкой
        fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $fileData = 'Номер заказа;'
            . 'ФИО;'
            . 'Должность;'
            . 'Организация;'
            . 'Логин;'
            . 'Пароль;'
            . 'Курсы;'
            . 'Дней' . PHP_EOL;

        fputs($file, $fileData);

        foreach ($userData as $row) {
            $createdByUser = $this->userRepository->find($row->getCreatedBy()->getId());

            // Ищем пользователя по логину и организации.
            $user = $this->userRepository
                ->findOneBy([
                    'fullName' => implode(
                        ' ',
                        [
                            $row->getLastName(),
                            $row->getFirstName(),
                            $row->getPatronymic()
                        ]
                    ),
                    'organization' => $row->getOrganization()
                ]);

            // Создадим нового если не нашлось.
            if (!$user instanceof User) {
                $user = (new User())
                    ->setOrganization($row->getOrganization())
                    ->setLastName($row->getLastName())
                    ->setFirstName($row->getFirstName())
                    ->setPatronymic($row->getPatronymic())
                    ->setPosition($row->getPosition())
                    ->setPatronymic($row->getPatronymic())
                    ->setCreatedAt($row->getCreatedAt())
                    ->setCreatedBy($createdByUser);
                    
                $user = $this->userService->setNewUser($user);
                $this->userRepository->save($user, true);
            }

            // Проверяем доступы.
            foreach (explode(',', $row->getCourseIds()) as $courseId) {
                $course = $this->courseRepository->find($courseId);
                if ($course instanceof Course) {
                    $permission = $this->permissionRepository
                        ->getLastActivePermission($course, $user);

                    // Создаем новый доступ если нет активного.
                    if (!$permission instanceof Permission) {
                        $permission = (new Permission())
                            ->setCreatedAt(new DateTime())
                            ->setOrderNom($row->getOrderNom())
                            ->setDuration($row->getDuration())
                            ->setCourse($course)
                            ->setUser($user);

                        $this->permissionRepository->save($permission, true);
                    }

                    $fileData = $row->getOrderNom() . ';'
                        . $user->getFullName() . ';'
                        . $user->getPosition() . ';'
                        . $user->getOrganization() . ';'
                        . $user->getLogin() . ';'
                        . $user->getPlainPassword() . ';'
                        . $course->getShortName() . ';'
                        . $permission->getDuration() . PHP_EOL;

                    fputs($file, $fileData);
                }
            }

            // Очередь.
            $row->setResult('success');
            $this->queryUserRepository->save($row, true);
        }

        fclose($file);

        return $fileName;
    }
}
