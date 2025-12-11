<?php

declare (strict_types=1);

namespace App\Controller\Admin\Email;

use App\Entity\MailingQueue;
use App\Entity\User;
use App\Form\Admin\EmailAttachmentType;
use App\Form\Admin\EmailType as AdminEmailType;
use App\Repository\MailingQueueRepository;
use App\Service\EmailService\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class EditEmailController extends AbstractController
{
    public function __construct(
        private readonly MailingQueueRepository $mailingQueueRepository,
        private readonly EmailService $emailService,
    ) {}

    #[Route('/admin/email/edit/{mailId}/', name: 'admin_email_report_edit')]
    public function index(Request $request, ?int $mailId = null): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $email = $this->mailingQueueRepository->find($mailId);

        if (! $email instanceof MailingQueue) {
            throw new NotFoundHttpException('Email Record Not Found');
        }

        $form = $this->createForm(AdminEmailType::class, $email);
        $formAttachment = $this->createForm(EmailAttachmentType::class);
        $formAttachment->get('emailId')->setData($email->getId());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email->setCreatedBy($user);
            $this->mailingQueueRepository->save($email, true);
            $this->emailService->sendEmailWithAttachments($email);

            $this->addFlash('success', 'Сообщение отправлено');

            return $this->redirectToRoute('admin_homepage');
        }

        return $this->render('admin/email/edit/index.html.twig', [
            'form' => $form->createView(),
            'formAttachment' => $formAttachment->createView(),
            'files' => $this->emailService->getUploadedFiles($user, $email->getId()),
        ]);
    }

    #[Route('/admin/email/read-file/', name: 'admin_email_read_file')]
    public function readFile(Request $request): Response
    {
        $filename = $request->query->get('filename');
        $emailId = (int) $request->query->get('emailId');

        $outputFileName = $this->emailService->readFile($filename, $emailId);

        $response = new BinaryFileResponse($outputFileName);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filename);

        return $response;
    }

    #[Route('/admin/email/delete-file/', name: 'admin_email_delete_file')]
    public function deleteFile(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $filename = $request->query->get('filename');
        $emailId = (int) $request->query->get('emailId');

        $this->emailService->deleteFile($filename, $user, $emailId);
        $this->addFlash('success', 'Вложение удалено');

        return $this->redirectToRoute('admin_email_report_edit', ['mailId' => $emailId]);
    }

    #[Route('/admin/email/add-file/', name: 'admin_email_add_file')]
    public function addFile(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(EmailAttachmentType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $emailId = (int) $form->get('emailId')->getData();
            $personalPath = $this->emailService->getUserUploadDir($user, $emailId);
            $attachment = $form->get('attachment')->getData();
            $attachmentName = $attachment->getClientOriginalName();
            $attachment->move($personalPath, $attachmentName);

            $this->addFlash('success', 'Вложение добавлено');
        }

        return $this->redirectToRoute('admin_email_report_edit', ['mailId' => $emailId]);
    }
}
