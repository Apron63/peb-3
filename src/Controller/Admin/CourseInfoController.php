<?php

namespace App\Controller\Admin;

use App\Service\FileUploadService;
use App\Entity\Course;
use App\Entity\CourseInfo;
use App\Decorator\MobileController;
use App\Form\Admin\CourseInfoEditType;
use App\Repository\CourseInfoRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CourseInfoController extends MobileController
{
    public function __construct(
        private readonly CourseInfoRepository $courseInfoRepository,
        private readonly FileUploadService $fileUploadService,
        private readonly Filesystem $filesystem,
        private readonly string $courseUploadPath,
    ) {}

    #[Route('/admin/course_info/create/{id<\d+>}/', name: 'admin_course_info_create')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminCourseInfoCreate(Request $request, Course $course): Response
    {
        $courseInfo = new CourseInfo();
        $courseInfo->setCourse($course);
        $form = $this->createForm(CourseInfoEditType::class, $courseInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('fileName')->getData();

            if ($file instanceof UploadedFile) {
                $path = $this->courseUploadPath . DIRECTORY_SEPARATOR . $course->getId();
                $newFilename = $this->fileUploadService->uploadFile($file, $path);

                $courseInfo->setFileName($newFilename);
            }
            $this->courseInfoRepository->save($courseInfo, true);

            $this->addFlash('success', 'Добавлен новый материал');

            return $this->redirect('/admin/course/' . $courseInfo->getCourse()->getId() . '/');
        }

        return $this->mobileRender('admin/course-info/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/course_info/{id<\d+>}/', name: 'admin_course_info_edit')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminCourseInfoEdit(Request $request, CourseInfo $courseInfo): Response
    {
        $form = $this->createForm(CourseInfoEditType::class, $courseInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('fileName')->getData();

            if ($file instanceof UploadedFile) {
                $path = $this->courseUploadPath . DIRECTORY_SEPARATOR . $courseInfo->getCourse()->getId();
                $newFilename = $this->fileUploadService->uploadFile($file, $path, $courseInfo->getFileName());

                $courseInfo->setFileName($newFilename);
            }

            $this->courseInfoRepository->save($courseInfo, true);

            $this->addFlash('success', 'Материал обновлен');

            return $this->redirect('/admin/course/' . $courseInfo->getCourse()->getId() . '/');
        }

        return $this->mobileRender('admin/course-info/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/course_info/delete/{id<\d+>}/', name: 'admin_course_info_delete')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminCourseInfoDelete(CourseInfo $courseInfo): Response
    {
        $courseId = $courseInfo->getCourse()?->getId();

        $path = $this->courseUploadPath . DIRECTORY_SEPARATOR . $courseInfo->getCourse()->getId();
        $fileName = $path . DIRECTORY_SEPARATOR . $courseInfo->getFileName();

        if ($this->filesystem->exists($fileName)) {
            $this->filesystem->remove($fileName);
        }

        $this->courseInfoRepository->remove($courseInfo, true);

        $this->addFlash('success', 'Материал удален');

        return $this->redirect('/admin/course/' . $courseId . '/');
    }
}
