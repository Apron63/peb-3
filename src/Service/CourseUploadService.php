<?php

namespace App\Service;

use App\Entity\Course;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use DOMElement;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use XMLReader;
use ZipArchive;

class CourseUploadService
{
    private EntityManagerInterface $em;

    private string $originalFilename;
    private string $courseName;
    private array $materials;
    private array $data;
    private ?Course $course;
    private string $courseUploadPath;

    /**
     * CourseUploadService constructor.
     * @param EntityManagerInterface $em
     * @param string $courseUploadPath
     */
    public function __construct(EntityManagerInterface $em, string $courseUploadPath)
    {
        $this->em = $em;
        $this->em->getConnection()->getConfiguration()->setSQLLogger();
        $this->courseUploadPath = $courseUploadPath;
    }

    /**
     * @param UploadedFile $data
     */
    public function fileCourseUpload(UploadedFile $data): void
    {
        $this->originalFilename = pathinfo($data->getClientOriginalName(), PATHINFO_FILENAME);
        $path = $this->courseUploadPath . DIRECTORY_SEPARATOR . $this->originalFilename;

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
    }

    /**
     * @param string $fileName
     * @throws Exception
     */
    public function readCourseIntoDb(string $fileName): void
    {
        $themeNom = 0;
        $questionNom = 0;
        $aNom = 0;
        $materialsNom = 0;

        $this->originalFilename = pathinfo($fileName, PATHINFO_FILENAME);
        $path = $this->courseUploadPath . DIRECTORY_SEPARATOR . $this->originalFilename;
        $reader = new XMLReader();
        $reader->open($path . '/' . $this->originalFilename . '.xml');

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT) {
                /** @var DOMElement $dom */
                $dom = $reader->expand();
                if ($dom === false) {
                    continue;
                }
                $children = $dom->childNodes;

                foreach ($children as $child) {
                    switch ($child->nodeName) {
                        case 'my:curs':
                            $this->courseName = $child->nodeValue;
                            break;
                        case 'my:tktext':
                            $this->data[++$themeNom]['theme']['name'] = $child->nodeValue;
                            $questionNom = 0;
                            break;
                        case 'my:materials':
                            foreach ($child->childNodes as $mNode) {
                                switch (trim($mNode->nodeName)) {
                                    case 'my:material':
                                        foreach ($mNode->childNodes as $mChNode) {
                                            switch ($mChNode->nodeName) {
                                                case 'my:name':
                                                    $this->materials[++$materialsNom]['name'] = $mChNode->nodeValue;
                                                    break;
                                                case 'my:filename':
                                                    $this->materials[$materialsNom]['file'] = $mChNode->nodeValue;
                                                    break;
                                            }
                                        }
                                        break;
                                }
                            }
                            break;
                        case 'my:question':
                            $aNom = 0;
                            foreach ($child->childNodes as $chNode) {
                                if (!isset($chNode->tagName)) {
                                    continue;
                                }
                                switch ($chNode->tagName) {
                                    case 'my:qtext':
                                        $this->data[$themeNom]['questions'][++$questionNom]['qText'] = $chNode->nodeValue;
                                        $this->data[$themeNom]['questions'][$questionNom]['type'] = 0;
                                        break;
                                    case 'my:answer':
                                        foreach ($chNode->childNodes as $row) {
                                            if (!isset($row->tagName)) {
                                                continue;
                                            }
                                            switch ($row->tagName) {
                                                case 'my:atext':
                                                    $this->data[$themeNom]['questions'][$questionNom]['answer'][$aNom]['aText'] = $row->nodeValue;
                                                    break;
                                                case 'my:astatus':
                                                    if ($row->nodeValue === 'Правильный ответ') {
                                                        $this->data[$themeNom]['questions'][$questionNom]['answer'][++$aNom]['aStatus'] = true;
                                                        //Если есть несколько правильных ответов, записываем для вопроса множествнный тип.
                                                        if ($this->data[$themeNom]['questions'][$questionNom]['type'] === 0) {
                                                            $this->data[$themeNom]['questions'][$questionNom]['type'] = 1;
                                                        } elseif ($this->data[$themeNom]['questions'][$questionNom]['type'] === 1) {
                                                            $this->data[$themeNom]['questions'][$questionNom]['type'] = 2;
                                                        }
                                                    } elseif ($row->nodeValue === 'Неправильный ответ') {
                                                        $this->data[$themeNom]['questions'][$questionNom]['answer'][++$aNom]['aStatus'] = false;
                                                    }
                                                    break;
                                            }
                                        }
                                        break;
                                    case 'my:qhelp':
                                        $this->data[$themeNom]['questions'][$questionNom]['hText'] = $chNode->nodeValue;
                                        break;
                                }
                            }
                            break;
                    }
                }
            }
        }
        $reader->close();
        // Проверить наличие курса в БД
        $this->checkCourseDb();
        // Сохраняем данные в БД
        $this->saveDataToDb();
    }

    /**
     * Проверяем есть ли учебный материал в БД, перезаписываем его
     */
    private function checkCourseDb(): void
    {
        $this->course = $this->em->getRepository(Course::class)
            ->findOneBy(['shortName' => $this->originalFilename]);

        if ($this->course) {
            $this->em->getRepository(Course::class)->prepareCourseClear($this->course);
        } else {
            $this->course = new Course();
        }

        $this->course->setShortName($this->originalFilename)
            ->setName($this->courseName);
        $this->em->persist($this->course);
        $this->em->flush();
    }

    /**
     * Сохраняем курсы в БД
     * @throws Exception
     */
    private function saveDataToDb(): void
    {
        $themeNom = 1;

        foreach ($this->data as $theme) {
            $this->em->getConnection()->executeQuery("
                INSERT INTO course_theme (id, course_id, name, description)
                VALUES (NULL, '{$this->course->getId()}', '{$themeNom}', '{$theme['theme']['name']}')
            ");
            $themeId = $this->em->getConnection()->lastInsertId();
            $themeNom++;

            // Вопросы
            $cnt = 1;
            foreach ($theme['questions'] as $item) {
                $this->em->getConnection()->executeQuery("
                    INSERT INTO questions (id, course_id, parent_id , description, type, help, nom)
                    VALUES (NULL, {$this->course->getId()}, {$themeId}, '{$item['qText']}', {$item['type']}, '{$item['hText']}', {$cnt})
                ");
                $questionId = $this->em->getConnection()->lastInsertId();

                // Ответы
                $aCnt = 1;
                foreach ($item['answer'] as $row) {
                    $status = (int)$row['aStatus'];
                    $this->em->getConnection()->executeQuery("
                        INSERT INTO answer (id, question_id , description, is_correct, nom)
                        VALUES (NULL, {$questionId}, '{$row['aText']}', {$status}, {$aCnt})
                    ");
                    $aCnt++;
                }
                $cnt++;
            }
        }

        // Материалы
        foreach ($this->materials as $material) {
            $this->em->getConnection()->executeQuery("
                INSERT INTO course_info (id, course_id, name, file_name)
                VALUES (NULL, {$this->course->getId()}, '{$material['name']}', '{$material['file']}')
            ");
        }
    }
}
