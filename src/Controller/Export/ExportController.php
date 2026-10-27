<?php

namespace App\Controller\Export;

use App\Entity\Hebergement;
use App\Entity\Projet;
use App\Repository\HebergementRepository;
use App\Repository\ProjetRepository;
use App\Security\Voter\CompanyVoter;
use App\Service\Export\CsvExporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CSV exports of the lists, for spreadsheets and reports.
 * Projects are limited to the companies the user may open (CompanyVoter).
 */
#[Route('/export')]
final class ExportController extends AbstractController
{
    public function __construct(private readonly CsvExporter $csv) {}

    #[Route('/projets.csv', name: 'app_export_projets', methods: ['GET'])]
    public function projets(ProjetRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $projets = array_filter(
            $repository->findBy([], ['nom' => 'ASC']),
            fn (Projet $projet) => $this->isGranted(CompanyVoter::ACCESS, $projet),
        );

        $rows = array_map(static fn (Projet $p) => [
            $p->getNom(),
            $p->getSociete()?->getDenomination(),
            $p->getType(),
            $p->getStatus(),
            $p->getPriorite(),
            $p->getDateCommande()?->format('d/m/Y'),
            $p->getDateLivraison()?->format('d/m/Y'),
            $p->getUrl(),
            $p->getSslExpiresAt()?->format('d/m/Y'),
            $p->isProjetTeste(),
            $p->isProjetValide(),
        ], $projets);

        return $this->download('projets', [
            'Projet', 'Société', 'Type', 'Statut', 'Priorité', 'Date de commande', 'Date de livraison',
            'URL', 'Certificat SSL jusqu\'au', 'Testé', 'Validé',
        ], $rows);
    }

    #[Route('/hebergements.csv', name: 'app_export_hebergements', methods: ['GET'])]
    public function hebergements(HebergementRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Credentials ("acces") are deliberately not exported
        $rows = array_map(static fn (Hebergement $h) => [
            $h->getSite(),
            $h->getFournisseur(),
            $h->getType(),
            $h->getUrl(),
            $h->getDateExpiration()?->format('d/m/Y'),
            $h->isEstActif(),
            $h->getPrix(),
            $h->getPeriodicite(),
            $h->getCoutAnnuel(),
        ], $repository->findBy([], ['dateExpiration' => 'ASC']));

        return $this->download('hebergements', [
            'Domaine', 'Fournisseur', 'Type', 'URL', 'Expiration', 'Actif', 'Prix', 'Facturation', 'Coût annuel',
        ], $rows);
    }

    /** @param list<list<mixed>> $rows */
    private function download(string $name, array $headers, array $rows): Response
    {
        $response = new Response($this->csv->toString($headers, $rows));
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            sprintf('hubly-%s-%s.csv', $name, date('Y-m-d')),
        ));

        return $response;
    }
}
