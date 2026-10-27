<?php
namespace App\Controller\Search;

use App\Entity\Projet;
use App\Entity\Societe;
use App\Form\SearchType;
use App\Entity\Hebergement;
use App\Entity\Technologie;
use App\Entity\Notification;
use App\Service\Monitoring\HebergementExpirationService;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
/**
 * Class SearchController
 * 
 * @package App\Controller\Search
 * 
 * Controller responsible for search functionality across the application.
 *
 * Provides search and list views for:
 *  - Projets
 *  - Sociétés' projets
 *  - Développeurs
 *  - Hébergements
 *  - Technologies
 *  - Historiques
 *  - Notifications
 *
 * Services used:
 *  - EntityManagerInterface for entity queries and persistence
 *  - HebergementExpirationService for expiration-related notifications
 *  - LoggerInterface for logging errors, warnings, and info messages
 */
class SearchController extends AbstractController
{   
    /**
     * List all projects (`Projet`) and optionally search by name.
     *
     * - Uses EntityManagerInterface to fetch `Projet` entities.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/projets', name: 'app_projets_index')]
    public function index_projet(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);

        // Liste des projets par défaut
        $projets = $em->getRepository(Projet::class)->findBy([], ['status' => 'ASC']);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (!empty($data['search'])) {
                // Recherche des projets par nom
                $projets = $em->getRepository(Projet::class)
                ->createQueryBuilder('p')
                ->where('LOWER(p.nom) LIKE LOWER(:search)')
                ->setParameter('search', '%' . $data['search'] . '%')
                ->orderBy('p.status', 'ASC')
                ->getQuery()
                ->getResult();

            }
    
            return $this->render('projets/_search_result.html.twig',[
                'form' => $form->createView(),
                'projets' => $projets,
            ]);
        }

        return $this->render('projets/index.html.twig', [
            'form' => $form->createView(),  // Assurez-vous de passer cette variable
            'projets' => $projets,
        ]);
    }

    /**
     * Search projects belonging to a specific Société.
     *
     * This method handles both full-page and AJAX requests.
     * It filters projects (`Projet`) by the given Société ID and optional search term.
     *
     * @param int                   $id       The ID of the Société.
     * @param Request               $request  The HTTP request instance.
     * @param EntityManagerInterface $em       The Doctrine entity manager.
     * @param LoggerInterface       $logger   The logger service.
     *
     * @return Response
     */
    #[Route('/societes/{id}/projets/search', name: 'app_societe_projets_search', requirements: ['id' => '\d+'])]
    public function searchProjetsBySociete(int $id, Request $request, EntityManagerInterface $em, LoggerInterface $logger): Response {
        try {
            if (!$this->isGranted('ROLE_USER')) {
                $logger->warning('Unauthorized access attempt to search projets.', [
                    'societe_id' => $id,
                    'user' => $this->getUser()?->getUserIdentifier(),
                ]);

                return $this->redirectToRoute('app_login');
            }

            $societe = $em->getRepository(Societe::class)->find($id);
            if (!$societe) {
                $logger->error('Société introuvable.', ['societe_id' => $id]);
                throw $this->createNotFoundException(sprintf('Société avec l\'ID %d introuvable.', $id));
            }

            $form = $this->createForm(SearchType::class);
            $form->handleRequest($request);

            $projets = $em->getRepository(Projet::class)
                ->findBy(['societe' => $societe], ['status' => 'ASC']);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                if (!empty($data['search'])) {
                    $logger->info('Filtering projets by search term.', [
                        'societe_id' => $id,
                        'search' => $data['search'],
                    ]);

                    $projets = $em->getRepository(Projet::class)
                        ->createQueryBuilder('p')
                        ->where('LOWER(p.nom) LIKE LOWER(:search)')
                        ->andWhere('p.societe = :societe')
                        ->setParameter('search', '%' . $data['search'] . '%')
                        ->setParameter('societe', $societe)
                        ->orderBy('p.status', 'ASC')
                        ->getQuery()
                        ->getResult();
                }

                if ($request->isXmlHttpRequest()) {
                    return $this->render('projets/_search_result.html.twig', [
                        'projets' => $projets,
                    ]);
                }
            }

            return $this->render('projets/index.html.twig', [
                'form' => $form->createView(),
                'projets' => $projets,
                'societe_id' => $societe->getId(),
            ]);
        } catch (\Exception $e) {
            $logger->critical('Erreur lors de la recherche des projets.', [
                'societe_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('danger', 'Une erreur est survenue lors de la recherche des projets.');

            return $this->redirectToRoute('app_societes');
        }
    }
}