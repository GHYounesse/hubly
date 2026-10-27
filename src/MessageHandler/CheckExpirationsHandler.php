<?php

namespace App\MessageHandler;

use App\Message\CheckExpirationsMessage;
use App\Service\Monitoring\HebergementExpirationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CheckExpirationsHandler
{
    public function __construct(private readonly HebergementExpirationService $service) {}

    public function __invoke(CheckExpirationsMessage $message): void
    {
        $this->service->checkExpirationsAndNotify();
    }
}
