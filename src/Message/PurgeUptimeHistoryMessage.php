<?php

namespace App\Message;

/**
 * Delete uptime checks older than the retention period.
 * Dispatched by App\Schedule\MainSchedule, handled by the matching handler in src/MessageHandler.
 */
final class PurgeUptimeHistoryMessage
{
}
