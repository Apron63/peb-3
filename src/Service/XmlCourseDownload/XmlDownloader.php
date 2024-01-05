<?php

namespace App\Service\XmlCourseDownload;

use App\Entity\Course;
use App\Repository\CourseRepository;
use DOMElement;
use DOMNodeList;
use RuntimeException;
use XMLReader;

class XmlDownloader
{
    private string $originalFilename;
    private ?string $courseName = null;
    private ?Course $course;
    private ?XMLReader $reader = null;
    /** @var CourseThemeDTO[] $themes */
    private array $themes = [];

    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly string $courseUploadPath,
    ) {}

    public function downloadXml(array $content): array
    {
        $this->course = $this->courseRepository->find($content['courseId']);

        if (! $this->course instanceof Course) {
            throw new RuntimeException('Курс не найден');
        }

        $this->originalFilename = pathinfo($content['filename'], PATHINFO_FILENAME);
        $path = $this->courseUploadPath . DIRECTORY_SEPARATOR . $content['courseId'];
        $this->reader = new XMLReader();
        $this->reader->open($path . DIRECTORY_SEPARATOR . $this->originalFilename . '.xml');

        while ($this->reader->read()) {
            if (XMLReader::ELEMENT === $this->reader->nodeType) {
                $this->parseElement();
            }
        }

        $this->reader->close();

        return [
            'courseName' => $this->courseName,
            'themes' => $this->themes,
        ];
    }

    private function parseElement(): void
    {
        $dom = $this->reader->expand();

        if (false === $dom) {
            return;
        }

        $children = $dom->childNodes;

        foreach ($children as $child) {
            switch ($child->nodeName) {
                case 'my:curs':
                    $this->courseName = $child->nodeValue;

                    break;
                case 'my:temicursov':
                    $this->themes = $this->parseThemes($child);

                    break;
            }
        }
    }

    /**
     * @return CourseThemeDTO[]
     */
    private function parseThemes(DOMElement $node): array
    {
        $themes = [];
        foreach ($node->childNodes as $child) {
            switch ($child->nodeName) {
                case 'my:temacursa':
                    $themes[] = $this->parseTheme($child);
            }
        }

        return $themes;
    }

    private function parseTheme(DOMElement $node): CourseThemeDTO
    {
        $description = '';
        $questions = [];
        $materials = [];

        foreach ($node->childNodes as $child) {
            if (isset($child->tagName)) {
                switch ($child->tagName) {
                    case 'my:tktext':
                        $description = $child->nodeValue;

                        break;
                    case 'my:questions':
                        $questions = $this->parseQuestions($child);

                        break;
                    case 'my:materials':
                        $materials = $this->parseMaterials($child);

                    break;
                }
            }
        }

        return new CourseThemeDTO(
            courseId: $this->course->getId(),
            name: 1,
            description: $description,
            questions: $questions,
            materials: $materials,
        );
    }

    /**
     * @return QuestionDTO[]
     */
    private function parseQuestions(DOMElement $node): array
    {
        $questionNom = 1;
        $questions = [];

        foreach ($node->childNodes as $child) {
            if (isset($child->tagName)) {
                switch ($child->tagName) {
                    case 'my:question':
                        $questions[] = $this->parseQuestion($child, $questionNom);
                        $questionNom ++;

                        break;
                }
            }
        }

        return $questions;
    }

    private function parseQuestion(DOMElement $node, int $questionNom): QuestionDTO
    {
        $answerNom = 1;
        $description = '';
        $help = '';
        $answers = [];

        foreach ($node->childNodes as $child) {
            if (isset($child->tagName)) {
                switch ($child->tagName) {
                    case 'my:qtext':
                        $description = trim($child->nodeValue);

                        if (empty($description)) {
                            $description = $questionNom . '.';
                        }

                        $image = $this->searchImage($child);

                        if (null !== $image) {
                            $description .= $image;
                        }

                        break;
                    case 'my:answer':
                        $answers[] = $this->parseAnswers($child, $answerNom);
                        $answerNom ++;

                        break;
                    case 'my:qhelp':

                        $help = trim($child->nodeValue);

                        $image = $this->searchImage($child);

                        if (null !== $image) {
                            $help .= $image;
                        }

                        break;
                }
            }
        }

        $correctAnswers = 0;
        
        foreach ($answers as $answer) {
            if ($answer->isCorrect) {
                $correctAnswers ++;
            }
        }

        return new QuestionDTO(
            courseId: $this->course->getId(),
            parentId: 1,
            description: $description,
            help: $help,
            nom: $questionNom,
            type: $correctAnswers > 1 ? 2 : 1,
            answers: $answers,
        );
    }

    private function parseAnswers(DOMElement $node, int $answerNom): AnswerDTO
    {
        $description = '';
        $isCorrect = false;
        foreach ($node->childNodes as $child) {
            if (isset($child->tagName)) {
                switch ($child->tagName) {
                    case 'my:atext':
                        $description = trim($child->nodeValue);

                        if (empty($description)) {
                            $description = $answerNom . '.';
                        }

                        $image = $this->searchImage($child);

                        if (null !== $image) {
                            $description .= $image;
                        }

                        break;
                    case 'my:astatus':
                        if ($child->nodeValue === 'Правильный ответ') {
                            $isCorrect = true;
                        }

                        break;
                }
            }
        }

        return new AnswerDTO(
            description: $description,
            isCorrect: $isCorrect,
            nom: $answerNom,
        );
    }

    /**
     * @return CourseInfoDTO[]
     */
    private function parseMaterials(DOMElement $node): array
    {
        $materials = [];

        foreach ($node->childNodes as $child) {
            if (isset($child->tagName)) {
                switch ($child->tagName) {
                    case 'my:material':
                        $materials[] = $this->parseMaterial($child);
                }
            }
        }

        return $materials;
    }

    private function parseMaterial(DOMElement $node): CourseInfoDTO
    {
        $name = '';
        $fileName = '';

        foreach ($node->childNodes as $child) {
            if (isset($child->tagName)) {
                switch ($child->tagName) {
                    case 'my:name':
                        $name = $child->nodeValue;

                        break;
                    case 'my:filename':
                        $fileName = $child->nodeValue;

                        break;
                }
            }
        }

        return new CourseInfoDTO(
            courseId: $this->course->getId(),
            name: $name,
            filename: $fileName,
        );
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
