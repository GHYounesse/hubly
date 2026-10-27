<?php

namespace App\Controller\Monitoring;

use App\Entity\Notification;
use App\Repository\HebergementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Service\Monitoring\HebergementExpirationService;
use App\Service\Monitoring\UrlService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Form\SearchType;

/**
 * Class AlertesNotificationsController
 * @package App\Controller\Monitoring
 * Controller managing alerts and notifications.
 *
 * - Handles checking for hosting expirations and URL accessibility.
 * - Creates notifications and optionally triggers emails via services.
 * - Displays unread notifications and allows marking them as read.
 */
class AlertesNotificationsController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private HebergementExpirationService $hebergementExpirationService;
    private UrlService $urlService;

    /**
     * Constructor for AlertesNotificationsController.
     *
     * Initializes the controller with required services.
     *
     * @param EntityManagerInterface $entityManager Entity manager used to access and persist entities.
     * @param HebergementExpirationService  a service to check hosting expirations,create notifications.
     * @param UrlService $urlService Service to check URLs and create notifications.
     */
    public function __construct(EntityManagerInterface $entityManager,HebergementExpirationService $hebergementExpirationService,UrlService $urlService)
    {
        $this->entityManager = $entityManager;
        $this->urlService = $urlService;
        $this->hebergementExpirationService = $hebergementExpirationService;
    }
    /**
     * Checks hosting expirations, creates notifications, and optionally sends emails.
     *
     * - Uses the `HebergementExpirationService` initialized by the constructor.
     *
     * @return JsonResponse JSON response with the count of notifications created.
     */
    #[Route('/check-expirations', name: 'app_check_expirations')]
    public function checkExpirations(): JsonResponse
    {
        // Lancer la vérification et la création des notifications + envoi mail
        $notifications = $this->hebergementExpirationService->checkExpirationsAndNotify();

        return new JsonResponse([
            'success' => true,
            'message' => count($notifications) . ' notification(s) créée(s).',
            'notificationsCount' => count($notifications),
        ]);
    }
    /**
     * Checks project URLs, creates notifications, and optionally sends emails.
     *
     * - Uses the `UrlService` initialized by the constructor.
     *
     * @return JsonResponse JSON response with the count of notifications created.
     */
    #[Route('/check-url', name: 'app_chek_url')]
    public function checkUrls(): JsonResponse
    {
        // Lancer la vérification et la création des notifications + envoi mail
        $notifications = $this->urlService->checkUrls();

        return new JsonResponse([
            'success' => true,
            'message' => count($notifications) . ' notification(s) créée(s).',
            'notificationsCount' => count($notifications),
        ]);
    }
    
    /**
     * List all notifications (`Notification`) and optionally search by message.
     *
     * - Uses EntityManagerInterface to fetch `Notification` entities.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/alertes-notifications', name: 'app_alertes_notifications')]
    public function index_notification(
        Request $request,
        EntityManagerInterface $em,
        ): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);

        // Liste des notifications par défaut
        $notifications = $em->getRepository(Notification::class)->findAll();

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (!empty($data['search'])) {
                // Recherche des notifications par message
                $notifications = $em->getRepository(Notification::class)
                    ->createQueryBuilder('a')
                    ->where('LOWER(a.message) LIKE LOWER(:search) OR LOWER(a.title) LIKE LOWER(:search)')
                    ->setParameter('search', '%' . $data['search'] . '%')
                    ->getQuery()
                    ->getResult();
            }

            return $this->render('alertes_notifications/_search_result.html.twig', [
                'form' => $form->createView(),
                'notifications' => $notifications,
            ]);
        }

        return $this->render('alertes_notifications/index.html.twig', [
            'form' => $form->createView(),
            'notifications' => $notifications,
        ]);
    }
    /**
     * Marks a notification as read.
     *
     * - Uses the `EntityManagerInterface` initialized by the constructor to fetch and update the notification.
     *
     * @param int $id The ID of the notification to mark as read.
     * @param HebergementRepository a repository for hosting entities(not used here).
     *
     * @return JsonResponse JSON response indicating success or error.
     */
    #[Route('/notification/{id}/mark-read', name: 'notification_mark_read', methods: ['POST','GET'])]
    public function markAsRead(int $id,Request $request): JsonResponse|RedirectResponse
    {
        $notification = $this->entityManager->getRepository(Notification::class)->find($id);
        
        if (!$notification) {
            $this->addFlash('error', 'Notification non trouvée');
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Notification non trouvée'
                ], 404);
            }
            return $this->redirectToRoute('app_alertes_notifications');
            
        }

        if (!$notification->isRead()) {
            $notification->setIsRead(true);
            $notification->setUpdatedAt(new \DateTime());

            $this->entityManager->flush();
            $this->addFlash('success', 'Notification marquée comme lue');
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Notification marquée comme lue'
            ]);
        }
        return $this->redirectToRoute('app_alertes_notifications');
    }
}
