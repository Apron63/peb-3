<?php

namespace App\Service;

use XMLReader;
use DOMElement;
use ZipArchive;
use DOMNodeList;
use RuntimeException;
use App\Entity\Course;
use App\Repository\CourseRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class CourseUploadService
{
    private string $originalFilename;
    private ?string $courseName = null;
    private array $materials = [];
    private array $data = [];
    private ?Course $course;

    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly string $courseUploadPath
    ) {}

    public function fileCourseUpload(UploadedFile $data): Course
    {
        $this->originalFilename = pathinfo($data->getClientOriginalName(), PATHINFO_FILENAME);

        $course = $this->courseRepository->findOneBy(['shortName' => $this->originalFilename]);
        if (!$course instanceof Course) {
            $course = (new Course())
                ->setShortName($this->originalFilename)
                ->setName($this->originalFilename)
                ->setType(Course::CLASSC);

            $this->courseRepository->save($course, true);
        }

        $path = $this->courseUploadPath . DIRECTORY_SEPARATOR . $course->getId();

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

        return $course;
    }

    public function readCourseIntoDb(array $content): void
    {
        $themeNom = 0;
        $questionNom = 0;
        $aNom = 0;
        $materialsNom = 0;

        $this->originalFilename = pathinfo($content['filename'], PATHINFO_FILENAME);
        $path = $this->courseUploadPath . DIRECTORY_SEPARATOR . $content['courseId'];
        $reader = new XMLReader();
        $reader->open($path . '/' . $this->originalFilename . '.xml');

        $this->data = [];
        $this->materials = [];

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
                                        $this->data[$themeNom]['questions'][++$questionNom]['qText'] = trim($chNode->nodeValue);
                                        
                                        if (empty($this->data[$themeNom]['questions'][$questionNom]['qText'])) {
                                            $this->data[$themeNom]['questions'][$questionNom]['qText'] = $questionNom . '.';
                                        }
                                        
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
                                                    $this->data[$themeNom]['questions'][$questionNom]['answer'][$aNom]['aText'] = trim($row->nodeValue);

                                                    if (empty($this->data[$themeNom]['questions'][$questionNom]['answer'][$aNom]['aText'])) {
                                                        $this->data[$themeNom]['questions'][$questionNom]['answer'][$aNom]['aText'] = $aNom . '.';
                                                    }
                                                    
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
                                        $this->data[$themeNom]['questions'][$questionNom]['hText'] = trim($chNode->nodeValue);

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

        // Проверить наличие курса в БД
        $this->checkCourseDb($content['courseId']);
        // Сохраняем данные в БД
        $this->courseRepository->saveDataToDb($this->data, $this->materials, $content['courseId']);
    }

    private function checkCourseDb(int $courseId): void
    {
        $this->course = $this->courseRepository->find($courseId);

        if (! $this->course instanceof Course) {
            throw new RuntimeException('Курс не найден');
        }

        $this->courseRepository->prepareCourseClear($this->course);

        if (null !== $this->courseName) {
            $this->course->setName($this->courseName);

            $this->courseRepository->save($this->course, true);
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
