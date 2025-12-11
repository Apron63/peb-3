<?php

declare (strict_types=1);

namespace App\Controller\Admin\Email;

use App\Entity\User;
use App\Service\EmailService\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class CreateEmailController extends AbstractController
{
    public function __construct(
        private readonly EmailService $emailService,
    ) {}

    #[Route('/admin/email/create-report/{type}/', name: 'admin_email_report_create')]
    public function createReport(string $type, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $query = $request->query->all();
        $criteria = $query['criteria'] ?? [];

        if (empty($user->getEmail())) {
            $this->addFlash('error', 'Не заполнен email у пользователя');

            return $this->redirectToRoute('admin_user_list', $criteria);
        }

        try {
            $email = $this->emailService->createEmailWithReportData($user, $type, $criteria);
        } catch (Throwable $e) {
            $this->addFlash('error', 'Ошибка при создании отчета: ' . $e->getMessage());

            return $this->redirectToRoute('admin_user_list', $criteria);
        }

        return $this->redirectToRoute('admin_email_report_edit', ['mailId' => $email->getId()]);
    }

    #[Route('/admin/email/create-statistic/{type}/', name: 'admin_email_statistic_create')]
    public function createStatistic(string $type, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $query = $request->query->all();
        $criteria = $query['criteria'] ?? [];

        if (empty($user->getEmail())) {
            $this->addFlash('error', 'Не заполнен email у пользователя');

            return $this->redirectToRoute('admin_user_list', $criteria);
        }

        try {
            $email = $this->emailService->createEmailWithStatisticData($user, $type, $criteria);
        } catch (Throwable) {
            $this->addFlash('error', 'Файл не был сформирован, т.к. в выгрузке присутствуют слушатели, у которых не назначены доступы');

            return $this->redirectToRoute('admin_user_list', $criteria);
        }

        return $this->redirectToRoute('admin_email_report_edit', ['mailId' => $email->getId()]);
    }
}
