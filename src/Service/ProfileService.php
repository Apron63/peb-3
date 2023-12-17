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
    private const ALPHABET = '1234567890';

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
                    ByteString::fromRandom(3, self::ALPHABET)->toString() . DIRECTORY_SEPARATOR
                    . ByteString::fromRandom(3, self::ALPHABET)->toString() . DIRECTORY_SEPARATOR
                    . ByteString::fromRandom(3, self::ALPHABET)->toString() . DIRECTORY_SEPARATOR;

            $uploadFullPath = $this->avatarUploadPath . DIRECTORY_SEPARATOR . $uploadDirectory;

            if (! file_exists($uploadFullPath) && ! mkdir($uploadFullPath, 0777, true) && ! is_dir($uploadFullPath)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $uploadFullPath));
            }

            try {
                $image->move($uploadFullPath, $image->getClientOriginalName());
            } catch (FileException) {
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
