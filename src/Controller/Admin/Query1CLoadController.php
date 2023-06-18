<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Repository\QueryUserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Query1CLoadController extends MobileController
{
    public function __construct(
        private readonly QueryUserRepository $queryUserRepository)
    {}

    #[Route('/admin/query/1cload/', name: 'admin_query_1cload')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $this->queryUserRepository->getQueryUser($this->getUser());

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );
        
        return $this->render('admin/query-1cload/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }
}
