<?php

namespace App\MessageHandler;

use App\Message\SendDigestMessage;
use App\Service\Monitoring\DigestService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendDigestHandler
{
    public function __construct(private readonly DigestService $service) {}

    public function __invoke(SendDigestMessage $message): void
    {
        $this->service->send();
    }
}
