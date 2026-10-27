<?php

namespace App\Service\Monitoring;

use App\Entity\Hebergement;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\Notification\NotificationService;
use DateTime;

class HebergementExpirationService
{
    private EntityManagerInterface $entityManager;
    private NotificationService $notificationService;

    private string $alertRecipient;

    public function __construct(EntityManagerInterface $entityManager, NotificationService $notificationService, string $alertRecipient)
    {
        $this->entityManager = $entityManager;
        $this->notificationService = $notificationService;
        $this->alertRecipient = $alertRecipient;
    }

    /**
     * Vérifie les hébergements expirant bientôt, crée des notifications (sans doublon) et envoie un email.
     *
     * @return Notification[] Liste des notifications créées
     */
    public function checkExpirationsAndNotify(): array
    {
        $hebergements = $this->entityManager->getRepository(Hebergement::class)->findAll();
        $notifications = [];
        $aujourdhui = new \DateTime();

        foreach ($hebergements as $hebergement) {
            $dateExpiration = $hebergement->getDateExpiration();

            $interval = $aujourdhui->diff($dateExpiration);

            //$interval = $aujourdhui->diff($dateExpiration);
            //$joursRestants = (int)$interval->format('%r%a'); // nombre de jours avec signe
            $joursRestants = $interval->days;

            if ($joursRestants ==15) {
                $title = 'Expiration proche';
                $message = sprintf(
                    "⚠️ L'hébergement '%s' expire dans %d jour%s (le %s).",
                    $hebergement->getFournisseur(),
                    $joursRestants,
                    $joursRestants > 1 ? 's' : '',
                    $dateExpiration->format('d-m-Y')
                );

                // Vérifier s'il existe déjà une notification identique non lue pour cet hébergement
                $existingNotification = $this->entityManager->getRepository(Notification::class)
                    ->findOneBy([
                        'hebergement' => $hebergement,
                        'isRead' => true,
                    ]);

                if (!$existingNotification) {

                    error_log("✅ Création d'une notification pour '{$hebergement->getFournisseur()}' (exp. dans {$joursRestants} jours)");

                    $notification = new Notification();
                    $notification->setTitle($title);
                    $notification->setMessage($message);
                    $notification->setCreatedAt(new \DateTime());
                    $notification->setUpdatedAt(new \DateTime());
                    $notification->setIsRead(false);

                    // IMPORTANT : Lier la notification à l'hébergement
                    $notification->setHebergement($hebergement);

                    $this->entityManager->persist($notification);
                    $notifications[] = $notification;

                    // Envoi d'email

                    $this->notificationService->sendNotification(
                        $title,
                        $message,
                        $this->alertRecipient
                    );
                } else {
                    error_log("ℹ️ Notification déjà existante pour '{$hebergement->getFournisseur()}' -> Pas de doublon.");
                }
            }
        }

        $this->entityManager->flush();
        return $notifications;
    }
}
