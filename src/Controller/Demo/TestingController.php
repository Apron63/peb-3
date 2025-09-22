<?php

declare (strict_types=1);

namespace App\Controller\Demo;

use App\Entity\Course;
use App\Entity\DemoLogger;
use App\Repository\DemoLoggerRepository;
use App\Service\DemoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class TestingController extends AbstractController
{
    public function __construct(
        private readonly DemoService $demoService,
        private readonly DemoLoggerRepository $demoLoggerRepository,
    ) {}

    #[Route('/demo/final-testing/{id<\d+>}/', name: 'app_demo_final_testing')]
    public function finalTesting(Course $course, Request $request): Response
    {
        if (! $course->isForDemo()) {
            throw new NotFoundHttpException();
        }

        $loggerId = $request->cookies->get('loggerId');

        if (null === $loggerId) {
            $logger = $this->demoService->createNewLogger($course);
        } else {
            $logger = $this->demoLoggerRepository->findOneBy(['loggerId' => $loggerId]);

            if (
                ! $logger instanceof DemoLogger
                || null !== $logger->getEndAt()
            ) {
                $logger = $this->demoService->createNewLogger($course);
            }
        }

        if (0 === $logger->getTimeLeftInSeconds()) {
            return $this->redirect(
                $this->generateUrl('app_demo_final_testing_end', ['id' => $course->getId()])
            );
        }

        $response = new Response();

        $response->headers->setCookie(new Cookie('loggerId', $logger->getLoggerId(), time() + 3600));

        return $this->render('frontend/demo/testing/final-testing.html.twig', [
            'course' => $course,
            'data' => $this->demoService->getData($logger),
        ], $response);
    }

    #[Route('/demo/testing-next-step/{id<\d+>}/', name: 'app_demo_testing_next_step',  condition: 'request.isXmlHttpRequest()')]
    public function nextStep(Course $course, Request $request): JsonResponse
    {
        if (! $course->isForDemo()) {
            throw new NotFoundHttpException();
        }

        $loggerId = $request->cookies->get('loggerId');
        if (null === $loggerId) {
            throw new NotFoundHttpException('Logger not found');
        }

        $logger = $this->demoLoggerRepository->findOneBy(['loggerId' => $loggerId]);
        if (! $logger instanceof DemoLogger) {
            throw new NotFoundHttpException('Logger not found');
        }

        return new JsonResponse([
            'redirectUrl' => $this->demoService->ticketProcessing($logger, $request->request->all()),
        ]);
    }

    #[Route('/demo/testing/end/{id<\d+>}/', name: 'app_demo_final_testing_end')]
    public function endTesting(Course $course, Request $request): Response
    {
        $loggerId = $request->cookies->get('loggerId');
        if (null === $loggerId) {
            throw new NotFoundHttpException('Logger not found');
        }

        $logger = $this->demoLoggerRepository->findOneBy(['loggerId' => $loggerId]);
        if (! $logger instanceof DemoLogger) {
            throw new NotFoundHttpException('Logger not found');
        }

        return $this->render('frontend/demo/testing/protocol.html.twig', [
            'logger' => $this->demoService->closeLogger($logger),
            'skipped' => $this->demoService->getSkippedQuestion($logger),
        ]);
    }
}
