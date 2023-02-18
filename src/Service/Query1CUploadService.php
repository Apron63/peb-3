<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Permission;
use App\Entity\QueryUser;
use App\Entity\User;
use App\Entity\UserQuery;
use App\Message\Query1CUploadMessage;
use App\Repository\CourseRepository;
use App\Repository\PermissionRepository;
use App\Repository\QueryUserRepository;
use App\Repository\UserQueryRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

class Query1CUploadService
{
    // private EntityManagerInterface $em;
    // private ContainerBagInterface $params;
    // private MessageBusInterface $bus;
    // private UserQueryRepository $userQueryRepository;
    // private UserRepository $userRepository;
    // private UserService $userService;
    // private CourseRepository $courseRepository;
    // private PermissionRepository $permissionRepository;

    private string $originalFilename;

    private string $reportUploadPath;
    private string $exchange1cUploadDirectory;

    /**
     * CourseUploadService constructor.
     * @param EntityManagerInterface $em
     * @param ContainerBagInterface $params
     * @param MessageBusInterface $bus
     * @param UserQueryRepository $userQueryRepository ,
     * @param UserRepository $userRepository
     * @param UserService $userService
     * @param CourseRepository $courseRepository
     * @param PermissionRepository $permissionRepository
     */
    public function __construct(
        readonly QueryUserRepository $queryUserRepository,
        EntityManagerInterface $em,
        ContainerBagInterface $params,
        MessageBusInterface $bus,
        //UserQueryRepository $userQueryRepository,
        UserRepository $userRepository,
        //UserService $userService,
        CourseRepository $courseRepository,
        //PermissionRepository $permissionRepository,
        string $reportUploadPath,
        string $exchange1cUploadDirectory
    ) {
        // $this->em = $em;
        // $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        // $this->params = $params;
        // $this->bus = $bus;
        // $this->userQueryRepository = $userQueryRepository;
        // $this->userRepository = $userRepository;
        // $this->userService = $userService;
        // $this->courseRepository = $courseRepository;
        // $this->permissionRepository = $permissionRepository;
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
        while ($str = fgetcsv($userFile)) {
            // Удалим BOM символ на первой строке.
            if ($firstLine) {
                $bom = pack('H*', 'EFBBBF');
                $str[0] = preg_replace("/^$bom/", '', $str[0]);
                $firstLine = false;
            }
            $loadData = explode(';', $str[0]);
            $tmp['orderNo'] = $loadData[0];
            $tmp['lastName'] = $loadData[1];
            $tmp['firstName'] = $loadData[2];
            $tmp['patronymic'] = $loadData[3];
            $tmp['x3'] = $loadData[4];
            $tmp['x3_2'] = $loadData[5];
            $tmp['organization'] = $loadData[6];
            $tmp['x3_3'] = $loadData[7];
            $tmp['courseName'] = $loadData[8];

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
            $$queryUser
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
                ->setOrganization($row[4])
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
     * @return Permission|null
     */
    public function checkPermissionForUser(): ?Permission
    {
        return null;
    }

    /**
     * @return string|null
     * @throws NonUniqueResultException
     */
    public function createUsersAndPermissions(): ?string
    {
        $userData = $this->userQueryRepository->getUserQueryNew();

        $fileName = $this->reportUploadPath . '/' . (new DateTime())->format('d-m-Y_H_i_s') . '_' . uniqid() . '.csv';
        $file = fopen($fileName, 'w');
        // BOM для корректной работы с кодировкой
        fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $fileData = 'Номер заказа;'
            . 'ФИО;'
            . 'Организация;'
            . 'Логин;'
            . 'Пароль;'
            . 'Курсы;'
            . 'Дней' . PHP_EOL;

        fputs($file, $fileData);

        foreach ($userData as $row) {
            $createdByUser = $this->userRepository->find($row['createdBy']);

            // Ищем пользователя по логину и организации.
            $user = $this->userRepository
                ->findOneBy([
                    'name' => implode(
                        ' ',
                        [
                            $row[0]->getLastName(),
                            $row[0]->getFirstName(),
                            $row[0]->getPatronymic()
                        ]
                    ),
                    'organization' => $row[0]->getOrganization()
                ]);

            // Создадим нового если не нашлось.
            if (!$user instanceof User) {
                $user = (new User())
                    ->setOrganization($row[0]->getOrganization())
                    ->setLastName($row[0]->getLastName())
                    ->setFirstName($row[0]->getFirstName())
                    ->setPatronymic($row[0]->getPatronymic())
                    ->setCreatedAt($row[0]->getCreatedAt())
                    ->setCreatedBy($createdByUser)
                    ->setPosition('');
                $user = $this->userService->setNewUser($user);
                $this->em->persist($user);
                $this->em->flush();
            }

            // Проверяем доступы.
            foreach (explode(',', $row[0]->getCourseIds()) as $courseId) {
                $course = $this->courseRepository->find($courseId);
                if ($course instanceof Course) {
                    $permission = $this->permissionRepository
                        ->getLastActivePermission($course, $user);

                    // Создаем новый доступ если нет активного.
                    if (!$permission instanceof Permission) {
                        $permission = (new Permission())
                            ->setCreatedAt(new DateTime())
                            ->setOrderNom($row[0]->getOrderNom())
                            ->setDuration($row[0]->getDuration())
                            ->setCourse($course)
                            ->setUser($user);

                        $this->em->persist($permission);
                        $this->em->flush();
                    }

                    $fileData = $row[0]->getOrderNom() . ';'
                        . $user->getName() . ';'
                        . $user->getOrganization() . ';'
                        . $user->getLogin() . ';'
                        . $user->getPlainPassword() . ';'
                        . $course->getShortName() . ';'
                        . $permission->getDuration() . PHP_EOL;

                    fputs($file, $fileData);
                }
            }

            // Очередь.
            $row[0]->setResult('success');
            $this->em->persist($row[0]);
            $this->em->flush();
        }

        fclose($file);

        return $fileName;
    }
}