<?php

namespace App\Service;

use App\Entity\ModuleInfo;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

class InteractiveUploadService
{
    private string $originalFilename;
    private string $interactiveUploadPath;

    /**
     * InteractiveUploadService constructor.
     * @param string $interactiveUploadPath
     */
    public function __construct(string $interactiveUploadPath)
    {
        $this->interactiveUploadPath = $interactiveUploadPath;
    }

    /**
     * @param UploadedFile $data
     * @param ModuleInfo $moduleInfo
     */
    public function fileInteractiveUpload(UploadedFile $data, ModuleInfo $moduleInfo): void
    {
        $this->originalFilename = pathinfo($data->getClientOriginalName(), PATHINFO_FILENAME);
        $path =
            $this->interactiveUploadPath
            . DIRECTORY_SEPARATOR
            . $moduleInfo->getModule()->getCourse()->getId()
            . DIRECTORY_SEPARATOR
            . $moduleInfo->getId()
            . DIRECTORY_SEPARATOR;

        // Проверить что каталог существует, при необходимости создать.
        if (!file_exists($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
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
        } catch (FileException $e) {
            throw new RuntimeException('Невозможно переместить файл в каталог загрузки');
        }

        // Распаковать архив
        $zip = new ZipArchive;
        $res = $zip->open($path . '/' . $this->originalFilename . '.zip');
        if (true === $res) {
            $zip->extractTo($path);
            $zip->close();
        } else {
            throw new RuntimeException('Невозможно распаковать архив');
        }

        // Дописать т.н."защиту от внешних ссылок"
        $indexFile = $path . DIRECTORY_SEPARATOR . 'res' . DIRECTORY_SEPARATOR . 'index.html';
        if (!file_exists($indexFile)) {
            throw new RuntimeException('Невозможно открыть индексный файл');
        }

        $targetFile = $path . DIRECTORY_SEPARATOR . 'res' . DIRECTORY_SEPARATOR . 'index.php';
        if (!rename($indexFile, $targetFile)) {
            throw new RuntimeException('Невозможно переименовать индексный файл');
        }

        $templateFile = \dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Security' . DIRECTORY_SEPARATOR . 'InteractiveProtection.php.template';
        if (!file_exists($templateFile)) {
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
    }
}