<?php

namespace App\Controller\Admin;

use App\Entity\Course;
use App\Entity\CourseTheme;
use App\Decorator\MobileController;
use App\Form\Admin\CourseThemeEditType;
use App\Repository\QuestionsRepository;
use App\Repository\CourseThemeRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CourseThemeController extends MobileController
{
    public function __construct(
        private readonly CourseThemeRepository $courseThemeRepository,
        private readonly QuestionsRepository $questionsRepository
    ) {}

    #[Route('/admin/course_theme/create/{id<\d+>}/', name: 'admin_course_theme_create')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminCourseThemeCreate(Request $request, Course $course): Response
    {
        $courseTheme = new CourseTheme();
        $courseTheme->setCourse($course);
        $form = $this->createForm(CourseThemeEditType::class, $courseTheme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->courseThemeRepository->save($courseTheme, true);

            return $this->redirect('/admin/course/' . $courseTheme->getCourse()->getId() . '/');
        }

        return $this->mobileRender('admin/course-theme/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/course_theme/{id<\d+>}/', name: 'admin_course_theme_edit')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
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
            $this->courseThemeRepository->save($courseTheme, true);

            return $this->redirect('/admin/course/' . $courseTheme->getCourse()->getId() . '/');
        }

        return $this->mobileRender('admin/course-theme/edit.html.twig', [
            'form' => $form->createView(),
            'pagination' => $pagination,
            'parentId' => $courseTheme->getId(),
        ]);
    }

    #[Route('/admin/course_theme/delete/{id<\d+>}/', name: 'admin_course_theme_delete')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminCourseThemeDelete(CourseTheme $courseTheme): Response
    {
        $courseId = $courseTheme->getCourse()?->getId();
        $this->courseThemeRepository->remove($courseTheme, true);

        return $this->redirect('/admin/course/' . $courseId . '/');
    }
}
