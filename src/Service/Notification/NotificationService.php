<?php
namespace App\Service\Notification;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Notification;
class NotificationService
{
    private MailerInterface $mailer;
    private EntityManagerInterface $entityManager;
    private string $mailFrom;
    private ChatAlerter $chat;

    public function __construct(MailerInterface $mailer , EntityManagerInterface $entityManager, string $mailFrom, ChatAlerter $chat)
    {
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->mailFrom = $mailFrom;
        $this->chat = $chat;

    }

    public function sendNotification(string $subject, string $content, string $recipientEmail): void
    {
        error_log("Préparation envoi email à $recipientEmail avec sujet : $subject");
    
        $email = (new Email())
            ->from($this->mailFrom)
            ->to($recipientEmail)
            ->subject($subject)
            ->text($content)
            ->html("<p>$content</p>");
    
        $this->mailer->send($email);
        $this->chat->send($subject . "\n" . $content);
    }
    
    /**
     * Retourne les notifications non lues
     * 
     * @return Notification[]
     */
    public function getNotifications(): array
    {
        return $this->entityManager
            ->getRepository(Notification::class)
            ->findBy(['isRead' => false]);
    }

    /**
     * Marque une notification comme lue
     */
    public function markAsRead(int $id): ?Notification
    {
        $notification = $this->entityManager->getRepository(Notification::class)->find($id);

        if ($notification && !$notification->isRead()) {
            $notification->setIsRead(true);
            $notification->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();
        }

        return $notification;

    }
    /**
     * Compte les notifications non lues.
     */
    public function countUnreadNotifications(): int
    {
        return $this->entityManager
            ->getRepository(Notification::class)
            ->count(['isRead' => false]);
    }
}
