<?php

// src/EventSubscriber/NotificationSubscriber.php

namespace App\EventSubscriber;

use App\Service\Notification\NotificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class NotificationSubscriber implements EventSubscriberInterface
{
    private NotificationService $notificationService;
    private Environment $twig;
    private RequestStack $requestStack;

    public function __construct(NotificationService $notificationService, Environment $twig, RequestStack $requestStack)
    {
        $this->notificationService = $notificationService;
        $this->twig = $twig;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Ne traiter que la requête principale
        if (!$event->isMainRequest()) {
            return;
        }

        // Récupérer les notifications non lues
        $notifications = $this->notificationService->getNotifications();
        $unreadCount = count($notifications);

        // Vérifier si la requête est une demande de marquage comme lu
        $request = $this->requestStack->getCurrentRequest();
        if ($request && $request->isXmlHttpRequest() && $request->getMethod() === 'POST') {
            $id = (int) $request->get('id');
            if ($id) {
                $this->notificationService->markAsRead($id);
                $unreadCount = $this->notificationService->countUnreadNotifications();
            }
        }

        //------------CE CODE AFFICHE LES LISTE DES NOTIFICATIONS ----------------
        $this->twig->addGlobal('notifications', $notifications);
        $this->twig->addGlobal('notification_count', $unreadCount);
    }
}
