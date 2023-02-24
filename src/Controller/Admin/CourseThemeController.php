<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\Course;
use App\Entity\CourseTheme;
use App\Form\Admin\CourseThemeEditType;
use App\Repository\CourseThemeRepository;
use App\Repository\QuestionsRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CourseThemeController extends MobileController
{
    public function __construct(
        readonly CourseThemeRepository $courseThemeRepository,
        readonly QuestionsRepository $questionsRepository
    ) {}

    #[Route('/admin/course_theme/create/{id<\d+>}/', name: 'admin_course_theme_create')]
    public function adminCourseThemeCreate(Request $request, Course $course): Response
    {
        $courseTheme = new CourseTheme();
        $courseTheme->setCourse($course);
        $form = $this->createForm(CourseThemeEditType::class, $courseTheme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                $this->courseThemeRepository->save($courseTheme, true);
            }

            return $this->redirect('/admin/course/' . $courseTheme->getCourse()->getId() . '/');
        }

        return $this->mobileRender('admin/course-theme/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/course_theme/{id<\d+>}/', name: 'admin_course_theme_edit')]
    public function adminCourseThemeEdit(
        Request $request, 
        PaginatorInterface $paginator, 
        CourseTheme $courseTheme
    ): Response {
        $pagination = $paginator->paginate(
            $this->questionsRepository->getQuestionQuery($courseTheme->getCourse(), $courseTheme->getId()),
            $request->query->getInt('page', 1),
            10
        );

        $form = $this->createForm(CourseThemeEditType::class, $courseTheme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                $this->courseThemeRepository->save($courseTheme, true);
            }

            return $this->redirect('/admin/course/' . $courseTheme->getCourse()->getId() . '/');
        }

        return $this->mobileRender('admin/course-theme/edit.html.twig', [
            'form' => $form->createView(),
            'pagination' => $pagination,
            'parentId' => $courseTheme->getId(),
        ]);
    }

    #[Route('/admin/course_theme/delete/{id<\d+>}/', name: 'admin_course_theme_delete')]
    public function adminCourseThemeDelete(Request $request, CourseTheme $courseTheme): Response
    {
        $courseId = $courseTheme->getCourse() ? $courseTheme->getCourse()->getId() : null;
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->courseThemeRepository->remove($courseTheme, true);
        }

        return $this->redirect('/admin/course/' . $courseId . '/');
    }
}
