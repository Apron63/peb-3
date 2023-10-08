<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\Permission;
use App\Form\Admin\Load1CType;
use App\Repository\CourseRepository;
use App\Repository\LoaderRepository;
use App\Repository\ProfileRepository;
use App\Service\LoaderService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LoaderController extends MobileController
{
    public function __construct(
        private readonly LoaderRepository $loaderRepository,
        private readonly LoaderService $loaderService,
        private readonly CourseRepository $courseRepository,
        private readonly ProfileRepository $profileRepository,
    ) {}

    #[Route('/admin/import_1C/', name: 'admin_import_1C')]
    public function import1C(Request $request): Response
    {
        $form = $this->createForm(Load1CType::class);
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
            && $form->get('filename')->getData() !== null
        ) {
            $this->loaderService->loadDataFrom1C($form->get('filename')->getData(), $this->getUser());

            return $this->redirectToRoute('admin_loader');
        }

        return $this->mobileRender('admin/loader/_select_file.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/admin/loader/', name: 'admin_loader')]
    public function index(): Response
    {
        $data = $this->loaderRepository->getLoaderforUser($this->getUser());
        $emptyData = count($data) > 0 ? 0 : 1;

        return $this->render('admin/loader/index.html.twig', [
            'data' => $data,
            'emptyData' => $emptyData,
        ]);
    }
    
    #[Route('/admin/loader/changeCheckBox/', name: 'admin_loader_change_check_box', condition: 'request.isXmlHttpRequest()')]
    public function changeCheckBoxValue(Request $request): JsonResponse
    {
        $id = (int) $request->get('id');
        $value = strtolower($request->get('value', 'false'));

        $this->loaderService->setCheckBoxChange($id, $value);

        return new JsonResponse();
    }
    
    #[Route('/admin/loader/setAllCheckBox/', name: 'admin_loader_set_all_check_box', condition: 'request.isXmlHttpRequest()')]
    public function setAllCheckBoxValue(Request $request): JsonResponse
    {
        $action = strtolower($request->get('action', 'select'));

        $this->loaderRepository->setAllCheckBoxValue($this->getUser(), $action);

        return new JsonResponse();
    }
    
    #[Route('/admin/loader/checkIfLoaderIsEmpty/', name: 'admin_loader_check_empty', condition: 'request.isXmlHttpRequest()')]
    public function checkIfLoaderIsEmpty(): JsonResponse
    {
        return new JsonResponse([
            'empty' => $this->loaderRepository->checkIfLoaderIsEmpty($this->getUser()),
        ]);
    }
    
    #[Route('/admin/loader/prepareData/', name: 'admin_loader_prepare_data', condition: 'request.isXmlHttpRequest()')]
    public function prepareData(): Response
    {
        return new Response(
            $this->renderView('admin/loader/_select_course.html.twig', [
                'course' => $this->courseRepository->getAllCourses(),
                'profiles' => $this->profileRepository->getAllProfiles(),
            ])
        );
    }
    
    #[Route('/admin/loader/sendToQuery/', name: 'admin_loader_send_to_query', condition: 'request.isXmlHttpRequest()')]
    public function sendToQuery(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $courseIds = $request->get('course');
        $duration = $request->get('duration');

        if ($duration >= Permission::MAX_DURATION) {
            $result['success'] = false;
        } else {
            $result = $this->loaderService->sendUserDataToQuery($user, $courseIds, $duration);
        }

        return new JsonResponse([
            'success' => $result['success'],
            'message' => $result['message'] ?? '',
        ]);
    }
    
    #[Route('/admin/loader/checkQuery/', name: 'admin_loader_check_query', condition: 'request.isXmlHttpRequest()')]
    public function checkQuery(): JsonResponse
    {
        return new JsonResponse([
            'result' => $this->loaderService->checkUserQueryIsEmpty($this->getUser())
        ]);
    }
}
