<?php

namespace App\MessageHandler;

use App\Message\CheckSslMessage;
use App\Service\Monitoring\SslMonitor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CheckSslHandler
{
    public function __construct(private readonly SslMonitor $service) {}

    public function __invoke(CheckSslMessage $message): void
    {
        $this->service->checkAll();
    }
}
