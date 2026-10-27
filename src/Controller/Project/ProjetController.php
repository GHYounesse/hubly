<?php

namespace App\Controller\Project;

use App\Entity\Projet;
use App\Security\Voter\CompanyVoter;
use App\Entity\Societe;
use App\Form\ProjetType;
use App\Form\SearchType;
use App\Entity\Historique;
use App\Service\Monitoring\UrlCheckerService;
use App\Repository\ProjetRepository;
use App\Repository\UptimeCheckRepository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class ProjetController
 * 
 * @package App\Controller\Project
 * 
 * Controller managing CRUD operations and views for Projet entities.
 */
#[Route('/projets')]
class ProjetController extends AbstractController
{   
    /**
     * Displays the list of projects with optional search functionality.
     *
     * @param Request $request The current HTTP request.
     * @param EntityManagerInterface $entityManager Entity manager for database operations.
     * @param ProjetRepository $projetRepository Repository for fetching projects.
     *
     * @return Response The rendered projects index page with search form.
     */
    #[Route('/', name: 'app_projets_index_main', methods: ['GET', 'POST'])]
    #public function index(Request $request, EntityManagerInterface $entityManager, ProjetRepository $projetRepository): Response
    public function index(Request $request,ProjetRepository $projetRepository): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);

        $projets = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (!empty($data['search'])) {
                $projets = $projetRepository->createQueryBuilder('p')
                    ->where('LOWER(p.nom) LIKE LOWER(:search)')
                    ->setParameter('search', '%' . $data['search'] . '%')
                    ->getQuery()
                    ->getResult();
            } else {
                $projets = $projetRepository->findBy([], ['status' => 'ASC']);
            }
        } else {
            $projets = $projetRepository->findBy([], ['status' => 'ASC']);
        }

        $projets = array_values(array_filter($projets, fn (Projet $projet) => $this->isGranted(CompanyVoter::ACCESS, $projet)));

        return $this->render('projets/index.html.twig', [
            'projets' => $projets,
            'form' => $form->createView(),
        ]);
    }
    /**
     * Creates a new project associated optionally with a société.
     *
     * @param Request $request The current HTTP request.
     * @param EntityManagerInterface $entityManager Entity manager for database operations.
     * @param LoggerInterface $logger Logger to capture errors during project creation.
     *
     * @return Response The rendered form page or redirect after successful creation.
     */
    #[Route('/new', name: 'app_projets_new', methods: ["GET", "POST"])]
    public function new(Request $request, EntityManagerInterface $entityManager ,LoggerInterface $logger): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $projet = new Projet();

        $societeId = $request->query->get('societe');
        $societe = null;
        if ($societeId) {
            $societe = $entityManager->getRepository(Societe::class)->find($societeId);
            $projet->setSociete($societe); // set the Societe initially
        }

        $form = $this->createForm(ProjetType::class, $projet, [
            'societe' => $societeId,
        ]);

        $form->remove('created');
        $form->remove('updated');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Ensure the Societe is set (if the form is hidden or overridden)
            try{
                if ($societe) {
                    $projet->setSociete($societe);
                }

                $projet->setCreated(new \DateTime());
                $projet->setUpdated(new \DateTime());

                $entityManager->persist($projet);
                $entityManager->flush();
                $this->addFlash('success','Projet créée avec succès !');

                return $this->redirectToRoute('app_societe_projets', ['id' => $projet->getSociete()->getId()]);
            } catch (\Throwable $e) {
                $logger->error('Error creating projet: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de la création.');
            }
        }

        return $this->render('projets/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    /**
     * Shows a project and optionally checks the URL accessibility.
     *
     * @param Projet $projet The project to display.
     * @param UrlCheckerService $checker Service to validate the project URL.
     *
     * @return Response The rendered project detail page.
     */
    #[Route('/show/{id}', name: 'app_projets_show', methods: ['GET'])]
    public function showOld(Projet $projet, UrlCheckerService $checker, UptimeCheckRepository $uptimeRepository): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted(CompanyVoter::ACCESS, $projet);

        $url = $projet->getUrl();
    
        if (!$url) {
            $this->addFlash('warning', 'Aucune URL définie pour ce projet.');
            // Tu peux choisir de rendre la vue sans tester l'URL ou rediriger ailleurs
            return $this->render('projets/show.html.twig', [
                'projet' => $projet,
                'checks' => $uptimeRepository->findRecentForProjet($projet, 30),
                'uptime' => $uptimeRepository->uptimePercentage($projet, 30),
            ]);
        }
    
        $result = $checker->checkUrl($url);
    
        if ($result['success']) {
            $this->addFlash('success', "L'URL {$result['url']} est accessible (statut HTTP {$result['status_code']}).");
        } else {
            $this->addFlash('danger', "L'URL {$result['url']} est inaccessible. Erreur : {$result['error']}");
        }
    
        return $this->render('projets/show.html.twig', [
            'projet' => $projet,
            'checks' => $uptimeRepository->findRecentForProjet($projet, 30),
            'uptime' => $uptimeRepository->uptimePercentage($projet, 30),
        ]);
    }
    // /**
    //  * Shows a project in a read-only form.
    //  *
    //  * @param Projet $projet The project to display.
    //  * @param FormFactoryInterface $formFactory Factory to create the read-only form.
    //  *
    //  * @return Response The rendered project detail page with read-only form.
    //  */
    // #[Route('/show/{id}', name: 'app_projets_show', methods: ["GET"])]
    // public function show(Projet $projet, FormFactoryInterface $formFactory): Response
    // {
    //     $form = $formFactory->create(ProjetType::class, $projet, [
    //         'disabled' => true,
    //     ]);

    //     return $this->render('projets/show.html.twig', [
    //         'form' => $form->createView(),
    //         'projet' => $projet,
    //     ]);
    // }

    /**
     * Updates an existing project.
     *
     * @param Request $request The current HTTP request.
     * @param Projet $projets The project to update.
     * @param EntityManagerInterface $entityManager Entity manager for database operations.
     *
     * @return Response The rendered edit form page or redirect after successful update.
     */
    #[Route('/update/{id}', name: 'app_projets_update', methods: ["GET", "POST"])]
    public function update(
        Request $request, 
        Projet $projets,
        EntityManagerInterface $entityManager
    ): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted(CompanyVoter::ACCESS, $projets);

        $form = $this->createForm(ProjetType::class, $projets, [
            'societe' => $projets->getSociete()?->getId(), // optional, if using in form type
        ]);

        $form->remove('created');
        $form->remove('updated');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $projets->setUpdated(new \DateTime());

            $entityManager->flush();
            $this->addFlash("success","Projet mise à jour avec succès !");
            return $this->redirectToRoute('app_societe_projets', ['id' => $projets->getSociete()->getId()]);
        }

        return $this->render('projets/edit.html.twig', [
            'projets' => $projets,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a project and all related notifications.
     *
     * @param int $id The ID of the project to delete.
     * @param ManagerRegistry $doctrine Doctrine service for entity management.
     *
     * @return Response Redirects to the associated société projects page.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If no project is found with the given ID.
     */
    #[Route('/delete/{id}', name: 'app_projet_delete', methods: ['POST'])]
    public function delete(int $id, ManagerRegistry $doctrine): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $entityManager = $doctrine->getManager();
        $projet = $entityManager->getRepository(Projet::class)->find($id);

        if (!$projet) {
            throw $this->createNotFoundException('Aucun projet trouvé avec l\'ID ' . $id);
        }

        $this->denyAccessUnlessGranted(CompanyVoter::ACCESS, $projet);

        // Supprimer les notifications liées au projet
        foreach ($projet->getNotifications() as $notification) {
            $entityManager->remove($notification);
        }

        // Supprimer le projet
        $entityManager->remove($projet);
        $entityManager->flush();

        $this->addFlash('success', 'Le projet a été supprimé avec succès.');

        return $this->redirectToRoute('app_societe_projets', ['id' => $projet->getSociete()->getId()]);
    }

    /**
     * Checks the accessibility of a project's URL (ping) and displays the result.
     *
     * - If the user is not authenticated, redirects to the login page.
     * - If the project has no URL, returns a 400 response with an error message.
     * - Uses UrlCheckerService to verify URL accessibility and renders success or error templates accordingly.
     *
     * @param Projet $projet The project whose URL will be checked.
     * @param UrlCheckerService $checker Service used to validate the project's URL.
     *
     * @return Response The rendered template showing the ping result or an error response if no URL is defined.
     */
    #[Route('/projet/ping/{id}', name: 'projet_ping')]
    public function pingProjet(Projet $projet, UrlCheckerService $checker): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted(CompanyVoter::ACCESS, $projet);
        
        $url = $projet->getUrl(); // ou getUrl()

        if (!$url) {
            return new Response('Aucune URL définie pour ce projet.', 400);
        }

        $result = $checker->checkUrl($url);

        if ($result['success']) {
            return $this->render('projets/ping_success.html.twig', [
                'url' => $url,
                'status_code' => $result['status_code']
            ]);
        } else {
            return $this->render('projets/ping_error.html.twig', [
                'error' => $result['error'],
                'url' => $url
            ]);
        }
    }
}
