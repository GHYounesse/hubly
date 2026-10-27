<?php
namespace App\Controller\Monitoring;

use App\Entity\Historique;
use App\Form\SearchType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
/**
 * Class HistoriqueController
 * @package App\Controller\Monitoring
 * Controller managing the display of Historique records.
 *
 * - Lists all historical records ordered by modification date descending.
 * - Supports a search form; submitted searches return only the table fragment.
 */
class HistoriqueController extends AbstractController
{
    /**
     * List all modification histories (`Historique`) and optionally search by entity modified.
     *
     * - Uses EntityManagerInterface to fetch `Historique` entities.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/historique', name: 'app_historique_index')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);

        // Liste des historiques par défaut
        $historiques = $em->getRepository(Historique::class)->findBy([], ['date_modification' => 'DESC']);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            if (!empty($data['search'])) {
                $historiques = $em->getRepository(Historique::class)
                    ->createQueryBuilder('ht')
                    ->where('LOWER(ht.entiteModifiee) LIKE LOWER(:search)')
                    ->setParameter('search', '%' . $data['search'] . '%')
                    ->orderBy('ht.date_modification', 'DESC')
                    ->getQuery()
                    ->getResult();
            }

            // Rendu du template pour les résultats de recherche AJAX
            return $this->render('historique/_search_result.html.twig', [
                'form' => $form->createView(),
                'historiques' => $historiques,
            ]);
        }

        // Rendu de la page complète
        return $this->render('historique/index.html.twig', [
            'form' => $form->createView(),
            'historiques' => $historiques,
        ]);
    }
}
