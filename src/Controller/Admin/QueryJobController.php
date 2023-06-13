<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Repository\QueryJobRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class QueryJobController extends MobileController
{
    public function __construct(
        private readonly QueryJobRepository $queryJobRepository
    ) {}

    #[Route('/admin/query/job/', name: 'admin_query_job')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $this->queryJobRepository->getPaginatedQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->mobileRender('admin/query-job/index.html.twig', [
            'pagination' => $pagination
        ]);
    }
}
