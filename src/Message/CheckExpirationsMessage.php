<?php

namespace App\Message;

/**
 * Alert about hosting plans that are about to expire.
 * Dispatched by App\Schedule\MainSchedule, handled by the matching handler in src/MessageHandler.
 */
final class CheckExpirationsMessage
{
}
