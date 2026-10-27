<?php

namespace App\Controller\Company;

use App\Entity\User;
use App\Entity\Societe;
use App\Security\Voter\CompanyVoter;
use App\Form\SearchType;
use App\Form\SocieteType;
use App\Repository\UserRepository;
use App\Repository\ProjetRepository;
use App\Repository\SocieteRepository;
use App\Repository\DeveloppeurRepository;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Doctrine\Common\Collections\ArrayCollection;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;


/**
 * Class SocieteController
 *
 * @package App\Controller\Company
 *
 * Handles all routes related to sociétés and their projects.
 */
final class SocieteController extends AbstractController
{
    /**
     * Display all sociétés, or directly redirect to the projects page if only one exists.
     *
     * @param Request             $request           Request object for pagination.
     * @param ProjetRepository    $projetRepository  Repository for projets.
     * @param PaginatorInterface  $paginator         Paginator service.
     * @param LoggerInterface     $logger            Logger service for error/info tracking.
     *
     * @return Response
     */
    #[Route('/societes', name: 'app_societes')]
    public function index(
        Request $request,
        ProjetRepository $projetRepository,
        PaginatorInterface $paginator,
        SocieteRepository $societeRepository,
        UserRepository $userRepository,
        DeveloppeurRepository $developpeurRepository,
        LoggerInterface $logger
    ): Response {
        if (!$this->isGranted('ROLE_USER')) {
            $logger->warning('Unauthorized access attempt to /societes');
            return $this->redirectToRoute('app_login');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $logger->info('User ID ' . $user->getId() . ' accessing sociétés list.');
        try {
            $societes = $user->getSocietes();
            
        } catch (\Throwable $e) {
            $logger->error('Database error while fetching sociétés: ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors du chargement des sociétés.');
            return $this->redirectToRoute('app_home');
        }
        $logger->info('User ID ' . $user->getId() . ' has access to ' . count($societes) . ' sociétés.');
        // If only one société → call the projets method
        if (count($societes) === 1) {
            $societeId = $societes[0]->getId();

            return $this->redirectToRoute('app_societe_projets', [
                'id' => $societeId,
            ]);
        }
        $logger->info('Rendering sociétés list for user ID ' . $user->getId() . '.');
        // Paginate the sociétés array
        $societesPagination = $paginator->paginate(
            $societes,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('societes/index.html.twig', [
            'societes' => $societesPagination,
        ]);
    }


    /**
     * Display all projects related to a specific société.
     *
     * @param int                     $id                       Société ID.
     * @param Request                 $request                  Request object.
     * @param SocieteRepository       $societeRepository        Repository for sociétés.
     * @param ProjetRepository        $projetRepository         Repository for projets.
     * @param UserRepository          $userRepository           Repository for users.
     * @param DeveloppeurRepository   $developpeurRepository    Repository for developers.
     * @param PaginatorInterface      $paginator                Paginator service.
     * @param LoggerInterface         $logger                   Logger service.
     *
     * @return Response
     */

    #[Route('/societes/{id}/projets', name: 'app_societe_projets', requirements: ['id' => '\d+'])]
    public function projets(
        int $id,
        Request $request,
        SocieteRepository $societeRepository,
        ProjetRepository $projetRepository,
        UserRepository $userRepository,
        DeveloppeurRepository $developpeurRepository,
        PaginatorInterface $paginator,
        LoggerInterface $logger
    ): Response {
        // Sécurité
        if (!$this->isGranted('ROLE_USER')) {
            $logger->warning("Unauthorized access attempt to société $id projects");
            return $this->redirectToRoute('app_login');
        }

        // Société
        $societe = $societeRepository->find($id);
        if (!$societe) {
            throw $this->createNotFoundException("Société avec l'ID {$id} introuvable.");
        }

        $this->denyAccessUnlessGranted(CompanyVoter::ACCESS, $societe);

        // Filtres depuis la requête
        $filters = [
            'type' => $request->query->get('type'),
            'status' => $request->query->get('status'),
            'suiviPar' => $request->query->get('suivi_par_id'),
            'developpeurPrincipal' => $request->query->get('developpeur_principal_id'),
            'commercial' => $request->query->get('commercial_id'),
            'priorite' => $request->query->get('priorite'),
            'etatProjet' => $request->query->get('etat_projet'),
            'client' => $request->query->get('client'),
        ];

        // Nettoyage des filtres vides
        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');

        // Société imposée
        $filters['societe'] = $societe;

        // QueryBuilder (PAS getResult ici)
        $queryBuilder = $projetRepository->findByFiltersQuery($filters);

        // Pagination Doctrine propre
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            25
        );

        //Count propre (méthode dédiée)
        $totalProjets = $projetRepository->countByFilters($filters);

        return $this->render('projets/index.html.twig', [
            'projets' => $pagination,
            'societe_denomination' => $societe->getDenomination(),
            'societe_id' => $societe->getId(),
            'filters' => $filters,
            'form' => $this->createForm(SearchType::class)->createView(),
            'users' => $userRepository->findAll(),
            'developpeurs' => $developpeurRepository->findAll(),
            'total_projets' => $totalProjets,
        ]);
    }


    /**
     * Create a new Societe.
     *
     * @param Request                $request       HTTP request object.
     * @param EntityManagerInterface $em            Doctrine entity manager.
     * @param LoggerInterface        $logger        Logger service for error/info tracking.
     *
     * @return Response
     */
    #[Route('/societes/new', name: 'app_societe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $logger->warning('Unauthorized attempt to create a société');
            throw $this->createAccessDeniedException();
        }

