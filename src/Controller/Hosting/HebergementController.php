<?php

namespace App\Controller\Hosting;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Hebergement;
use App\Form\SearchType;
use App\Form\HebergementType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\HebergementRepository;
use App\Service\Monitoring\HebergementExpirationService;
/**
 * Controller managing CRUD operations and views for Hebergement entities.
 *
 * - Uses EntityManagerInterface (initialized in the constructor) to manage Hebergement entities.
 * - Supports search, create, update, delete, and show operations.
 */
#[Route('/hebergement')]
final class HebergementController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    #private HebergementExpirationService $hebergementExpirationService;


    // public function __construct(EntityManagerInterface $entityManager, HebergementExpirationService $hebergementExpirationService)
    // {
    //     $this->entityManager = $entityManager;
    //     $this->hebergementExpirationService = $hebergementExpirationService;
    // }

    /**
     * Constructor for HebergementController.
     *
     * @param EntityManagerInterface $entityManager Used to persist, update, and remove Hebergement entities.
     * @param HebergementExpirationService $hebergementExpirationService Service to handle expiration checks and notifications (not directly used in shown methods but available for future use).
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        #$this->hebergementExpirationService = $hebergementExpirationService;
    }

    
    /**
     * List all hosting services (`Hebergement`) and optionally filter them.
     *
     * - Uses EntityManagerInterface to fetch `Hebergement` entities.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    
    #[Route('/', name: 'app_hebergement_index')]
    public function index_hebergement(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);

        // Get filter parameters
        $searchTerm = $request->query->get('search', '');
        $typeFilter = $request->query->get('type', '');
        $fournisseurFilter = $request->query->get('fournisseur', '');
        $estActifFilter = $request->query->get('est_actif', '');
        $expirationFilter = $request->query->get('expiration', '');

        // Build query with filters
        $queryBuilder = $em->getRepository(Hebergement::class)
            ->createQueryBuilder('h')
            ->orderBy('h.dateExpiration', 'ASC');

        // Always show only active hebergements by default, unless filter says otherwise
        if ($estActifFilter === '') {
            $queryBuilder->where('h.estActif = :actif')
                ->setParameter('actif', true);
        } elseif ($estActifFilter !== 'all') {
            $queryBuilder->where('h.estActif = :actif')
                ->setParameter('actif', $estActifFilter === 'true');
        }

        // Apply search term filter
        if (!empty($searchTerm)) {
            $queryBuilder->andWhere('LOWER(h.fournisseur) LIKE LOWER(:search) OR LOWER(h.site) LIKE LOWER(:search) OR LOWER(h.url) LIKE LOWER(:search)')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        // Apply type filter
        if (!empty($typeFilter)) {
            $queryBuilder->andWhere('h.type = :type')
                ->setParameter('type', $typeFilter);
        }

        // Apply fournisseur filter
        if (!empty($fournisseurFilter)) {
            $queryBuilder->andWhere('h.fournisseur = :fournisseur')
                ->setParameter('fournisseur', $fournisseurFilter);
        }

        // Apply expiration date filter
        if (!empty($expirationFilter)) {
            $today = new \DateTime();
            
            switch ($expirationFilter) {
                case 'expired':
                    // Already expired
                    $queryBuilder->andWhere('h.dateExpiration < :today')
                        ->setParameter('today', $today);
                    break;
                case '30days':
                    // Expires in next 30 days
                    $futureDate = (clone $today)->modify('+30 days');
                    $queryBuilder->andWhere('h.dateExpiration BETWEEN :today AND :futureDate')
                        ->setParameter('today', $today)
                        ->setParameter('futureDate', $futureDate);
                    break;
                case '60days':
                    // Expires in next 60 days
                    $futureDate = (clone $today)->modify('+60 days');
                    $queryBuilder->andWhere('h.dateExpiration BETWEEN :today AND :futureDate')
                        ->setParameter('today', $today)
                        ->setParameter('futureDate', $futureDate);
                    break;
                case '90days':
                    // Expires in next 90 days
                    $futureDate = (clone $today)->modify('+90 days');
                    $queryBuilder->andWhere('h.dateExpiration BETWEEN :today AND :futureDate')
                        ->setParameter('today', $today)
                        ->setParameter('futureDate', $futureDate);
                    break;
            }
        }

        $hebergements = $queryBuilder->getQuery()->getResult();
        $totalHebergements = count($hebergements);

        // Get unique types and fournisseurs for filter dropdowns
        $queryBuilderType = $em->getRepository(Hebergement::class)
            ->createQueryBuilder('h')
            ->select('DISTINCT h.type')
            ->where('h.type IS NOT NULL');
        $queryBuilderFournisseur = $em->getRepository(Hebergement::class)
            ->createQueryBuilder('h')
            ->select('DISTINCT h.fournisseur')
            ->where('h.fournisseur IS NOT NULL');
        $estActif=true;
        if($estActifFilter==='false'){
            $estActif=false;
            $queryBuilderType->andWhere('h.estActif = :estActif')
            ->setParameter('estActif', $estActif);
            $queryBuilderFournisseur->andWhere('h.estActif = :estActif')
            ->setParameter('estActif', $estActif);
        }elseif($estActifFilter==='true'){
            $queryBuilderType->andWhere('h.estActif = :estActif')
            ->setParameter('estActif', $estActif);
            $queryBuilderFournisseur->andWhere('h.estActif = :estActif')
            ->setParameter('estActif', $estActif);
        }
        
        
        $allTypes=$queryBuilderType    
            ->orderBy('h.type', 'ASC')
            ->getQuery()
            ->getResult();

        $allFournisseurs = $queryBuilderFournisseur
            ->orderBy('h.fournisseur', 'ASC')
            ->getQuery()
            ->getResult();

        // Handle form submission (for search bar)
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (!empty($data['search'])) {
                return $this->redirectToRoute('app_hebergement_index', [
                    'search' => $data['search']
                ]);
            }
        }

        return $this->render('hebergement/index.html.twig', [
            'form' => $form->createView(),
            'hebergements' => $hebergements,
            'total_hebergements' => $totalHebergements,
            'all_types' => $allTypes,
            'all_fournisseurs' => $allFournisseurs,
        ]);
    }
    /**
     * Creates a new hébergement.
     *
     * - Uses the constructor-initialized `$entityManager` to persist the new Hebergement entity.
     * - Automatically sets created and updated timestamps.
     *
     * @param Request $request The current HTTP request.
     *
     * @return Response The rendered creation form or redirect to the index after successful creation.
     */
    #[Route('/new', name: 'app_hebergement_new', methods: ["GET","POST"])]
    public function new(Request $request): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $hebergement = new Hebergement();
        $hebergement->setEstActif(true);
        $form = $this->createForm(HebergementType::class, $hebergement);
        $form->remove('created');
        $form->remove('updated');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $hebergement->setCreated(new \DateTime());
            $hebergement->setUpdated(new \DateTime());
            $this->entityManager->persist($hebergement);
            $this->entityManager->flush();
            $this->addFlash('success', 'Hébergement créé avec succès.');
            return $this->redirectToRoute('app_hebergement_index');
        }

        return $this->render('hebergement/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    /**
     * Updates an existing hébergement.
     *
     * - Uses the constructor-initialized `$entityManager` to fetch and persist changes to Hebergement entities.
     *
     * @param Request $request The current HTTP request.
     * @param int $id The ID of the Hebergement to update.
     * @param EntityManagerInterface $entityManager Method-injected entity manager (optional, `$this->entityManager` could be used instead).
     *
     * @return Response The rendered update form or redirect after successful update.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If no Hebergement exists with the given ID.
     */
    #[Route('/update/{id}', name: 'app_hebergement_update', methods: ["GET","POST"])]
    public function update(
        Request $request,
        int $id,
        EntityManagerInterface $entityManager
     ): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $hebergement = $this->entityManager->getRepository(Hebergement::class)->find($id);
        if (!$hebergement) {
            throw $this->createNotFoundException('Hébergement non trouvé avec l\'id ' . $id);
        }

        $form = $this->createForm(HebergementType::class, $hebergement);
        $form->remove('created');
        $form->remove('updated');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hebergement->setUpdated(new \DateTime());

            $this->entityManager->flush();
            $this->addFlash('success', 'Hébergement mis à jour avec succès.');
            return $this->redirectToRoute('app_hebergement_index');
        }

        return $this->render('hebergement/update.html.twig', [
            'form' => $form->createView(),
            'hebergement' => $hebergement,
        ]);
    }
    /**
     * Deletes a Hebergement and all related notifications.
     *
     * - Uses the method-injected `$doctrine` to get an entity manager.
     * - Removes notifications linked to the Hebergement before deletion.
     *
     * @param int $id The ID of the Hebergement to delete.
     * @param ManagerRegistry $doctrine Doctrine registry to access the entity manager.
     *
     * @return Response Redirects to the hébergement index page.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If no Hebergement exists with the given ID.
     */
    #[Route('/delete/{id}', name: 'app_hebergement_delete', methods: ['GET', 'POST'])]
    public function delete(int $id, ManagerRegistry $doctrine): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $entityManager = $doctrine->getManager();
        $hebergement = $entityManager->getRepository(Hebergement::class)->find($id);
    
        if (!$hebergement) {
            throw $this->createNotFoundException('Aucun hebergement trouvé avec l\'id ' . $id);
        }
    
        // Supprimer les notifications liées au projet
        foreach ($hebergement->getNotifications() as $notification) {
            $entityManager->remove($notification);
        }
    
        // Supprimer le projet après avoir supprimé les dépendances
        $entityManager->remove($hebergement);
        $entityManager->flush();
    
        return $this->redirectToRoute('app_hebergement_index');
        
    } 
    /**
     * Shows details of a single Hebergement.
     *
     * - Uses the constructor-initialized `$entityManager` to fetch the Hebergement entity.
     *
     * @param int $id The ID of the Hebergement to display.
     *
     * @return Response The rendered template showing Hebergement details.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If no Hebergement exists with the given ID.
     */
    #[Route('/show/{id}', name: 'app_hebergement_show', methods: ["GET"])]
    public function show(int $id): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }
        
        $hebergement = $this->entityManager->getRepository(Hebergement::class)->find($id);
        if (!$hebergement) {
            throw $this->createNotFoundException('Aucun hébergement trouvé avec l\'id ' . $id);
        }

        return $this->render('hebergement/show.html.twig', [
            'hebergement' => $hebergement,
        ]);
    }
}
