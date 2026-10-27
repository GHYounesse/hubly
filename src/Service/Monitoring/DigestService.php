<?php

namespace App\Service\Monitoring;

use App\Repository\HebergementRepository;
use App\Repository\ProjetRepository;
use App\Repository\UptimeCheckRepository;
use App\Service\Notification\ChatAlerter;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Sends one summary of everything that needs attention (instead of one e-mail per alert).
 * Nothing is sent on a quiet day.
 */
class DigestService
{
    public function __construct(
        private readonly HebergementRepository $hebergementRepository,
        private readonly ProjetRepository $projetRepository,
        private readonly UptimeCheckRepository $uptimeCheckRepository,
        private readonly DigestFormatter $formatter,
        private readonly MailerInterface $mailer,
        private readonly ChatAlerter $chat,
        private readonly string $mailFrom,
        private readonly string $alertRecipient,
    ) {}

    /** @return bool true when a digest was sent */
    public function send(?\DateTimeImmutable $now = null): bool
    {
        $text = $this->build($now ?? new \DateTimeImmutable());
        if ($text === null) {
            return false;
        }

        $this->mailer->send(
            (new Email())
                ->from($this->mailFrom)
                ->to($this->alertRecipient)
                ->subject('Hubly : résumé du jour')
                ->text($text)
                ->html('<pre style="font-family:inherit;white-space:pre-wrap">' . htmlspecialchars($text, ENT_QUOTES) . '</pre>'),
        );
        $this->chat->send($text);

        return true;
    }

    public function build(\DateTimeImmutable $now): ?string
    {
        $hostings = array_map(
            fn ($h) => [
                'label' => $h->getSite() ?: ($h->getUrl() ?: 'Hébergement #' . $h->getId()),
                'detail' => sprintf('%s, le %s', $h->getFournisseur() ?: 'fournisseur inconnu', $h->getDateExpiration()?->format('d/m/Y')),
            ],
            $this->hebergementRepository->findExpiringSoon(15, 20),
        );

        $certificates = array_map(
            fn ($p) => [
                'label' => $p->getNom() ?: 'Projet #' . $p->getId(),
                'detail' => 'certificat valable jusqu\'au ' . $p->getSslExpiresAt()?->format('d/m/Y'),
            ],
            $this->projetRepository->findSslExpiringSoon(SslMonitor::WARNING_DAYS, 20),
        );

        $down = array_map(
            fn ($check) => [
                'label' => $check->getProjet()?->getNom() ?: 'Projet #' . $check->getProjet()?->getId(),
                'detail' => $check->getStatusCode() ? 'HTTP ' . $check->getStatusCode() : ($check->getError() ?: 'injoignable'),
            ],
            $this->uptimeCheckRepository->findCurrentlyDown(20),
        );

        return $this->formatter->format($hostings, $certificates, $down);
    }
}
