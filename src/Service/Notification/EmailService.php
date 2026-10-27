<?php

namespace App\Service\Notification;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class EmailService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;

    private string $mailFrom;
    private string $alertRecipient;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger, string $mailFrom, string $alertRecipient)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->mailFrom = $mailFrom;
        $this->alertRecipient = $alertRecipient;
    }

    /**
     * Envoie un e-mail.
     *
     * @param string|null $to Destinataire de l'e-mail (ALERT_RECIPIENT par défaut)
     * @param string $content Contenu HTML de l'e-mail
     * @param string $subject Sujet de l'e-mail
     */
    public function sendEmail(?string $to = null, string $content = '<p>Test</p>', string $subject = 'Test'): void
    {
        $to ??= $this->alertRecipient;

        try {
            $email = (new Email())
                ->from($this->mailFrom)
                ->to($to)
                ->subject($subject)
                ->html($content);

            $this->mailer->send($email);

            $this->logger->info("E-mail envoyé avec succès à {$to}.");
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'envoi de l'e-mail : " . $e->getMessage());
        }
    }
}
