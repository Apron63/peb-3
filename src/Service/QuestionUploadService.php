<?php

namespace App\Service;

use App\Entity\Course;
use App\Repository\CourseRepository;
use App\Repository\PermissionRepository;
use App\Repository\QuestionsRepository;
use App\Repository\TicketRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use DOMElement;
use DOMNodeList;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use XMLReader;
use ZipArchive;

class QuestionUploadService
{
    private EntityManagerInterface $em;

    private string $originalFilename;
    private string $courseName;
    private array $materials;
    private array $data;
    private ?Course $course;
    private string $courseUploadPath;

    /**
     * QuestionUploadService constructor.
     * @param EntityManagerInterface $em
     * @param string $courseUploadPath
     */
    public function __construct(
        EntityManagerInterface $em, 
        string $courseUploadPath,
        private readonly CourseRepository $courseRepository,
        private readonly QuestionsRepository $questionsRepository,
        private readonly TicketRepository $ticketRepository,
    ) {
        $this->em = $em;
        $this->em->getConnection()->getConfiguration()->setSQLLogger();
        $this->courseUploadPath = $courseUploadPath;
    }

    /**
     * @param UploadedFile $data
     */
    public function fileQuestionUpload(UploadedFile $data, Course $course): void
    {
        $this->originalFilename = pathinfo($data->getClientOriginalName(), PATHINFO_FILENAME);
        $path = $this->courseUploadPath . DIRECTORY_SEPARATOR . $course->getId();

        // Проверить что каталог существует, при необходимости создать.
        if (!file_exists($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
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
    public function readCourseIntoDb(string $fileName, int $courseId): void
    {
        $themeNom = 0;
        $questionNom = 0;
        $aNom = 0;
        $materialsNom = 0;

        $course = $this->courseRepository->find($courseId);
        if (!$course instanceof Course) {
            throw new NotFoundHttpException('Course not found');
        }

        $this->originalFilename = pathinfo($fileName, PATHINFO_FILENAME);
        $reader = new XMLReader();
        $reader->open($this->courseUploadPath . '/' . $course->getId() . '/' . $this->originalFilename . '.xml');

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

                                        $image = $this->searchImage($chNode);

                                        if (null !== $image) {
                                            $this->data[$themeNom]['questions'][$questionNom]['qText'] .= $image;
                                        }

                                        break;
                                    case 'my:answer':
                                        foreach ($chNode->childNodes as $row) {
                                            if (!isset($row->tagName)) {
                                                continue;
                                            }
                                            switch ($row->tagName) {
                                                case 'my:atext':
                                                    $this->data[$themeNom]['questions'][$questionNom]['answer'][$aNom]['aText'] = $row->nodeValue;

                                                    $image = $this->searchImage($row);

                                                    if (null !== $image) {
                                                        $this->data[$themeNom]['questions'][$questionNom]['answer'][$aNom]['aText'] .= $image;
                                                    }
                                                    
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

                                        $image = $this->searchImage($chNode);

                                        if (null !== $image) {
                                            $this->data[$themeNom]['questions'][$questionNom]['hText'] .= $image;
                                        }

                                        break;
                                }
                            }
                            break;
                    }
                }
            }
        }
        $reader->close();

        // Сохраняем данные в БД
        $this->saveDataToDb($course);
    }

    /**
     * Сохраняем курсы в БД
     * @throws Exception
     */
    private function saveDataToDb(Course $course): void
    {
       
        $this->questionsRepository->removeQuestionsForCourse($course);
        $this->ticketRepository->deleteOldTickets($course);

        $themeNom = 1;

        foreach ($this->data as $theme) {
            $cnt = 1;
            foreach ($theme['questions'] as $item) {
                $this->em->getConnection()->executeQuery("
                    INSERT INTO questions (id, course_id, parent_id , description, type, help, nom)
                    VALUES (NULL, {$course->getId()}, NULL, '{$item['qText']}', {$item['type']}, '{$item['hText']}', {$cnt})
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
    }

    private function searchImage(DOMElement $node): ?string
    {
        $image = null;

        $childs = $node->childNodes;
        if ($childs instanceof DOMNodeList) {
            foreach ($childs as $child) {
                if (isset($child->tagName)) {
                    $tag = $child->tagName;
    
                    if ($tag === 'img') {
                        foreach($child->attributes as $attribute) {
                            if (
                                $attribute->name === 'src' 
                                && false === strpos($attribute->value, 'msoinline')
                            ) {
                                $image = '<br><img src="' . $attribute->value . '">';

                                break;
                            } elseif ($attribute->name === 'inline') {
                                $image = '<br><img src="data:image.jpeg;base64,' . $attribute->value . '">';

                                break;
                            }
                        }
                    } else {
                        $image = $this->searchImage($child);
                    }
    
                    if (null !== $image) {
                        return $image;
                    }
                }
            }
        }

        return $image;
    }
}
