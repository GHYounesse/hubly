<?php

namespace App\Message;

/**
 * Read the TLS certificate expiry of every https project.
 * Dispatched by App\Schedule\MainSchedule, handled by the matching handler in src/MessageHandler.
 */
final class CheckSslMessage
{
}