        $societe = new Societe();
        $form = $this->createForm(SocieteType::class, $societe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {

                // Handle the selected user manually
                // $selectedUser = $form->get('users')->getData();
                // foreach ($selectedUser as $user) {
                //     $societe->addUser($user);
                //     $user->addSociete($societe);
                // }

                // Handle logo upload
                $logoFile = $form->get('logo')->getData();
                if ($logoFile instanceof UploadedFile) {
                    $newFilename = uniqid() . '.' . $logoFile->guessExtension();
                    $logoFile->move(
                        $this->getParameter('logos_directory'),
                        $newFilename
                    );
                    $societe->setLogo("uploads/logos/$newFilename");
                }
                
                $em->persist($societe);
                $em->flush();

                $logger->info('New société created: ' . $societe->getDenomination());
                $this->addFlash('success', 'Société créée avec succès !');

                return $this->redirectToRoute('app_societes');
            } catch (\Throwable $e) {
                $logger->error('Error creating société: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de la création.');
            }
        }
        if($form->isSubmitted()){
            if(!$form->isValid()){
                foreach ($form->getErrors(true, true) as $error) {
                    $logger->error($error->getMessage(), [
                        'field' => $error->getOrigin()->getName(),
                    ]);
                }
            }
        }



        return $this->render('societes/new.html.twig', [
            'form' => $form->createView()

        ]);
    }

    /**
     * Update an existing Societe.
     *
     * @param int                    $id            Société ID.
     * @param Request                $request       HTTP request object.
     * @param SocieteRepository      $repository    Repository for Societe entity.
     * @param EntityManagerInterface $em            Doctrine entity manager.
     * @param LoggerInterface        $logger        Logger service for error/info tracking.
     *
     * @return Response
     */
    #[Route('/societes/{id}/edit', name: 'app_societe_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Societe $societe,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ): Response {
        // Permissions
        if (!$this->isGranted('ROLE_ADMIN')) {
            $logger->warning('Unauthorized attempt to edit société ID ' . $societe->getId());
            throw $this->createAccessDeniedException();
        }

        $oldLogo = $societe->getLogo();

        $form = $this->createForm(SocieteType::class, $societe);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            // Check CSRF token validity
            if (!$form->isValid()) {
                // Log CSRF errors if present
                $errors = $form->getErrors(true);
                foreach ($errors as $error) {
                    if ($error->getCause() instanceof \Symfony\Component\Security\Csrf\Exception\TokenNotFoundException) {
                        $logger->error('CSRF token expired or invalid for société edit ID ' . $societe->getId());
                        $this->addFlash('error', 'Le jeton de sécurité a expiré. Veuillez réessayer.');
                        return $this->redirectToRoute('app_societe_edit', ['id' => $societe->getId()]);
                    }
                }

                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
                return $this->render('societes/edit.html.twig', [
                    'form' => $form->createView(),
                    'societe' => $societe,
                ]);
            }

            /** @var UploadedFile|null $logoFile */
            $logoFile = $form->get('logo')->getData();

            if ($logoFile) {
                try {
                    // Delete old logo if exists and is different from default
                    if ($oldLogo && file_exists($this->getParameter('kernel.project_dir') . '/public/' . $oldLogo)) {
                        @unlink($this->getParameter('kernel.project_dir') . '/public/' . $oldLogo);
                    }

                    $newFilename = uniqid() . '.' . $logoFile->guessExtension();
                    $logoFile->move($this->getParameter('logos_directory'), $newFilename);
                    $societe->setLogo('uploads/logos/' . $newFilename);
                } catch (\Exception $e) {
                    $logger->error('Failed to upload logo for société ID ' . $societe->getId() . ': ' . $e->getMessage());
                    $this->addFlash('error', 'Erreur lors du téléchargement du logo.');
                    return $this->render('societes/edit.html.twig', [
                        'form' => $form->createView(),
                        'societe' => $societe,
                    ]);
                }
            } else {
                // Keep existing logo
                $societe->setLogo($oldLogo);
            }
            


            try {
                $em->persist($societe);
                $em->flush();
                $logger->info('Société updated successfully: ID ' . $societe->getId());
                $this->addFlash('success', 'Société mise à jour avec succès !');
                return $this->redirectToRoute('app_societes');
            } catch (\Exception $e) {
                $logger->error('Failed to update société ID ' . $societe->getId() . ': ' . $e->getMessage());
                $this->addFlash('error', 'Erreur lors de la mise à jour de la société.');
            }
        }

        return $this->render('societes/edit.html.twig', [
            'form' => $form->createView(),
            'societe' => $societe,
        ]);
    }
}
