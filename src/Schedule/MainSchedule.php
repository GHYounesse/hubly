<?php

namespace App\Schedule;

use App\Message\CheckExpirationsMessage;
use App\Message\CheckSslMessage;
use App\Message\CheckUrlsMessage;
use App\Message\PurgeUptimeHistoryMessage;
use App\Message\SendDigestMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Everything Hubly does on its own. Run the worker to execute it:
 *
 *   php bin/console messenger:consume scheduler_default -vv
 */
#[AsSchedule('default')]
final class MainSchedule implements ScheduleProviderInterface
{
    public function __construct(private readonly CacheInterface $cache) {}

    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(RecurringMessage::every('15 minutes', new CheckUrlsMessage()))
            ->add(RecurringMessage::every('1 day', new CheckSslMessage(), from: '03:00'))
            ->add(RecurringMessage::every('1 day', new PurgeUptimeHistoryMessage(), from: '04:00'))
            ->add(RecurringMessage::every('1 day', new CheckExpirationsMessage(), from: '08:00'))
            ->add(RecurringMessage::every('1 day', new SendDigestMessage(), from: '08:15'))
            // Remember what already ran so a restart of the worker does not replay it
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true);
    }
}
