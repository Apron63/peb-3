<?php

namespace App\Controller\Admin;

use App\Service\AdminReportService;
use App\Form\Admin\SendListToEmailType;
use App\Repository\PermissionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserReportAndSendController extends AbstractController
{
    public function __construct(
        private readonly AdminReportService $reportService,
        private readonly PermissionRepository $permissionRepository,
    ) {}
    
    #[Route('/admin/user/report/send/get_content/', name: 'admin_user_report_send_get_content', condition: 'request.isXmlHttpRequest()')]
    public function adminGetModalContent():JsonResponse
    {
        $data = [
            'emails' => '',
            'subject' => '',
            'comment' => '
                <p>Здравствуйте!</p>
                <p>Направляю файл с данными по назначенным доступам в системе дистанционного обучения: ФИО слушателя, должность, организация, логин, пароль, курс, количество дней.</p>
                <ol>
                    <li>Для начала обучения требуется перейти по ссылке: <a href="https://ucoks.safety63.ru/"><u><u>СТРАНИЦА ВХОДА </u></u></a>(или ввести в поисковой строке любого браузера - <a href="https://ucoks.safety63.ru/login"><u><u>https://ucoks.safety63.ru/login</u></u></a>);</li>
                    <li>Далее необходимо ввести свои данные из файла.</li>
                </ol>
                <p>&nbsp;</p>
                <p>Обучение можно проходить с любого устройства: персональный компьютер, планшет, смартфон.</p>
                <p>Приятного обучения!</p>
                <p><br />
                Ответственный методист: &hellip;<br />
                Телефон: &hellip;</p>
                <p>График работы методиста: пн-чт - с 08:00 до 17:00, пт &ndash; с 08:00 до 16:00</p>
                <p>&nbsp;</p>
                <p>Техническая поддержка: телефон +7-937-077-11-65, +7 (846) 26-915-26</p>
                <p>Email: <a href="mailto:support@safety63.ru"><u><u>support@safety63.ru</u></u></a><br />
                График работы техподдержки: с 8:00 до 22:00 без перерывов и выходных</p>
            ',
        ];

        $content = $this->renderView('admin/user/_send_email.html.twig', [
            'form' => $this->createForm(SendListToEmailType::class, $data)->createView(),
        ]);

        return new JsonResponse(['data' => $content]);
    }
    
    #[Route('/admin/user/report/send_letter_to_client/', name: 'admin_user_report_send_letter_to_client', condition: 'request.isXmlHttpRequest()')]
    public function adminSendLetterToClient(Request $request):JsonResponse
    {
        $recipient = $request->get('recipient');
        $subject = $request->get('subject');
        $comment = $request->get('comment');
        $type = $request->get('type');
        $criteria = $request->get('criteria');

        if (!empty($criteria)) {
            $data = $this->permissionRepository->getUserSearchQuery($criteria['user_search'], true)->getResult();
        }

        return new JsonResponse(
            $this->reportService->generateListAndSend($recipient, $subject, $comment, $type, $data)
        );
    }
}
