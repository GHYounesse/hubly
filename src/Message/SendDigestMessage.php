<?php

namespace App\Message;

/**
 * Send the daily summary of what needs attention.
 * Dispatched by App\Schedule\MainSchedule, handled by the matching handler in src/MessageHandler.
 */
final class SendDigestMessage
{
}
