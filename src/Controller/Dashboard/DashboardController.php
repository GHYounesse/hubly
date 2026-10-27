<?php

namespace App\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Projet;
use App\Entity\Developpeur;
use App\Entity\Technologie;
use App\Entity\Hebergement;
 
/**
 * Class DashboardController
 * @package App\Controller\Dashboard
 * Controller for the application dashboard.
 *
 * Displays summaries of projects, developers, technologies, and hosting services,
 * and shows any URL errors detected during previous checks.
 */
final class DashboardController extends AbstractController
{
    /**
     * Renders the dashboard page.
     *
     * - Fetches all projects, developers, technologies, and hosting entities.
     * - Checks for any URL errors stored in var/url_errors.json and displays them as flash messages.
     * - Optionally deletes the error file after displaying its contents.
     *
     * @param ManagerRegistry $doctrine Doctrine service to access repositories for multiple entities.
     *
     * @return Response The rendered dashboard template with entities and flash messages.
     */
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(ManagerRegistry $doctrine): Response
    {
        /** @var \App\Repository\ProjetRepository $projetRepo */
        $projetRepo = $doctrine->getRepository(Projet::class);
        /** @var \App\Repository\DeveloppeurRepository $devRepo */
        $devRepo = $doctrine->getRepository(Developpeur::class);
        /** @var \App\Repository\TechnologieRepository $techRepo */
        $techRepo = $doctrine->getRepository(Technologie::class);
        /** @var \App\Repository\HebergementRepository $hebRepo */
        $hebRepo = $doctrine->getRepository(Hebergement::class);

    

        $errorFile = __DIR__.'/../../var/url_errors.json';
        if (file_exists($errorFile)) {
            $errors = json_decode(file_get_contents($errorFile), true);
            if (!empty($errors)) {
                foreach ($errors as $url) {
                    $this->addFlash('danger', "L'URL suivante est injoignable : $url");
                }
                // Optionnel : vider le fichier après affichage
                unlink($errorFile);
            }
        }
        $notificationRepo = $doctrine->getRepository(\App\Entity\Notification::class);

        return $this->render('dashboard/dashboard.html.twig', [
            'projetsCount'      => $projetRepo->count([]),
            'developpeursCount' => $devRepo->count([]),
            'technologiesCount' => $techRepo->count([]),
            'hebergementsCount' => $hebRepo->count([]),
            'projetsParStatut'  => $projetRepo->countByStatus(),
            'hebergementsProches' => $hebRepo->findExpiringSoon(30, 5),
            'alertes'           => $notificationRepo->findLatestUnread(5),
            'sslProches'        => $projetRepo->findSslExpiringSoon(30, 5),
            'sitesHorsLigne'    => $doctrine->getRepository(\App\Entity\UptimeCheck::class)->findCurrentlyDown(5),
            'coutAnnuel'        => $hebRepo->annualCostEstimate(),
        ]);

        
    }
}
