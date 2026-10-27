<?php

namespace App\MessageHandler;

use App\Message\CheckUrlsMessage;
use App\Service\Monitoring\UrlService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CheckUrlsHandler
{
    public function __construct(private readonly UrlService $service) {}

    public function __invoke(CheckUrlsMessage $message): void
    {
        $this->service->checkUrls();
    }
}
