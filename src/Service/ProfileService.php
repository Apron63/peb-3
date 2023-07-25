<?php

namespace App\Service;

use App\Entity\User;
use RuntimeException;
use App\Repository\UserRepository;
use Symfony\Component\String\ByteString;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ProfileService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly string $avatarUploadPath,
    ) {}

   public function UploadAvatar(User $user, UploadedFile $image): array
   {
        $success = true;
        $message = '';

        if ($image->getSize() > 100000) {
            $message = 'Размер изображения должен быть меньше чем 100 кб';
            $success = false;
        }
        
        if ($image->getMimeType() !== 'image/jpeg' && $image->getMimeType() !== 'image/png') {
            $message = 'Допускаются только изображения JPG или PNG';
            $success = false;
        }

        if ($success) {
            $uploadDirectory = 
                    ByteString::fromRandom(3)->toString() . DIRECTORY_SEPARATOR
                    . ByteString::fromRandom(3)->toString() . DIRECTORY_SEPARATOR
                    . ByteString::fromRandom(3)->toString() . DIRECTORY_SEPARATOR;

                if (!file_exists($uploadDirectory) && !mkdir($uploadDirectory, 0777, true) && !is_dir($uploadDirectory)) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $uploadDirectory));
                }

                try {
                    $image->move($this->avatarUploadPath . DIRECTORY_SEPARATOR . $uploadDirectory, $image->getClientOriginalName());
                } catch (FileException $e) {
                    throw new RuntimeException('Невозможно переместить файл в каталог загрузки');
                }

                $user->setImage($uploadDirectory . $image->getClientOriginalName());
                $this->userRepository->save($user, true);
        }

        return [
            'success' => $success,
            'message' => $message,
        ];
   }
}
