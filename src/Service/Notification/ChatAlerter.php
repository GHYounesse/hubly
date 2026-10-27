<?php

namespace App\Service\Notification;

use Psr\Log\LoggerInterface;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

/**
 * Posts an alert in the team chat (Slack) through Symfony Notifier.
 *
 * With the default SLACK_DSN=null://null nothing leaves the application, so it is safe in development.
 * A chat outage never breaks the check that raised the alert: failures are logged and swallowed.
 */
class ChatAlerter
{
    public function __construct(
        private readonly ChatterInterface $chatter,
        private readonly LoggerInterface $logger,
    ) {}

    public function send(string $text): void
    {
        try {
            $this->chatter->send(new ChatMessage($text));
        } catch (\Throwable $e) {
            $this->logger->warning('Chat alert not delivered: ' . $e->getMessage());
        }
    }
}
