<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

readonly class FileUploadService
{
    public function __construct(
        private SluggerInterface $slugger,
        private Filesystem $filesystem,
    ) {}

    public function uploadFile(UploadedFile $image, string $path, ?string $oldFileName = null): ?string
    {
        $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

        if (! $this->filesystem->exists($path)) {
            $this->filesystem->mkdir($path);
        }

        if (null !== $oldFileName) {
            $fileToDelete = $path . DIRECTORY_SEPARATOR . $oldFileName;

            if ($this->filesystem->exists($fileToDelete)) {
                $this->filesystem->remove($fileToDelete);
            }
        }

        $newFilename =
            $this->slugger->slug($originalFilename)
            . '-'
            . uniqid()
            . '.'
            . $image->guessExtension();

        try {
            $image->move($path, $newFilename);
        } catch (FileException) {
        }

        return $newFilename;
    }
}
