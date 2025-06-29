<?php

declare (strict_types=1);

namespace App\Service;

use App\Entity\ModuleSectionPage;
use App\Repository\ModuleSectionPageRepository;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;
use function dirname;

class InteractiveUploadService
{
    private string $originalFilename;
    private string $originalFileExtension;

    public function __construct(
        private readonly string $courseUploadPath,
        private readonly string $videoUploadPath,
        private readonly ModuleSectionPageRepository $moduleSectionPageRepository,
    ) {}

    public function fileInteractiveUpload(UploadedFile $data, ModuleSectionPage $moduleSectionPage): void
    {
        $this->originalFilename = pathinfo($data->getClientOriginalName(), PATHINFO_FILENAME);
        $path =
            $this->courseUploadPath
            . DIRECTORY_SEPARATOR
            . $moduleSectionPage->getSection()->getModule()->getCourse()->getId()
            . DIRECTORY_SEPARATOR
            . $moduleSectionPage->getId()
            . DIRECTORY_SEPARATOR;

        // Проверить что каталог существует, при необходимости создать.
        if (! file_exists($path) && ! mkdir($path, 0777, true) && ! is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
        }

        // Очистим каталог
        $files = glob($path . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        // Переносим файл
        try {
            $data->move($path, $this->originalFilename . '.zip');
        } catch (FileException) {
            throw new RuntimeException('Невозможно переместить файл в каталог загрузки');
        }

        // Распаковать архив
        $zip = new ZipArchive;
        $res = $zip->open($path . DIRECTORY_SEPARATOR . $this->originalFilename . '.zip');
        if (true === $res) {
            $zip->extractTo($path);
            $zip->close();
        } else {
            throw new RuntimeException('Невозможно распаковать архив');
        }

        // Дописать т.н."защиту от внешних ссылок"
        $indexFile = $path . 'res' . DIRECTORY_SEPARATOR . 'index.html';
        if (! file_exists($indexFile)) {
            throw new RuntimeException('Невозможно открыть индексный файл');
        }

        $targetFile = $path . 'res' . DIRECTORY_SEPARATOR . 'index.php';
        if (! rename($indexFile, $targetFile)) {
            throw new RuntimeException('Невозможно переименовать индексный файл');
        }

        $templateFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Security' . DIRECTORY_SEPARATOR . 'InteractiveProtection.php.template';
        if (! file_exists($templateFile)) {
            throw new RuntimeException('Невозможно открыть файл c шаблоном');
        }

        $content = file_get_contents($templateFile) . file_get_contents($targetFile);
        if (false === $stream = fopen($targetFile, 'w+')) {
            throw new RuntimeException('Невозможно открыть файл на запись');
        }

        if (false === file_put_contents($targetFile, $content)) {
            throw new RuntimeException('Невозможно записать в файл');
        }

        fclose($stream);

        $moduleSectionPage->setUrl($data->getClientOriginalName());
        $this->moduleSectionPageRepository->save($moduleSectionPage, true);
    }

    public function videoFileInteractiveUpload(UploadedFile $data, ModuleSectionPage $moduleSectionPage, string $schemeAndHttpHost): void
    {
        $this->originalFilename = pathinfo($data->getClientOriginalName(), PATHINFO_FILENAME);
        $this->originalFileExtension = pathinfo($data->getClientOriginalName(), PATHINFO_EXTENSION);
        $path =
            $this->videoUploadPath
            . DIRECTORY_SEPARATOR
            . $moduleSectionPage->getSection()->getModule()->getCourse()->getId()
            . DIRECTORY_SEPARATOR;

        // Проверить что каталог существует, при необходимости создать.
        if (! file_exists($path) && ! mkdir($path, 0777, true) && ! is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
        }

        // Переносим файл
        try {
            $data->move($path, $this->originalFilename . '.' . $this->originalFileExtension);
        } catch (FileException) {
            throw new RuntimeException('Невозможно переместить файл в каталог загрузки');
        }

        $moduleSectionPage->setVideoUrl(
            $schemeAndHttpHost
            . '/video/'
            . $moduleSectionPage->getSection()->getModule()->getCourse()->getId()
            . DIRECTORY_SEPARATOR
            . $data->getClientOriginalName()
        );
    }
}
