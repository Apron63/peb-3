<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends MobileController
{
    public function __construct(
    ) {}

    #[Route('/admin/dashboard/', name: 'admin_dashboard')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(): Response
    {
        $freeDiskSpace = disk_free_space('/');
        $diskTotalSpace = disk_total_space('/');
        $usedSpace = (int) (($diskTotalSpace - $freeDiskSpace) / $diskTotalSpace * 100);

        $mailSentToday = 0;
        $mailNotSended = 0;

        return $this->mobileRender('admin/dashboard/index.html.twig', [
            'freeDiskSpace' => $this->getSymbolByQuantity($freeDiskSpace),
            'diskTotalSpace' => $this->getSymbolByQuantity($diskTotalSpace),
            'usedSpace' => $usedSpace,
            'mailSenttoday' => $mailSentToday,
            'mailNotSended' => $mailNotSended,
        ]);
    }

    private function getSymbolByQuantity($bytes): string
    {
        $symbols =['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $exp = floor(log($bytes)/log(1024));
    
        return sprintf('%.2f ' . $symbols[$exp], ($bytes/pow(1024, floor($exp))));
    }
}
