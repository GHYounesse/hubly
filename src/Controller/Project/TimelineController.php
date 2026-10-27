<?php

namespace App\Controller\Project;

use App\Entity\Projet;
use App\Repository\ProjetRepository;
use App\Security\Voter\CompanyVoter;
use App\Service\Project\TimelineBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Planning view: every project with a delivery date on one time axis.
 */
final class TimelineController extends AbstractController
{
    #[Route('/planning', name: 'app_projets_timeline', methods: ['GET'])]
    public function index(ProjetRepository $repository, TimelineBuilder $builder): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $projets = array_filter(
            $repository->findBy([], ['dateLivraison' => 'ASC']),
            fn (Projet $projet) => $this->isGranted(CompanyVoter::ACCESS, $projet),
        );

        return $this->render('projets/timeline.html.twig', [
            'timeline' => $builder->build($projets, new \DateTimeImmutable()),
        ]);
    }
}
