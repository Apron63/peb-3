<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

readonly class FileUploadService
{
    public function __construct(
        private SluggerInterface $slugger,
        private Filesystem $filesystem,
    ) {}

    public function uploadFile(UploadedFile $image, string $path, ?string $oldFileName = null, ?int $fileNameMaxLength = null): ?string
    {
        $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

        if (null !== $fileNameMaxLength && mb_strlen($originalFilename) > $fileNameMaxLength) {
            $shortName = (new UnicodeString($originalFilename))->slice(0, $fileNameMaxLength);
            $lastSpacePosition = $shortName->indexOfLast(' ');

            $originalFilename = $shortName->slice(0, $lastSpacePosition)->toString();
        }

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
