<?php

namespace App\Service\Monitoring;

use App\Entity\Notification;
use App\Entity\Projet;
use App\Repository\ProjetRepository;
use App\Service\Notification\NotificationService;
use Doctrine\ORM\EntityManagerInterface;

class UrlService
{
    public function __construct(
        private readonly ProjetRepository $projetRepository,
        private readonly UrlCheckerService $urlChecker,
        private readonly UptimeRecorder $uptimeRecorder,
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationService $notificationService,
        private readonly string $alertRecipient
    ) {}

    /**
     * Checks the URL of every project, stores the result in the uptime history
     * and raises a notification for each unreachable URL.
     *
     * @return string[] The URLs that could not be reached
     */
    public function checkUrls(): array
    {
        $errors = [];

        foreach ($this->projetRepository->findAll() as $projet) {
            $url = $projet->getUrl();
            if (!$url) {
                continue;
            }

            $result = $this->urlChecker->checkUrl($url);
            $this->uptimeRecorder->record($projet, $result);

            if ($result['success']) {
                continue;
            }

            $errors[] = $result['url'];
            $detail = $result['status_code'] !== null
                ? "URL inaccessible (code {$result['status_code']})"
                : 'Erreur lors de la vérification : ' . $result['error'];

            $this->createNotification($projet, $result['url'], $detail);
        }

        $this->entityManager->flush();

        return $errors;
    }

    private function createNotification(Projet $projet, string $url, string $message): void
    {
        // Do not raise the same unread alert twice
        $existingNotification = $this->entityManager->getRepository(Notification::class)
            ->findOneBy([
                'projet' => $projet,
                'isRead' => false,
                'message' => $message,
            ]);

        if ($existingNotification) {
            return;
        }

        $notification = new Notification();
        $notification->setTitle("Problème avec l'URL du projet");
        $notification->setMessage("Le projet '{$projet->getNom()}' a une URL inaccessible : $url. Détail : $message");
        $notification->setCreatedAt(new \DateTime());
        $notification->setUpdatedAt(new \DateTime());
        $notification->setIsRead(false);
        $notification->setProjet($projet);

        $this->entityManager->persist($notification);

        $this->notificationService->sendNotification(
            $notification->getTitle(),
            $notification->getMessage(),
            $this->alertRecipient
        );
    }
}
