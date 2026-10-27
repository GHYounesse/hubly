<?php

namespace App\MessageHandler;

use App\Message\PurgeUptimeHistoryMessage;
use App\Repository\UptimeCheckRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class PurgeUptimeHistoryHandler
{
    /** Keep three months of history: enough for the 30-day uptime figure and its trend. */
    public const RETENTION_DAYS = 90;

    public function __construct(private readonly UptimeCheckRepository $repository) {}

    public function __invoke(PurgeUptimeHistoryMessage $message): void
    {
        $this->repository->purgeOlderThan(self::RETENTION_DAYS);
    }
}
