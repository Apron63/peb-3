<?php

namespace App\Service;

use App\Repository\LoggerRepository;

class HistoryService
{
    public function __construct(
        readonly LoggerRepository $loggerRepository,
    ) {}

    public function getPermissionTestingResults()
    {
        # code...
    }
    
}
