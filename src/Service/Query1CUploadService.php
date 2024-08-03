<?php

namespace App\Service;

use App\Entity\QueryUser;
use App\Entity\User;
use App\Message\Query1CUploadMessage;
use App\Repository\QueryUserRepository;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

// TODO Класс не используется, удалить
class Query1CUploadService
{
    private string $originalFilename;
    private string $exchange1cUploadDirectory;

    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly QueryUserRepository $queryUserRepository,
        string $exchange1cUploadDirectory
    ) {
        $this->exchange1cUploadDirectory = $exchange1cUploadDirectory;
    }

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
}
