<?php

namespace App\Service;

use App\Entity\Answer;
use App\Entity\Course;
use App\Entity\CourseInfo;
use App\Entity\CourseTheme;
use App\Entity\Module;
use App\Entity\ModuleSection;
use App\Entity\ModuleSectionPage;
use App\Entity\Questions;
use App\Repository\AnswerRepository;
use App\Repository\CourseInfoRepository;
use App\Repository\CourseRepository;
use App\Repository\CourseThemeRepository;
use App\Repository\ModuleRepository;
use App\Repository\ModuleSectionPageRepository;
use App\Repository\ModuleSectionRepository;
use App\Repository\QuestionsRepository;
use Exception;
use Symfony\Component\Filesystem\Filesystem;

readonly class CourseCopyService
{
    public function __construct(
        private CourseRepository $courseRepository,
        private CourseInfoRepository $courseInfoRepository,
        private CourseThemeRepository $courseThemeRepository,
        private QuestionsRepository $questionsRepository,
        private AnswerRepository $answerRepository,
        private ModuleRepository $moduleRepository,
        private ModuleSectionRepository $moduleSectionRepository,
        private ModuleSectionPageRepository $moduleSectionPageRepository,
        private Filesystem $filesystem,
        private string $courseUploadPath,
    ) {}

    /**
     * @throws Exception
     */
    public function copyCourse(int $courseId): void
    {
        $course = $this->courseRepository->find($courseId);

        if (! $course instanceof Course) {
            throw new Exception('Курс не найден по id: ' . $courseId);
        }

        if (Course::INTERACTIVE === $course->getType()) {
            $this->copyInteractiveCourse($course);
        } elseif (Course::CLASSIC === $course->getType()) {
            $this->copyClassicCourse($course);
        }
    }

    private function copyInteractiveCourse(Course $course): void
    {
        $newCourse = $this->getNewCourseName($course);

        /** @var Module[] $modules */
        $modules = $this->moduleRepository->findBy(['course' => $course]);
        foreach ($modules as $module) {
            $newModule = new Module();

            $newModule
                ->setName($module->getName())
                ->setCourse($newCourse)
                ->setSortOrder($module->getSortOrder());

            $this->moduleRepository->save($newModule, true);

            /** @var ModuleSection[] $moduleSections */
            $moduleSections = $this->moduleSectionRepository->findBy(['module' => $module]);
            foreach ($moduleSections as $moduleSection) {
                $newModuleSection = new ModuleSection();

                $newModuleSection
                    ->setName($moduleSection->getName())
                    ->setType($moduleSection->getType())
                    ->setModule($newModule);

                $this->moduleSectionRepository->save($newModuleSection, true);

                $moduleSectionPages = $this->moduleSectionPageRepository->findBy(['section' => $moduleSection]);
                foreach ($moduleSectionPages as $moduleSectionPage) {
                    $newModuleSectionPage = new ModuleSectionPage();

                    $newModuleSectionPage
                        ->setSection($newModuleSection)
                        ->setType($moduleSectionPage->getType())
                        ->setName($moduleSectionPage->getName())
                        ->setUrl($moduleSectionPage->getUrl())
                        ->setTextData($moduleSectionPage->getTextData());

                    $this->moduleSectionPageRepository->save($newModuleSectionPage, true);

                    $sourcePath = $this->courseUploadPath . DIRECTORY_SEPARATOR . $course->getId() . DIRECTORY_SEPARATOR . $moduleSectionPage->getId();
                    if ($this->filesystem->exists($sourcePath)) {
                        $targetPath = $this->courseUploadPath . DIRECTORY_SEPARATOR . $newCourse->getId() . DIRECTORY_SEPARATOR . $newModuleSectionPage->getId();
                        $this->filesystem->mirror($sourcePath, $targetPath);
                    }
                }
            }
        }

        $this->copyMaterials($course, $newCourse);
    }

    private function copyClassicCourse(Course $course): void
    {
        $newCourse = $this->getNewCourseName($course);

        $courseThemes = $this->courseThemeRepository->findBy(['course' => $course]);
        foreach ($courseThemes as $courseTheme) {
            $newCourseTheme = new CourseTheme();

            $newCourseTheme
                ->setCourse($newCourse)
                ->setName($courseTheme->getName())
                ->setDescription($courseTheme->getDescription());

            $this->courseThemeRepository->save($newCourseTheme, true);

            $questions = $this->questionsRepository->findBy([
                'course' => $course,
                'parentId' => $courseTheme->getId(),
            ]);

            foreach ($questions as $question) {
                $newQuestion = new Questions();

                $newQuestion
                    ->setCourse($newCourse)
                    ->setParentId($newCourseTheme->getId())
                    ->setDescription($question->getDescription())
                    ->setType($question->getType())
                    ->setHelp($question->getHelp())
                    ->setNom($question->getNom());

                $this->questionsRepository->save($newQuestion, true);

                $answers = $this->answerRepository->findBy(['question' => $question]);
                foreach ($answers as $answer) {
                    $newAnswer = new Answer();

                    $newAnswer
                        ->setQuestion($newQuestion)
                        ->setNom($answer->getNom())
                        ->setDescription($answer->getDescription())
                        ->setIsCorrect($answer->isCorrect());

                    $this->answerRepository->save($newAnswer, true);
                }
            }
        }

        $this->copyMaterials($course, $newCourse);
    }

    private function getNewCourseName(Course $course): Course
    {
        $newCourse = new Course();
        $newCourse
            ->setName($course->getName() . ' (копия)')
            ->setShortName($course->getShortName() . ' (копия)')
            ->setType($course->getType())
            ->setImage($course->getImage())
            ->setProfile($course->getProfile())
            ->setForDemo($course->isForDemo());

        $this->courseRepository->save($newCourse, true);

        $path = $this->courseUploadPath . DIRECTORY_SEPARATOR . $newCourse->getId();
        $this->filesystem->mkdir($path);

        $sourceImage = $this->courseUploadPath . DIRECTORY_SEPARATOR . $course->getId() . DIRECTORY_SEPARATOR . $course->getImage();
        if (
            null !== $course->getImage()
            && $this->filesystem->exists($sourceImage)
        ) {
            $targetImage = $this->courseUploadPath . DIRECTORY_SEPARATOR . $newCourse->getId() . DIRECTORY_SEPARATOR . $newCourse->getImage();
            $this->filesystem->copy($sourceImage, $targetImage);
        }

        return $newCourse;
    }

    private function copyMaterials(Course $course, Course $newCourse): void
    {
        $courseInfos = $this->courseInfoRepository->findBy(['course' => $course]);

        foreach ($courseInfos as $courseInfo) {
            $newCourseInfo = new CourseInfo();

            $newCourseInfo
                ->setCourse($newCourse)
                ->setName($courseInfo->getName())
                ->setFileName($courseInfo->getFileName());

            $this->courseInfoRepository->save($newCourseInfo, true);

            $sourceName = $this->courseUploadPath . DIRECTORY_SEPARATOR . $course->getId() . DIRECTORY_SEPARATOR . $courseInfo->getFileName();
            if ($this->filesystem->exists($sourceName)) {
                $targetName = $this->courseUploadPath . DIRECTORY_SEPARATOR . $newCourse->getId() . DIRECTORY_SEPARATOR . $newCourseInfo->getFileName();
                $this->filesystem->copy($sourceName, $targetName);
            }
        }
    }
}
