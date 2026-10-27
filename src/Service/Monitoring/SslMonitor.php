<?php

namespace App\Service\Monitoring;

use App\Entity\Notification;
use App\Entity\Projet;
use App\Repository\ProjetRepository;
use App\Service\Notification\NotificationService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Stores when each project's TLS certificate expires and raises an alert shortly before it does.
 */
class SslMonitor
{
    /** An alert is raised when the certificate expires within this many days. */
    public const WARNING_DAYS = 14;

    public function __construct(
        private readonly ProjetRepository $projetRepository,
        private readonly SslCertificateChecker $checker,
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationService $notificationService,
        private readonly string $alertRecipient,
    ) {}

    /**
     * Checks every project with an https URL.
     *
     * @return int the number of certificates that expire soon (or already expired)
     */
    public function checkAll(?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable();
        $warnings = 0;

        foreach ($this->projetRepository->findAll() as $projet) {
            $host = $projet->getUrl() ? $this->checker->hostFromUrl($projet->getUrl()) : null;
            if ($host === null) {
                continue;
            }

            $expiry = $this->checker->getExpiry($host);
            $projet->setSslCheckedAt($now);

            if ($expiry === null) {
                continue; // unreachable: UrlService already alerts about that, keep the last known date
            }

            $projet->setSslExpiresAt($expiry);

            $days = self::daysLeft($expiry, $now);
            if ($days <= self::WARNING_DAYS) {
                ++$warnings;
                $this->raiseAlert($projet, $host, $days);
            }
        }

        $this->entityManager->flush();

        return $warnings;
    }

    /** Whole days between $now and the expiry; negative once the certificate has expired. */
    public static function daysLeft(\DateTimeImmutable $expiry, \DateTimeImmutable $now): int
    {
        return (int) floor(($expiry->getTimestamp() - $now->getTimestamp()) / 86400);
    }

    public static function alertMessage(Projet $projet, string $host, int $days): string
    {
        $when = $days < 0
            ? sprintf('a expiré il y a %d jour%s', -$days, -$days > 1 ? 's' : '')
            : ($days === 0 ? 'expire aujourd\'hui' : sprintf('expire dans %d jour%s', $days, $days > 1 ? 's' : ''));

        return sprintf("Le certificat SSL de %s (projet '%s') %s.", $host, $projet->getNom(), $when);
    }

    private function raiseAlert(Projet $projet, string $host, int $days): void
    {
        $message = self::alertMessage($projet, $host, $days);

        $existing = $this->entityManager->getRepository(Notification::class)->findOneBy([
            'projet' => $projet,
            'isRead' => false,
            'message' => $message,
        ]);
        if ($existing) {
            return; // the same unread alert is already waiting
        }

        $notification = (new Notification())
            ->setTitle('Certificat SSL à renouveler')
            ->setMessage($message)
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
            ->setIsRead(false)
            ->setProjet($projet);

        $this->entityManager->persist($notification);
        $this->notificationService->sendNotification($notification->getTitle(), $message, $this->alertRecipient);
    }
}
