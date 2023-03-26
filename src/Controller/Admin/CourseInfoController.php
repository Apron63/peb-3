<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\Course;
use App\Entity\CourseInfo;
use App\Form\Admin\CourseInfoEditType;
use App\Repository\CourseInfoRepository;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class CourseInfoController extends MobileController
{
    public function __construct(
        readonly SluggerInterface $slugger,
        readonly CourseInfoRepository $courseInfoRepository
    ) {
    }

    #[Route('/admin/course_info/create/{id<\d+>}/', name: 'admin_course_info_create')]
    public function adminCourseInfoCreate(Request $request, Course $course): Response
    {
        $courseInfo = new CourseInfo();
        $courseInfo->setCourse($course);
        $form = $this->createForm(CourseInfoEditType::class, $courseInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                /** @var UploadedFile $file */
                $file = $form->get('fileName')->getData();

                if ($file instanceof UploadedFile) {
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid('', true) . '.' . $file->guessExtension();
                    try {
                        $file->move(
                            $this->getParameter('course_upload_directory') . '/' . $course->getShortNameCleared(),
                            $newFilename
                        );
                    } catch (FileException $e) {
                    }

                    $courseInfo->setFileName($newFilename);
                }
                $this->courseInfoRepository->save($courseInfo, true);
            }

            return $this->redirect('/admin/course/' . $courseInfo->getCourse()->getId() . '/');
        }

        return $this->mobileRender('admin/course-info/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/course_info/{id<\d+>}/', name: 'admin_course_info_edit')]
    public function adminCourseInfoEdit(Request $request, CourseInfo $courseInfo): Response
    {
        $form = $this->createForm(CourseInfoEditType::class, $courseInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                /** @var UploadedFile $file */
                $file = $form->get('fileName')->getData();

                if ($file) {
                    // Проверить что каталог существует, при необходимости создать.
                    $catalogName = $this->getParameter('course_upload_directory')
                        . '/'
                        . $courseInfo->getCourse()->getShortNameCleared();

                    if (
                        !file_exists($catalogName)
                        && !mkdir($catalogName, 0777, true)
                        && !is_dir($catalogName)
                    ) {
                        throw new RuntimeException(sprintf('Directory "%s" was not created', $catalogName));
                    }
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid('', true) . '.' . $file->guessExtension();

                    try {
                        $file->move(
                            $catalogName,
                            $newFilename
                        );
                    } catch (FileException $e) {
                    }

                    $courseInfo->setFileName($newFilename);
                }

                $this->courseInfoRepository->save($courseInfo, true);
            }

            return $this->redirect('/admin/course/' . $courseInfo->getCourse()->getId() . '/');
        }

        return $this->mobileRender('admin/course-info/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/course_info/delete/{id<\d+>}/', name: 'admin_course_info_delete')]
    public function adminCourseInfoDelete(Request $request, CourseInfo $courseInfo): Response
    {
        $courseId = $courseInfo->getCourse() ? $courseInfo->getCourse()->getId() : null;
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->courseInfoRepository->remove($courseInfo, true);
        }

        return $this->redirect('/admin/course/' . $courseId . '/');
    }
}
