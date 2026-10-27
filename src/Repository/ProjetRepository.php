<?php

namespace App\Repository;

use App\Entity\Projet;
use App\Entity\Technologie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Projet>
 */
class ProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Projet::class);
    }

    /**
     * Number of projects per status, e.g. ['à faire' => 4, 'en cours' => 7].
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.status AS status', 'COUNT(p.id) AS total')
            ->groupBy('p.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    public function findBySocieteDescQuery($societeId)
    {
        return $this->createQueryBuilder('p')
            ->where('p.societe = :societe')
            ->setParameter('societe', $societeId)
            ->orderBy('p.created', 'DESC')
            ->getQuery();
    }

    public function findByFiltersQuery(array $filters)
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.status', 'ASC')
            ->addOrderBy('p.created', 'DESC');

        if (isset($filters['societe'])) {
            $qb->andWhere('p.societe = :societe')
                ->setParameter('societe', $filters['societe']);
        }

        if (isset($filters['type'])) {
            $qb->andWhere('p.type = :type')
                ->setParameter('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (isset($filters['suiviPar'])) {
            $qb->andWhere('p.suiviPar = :suiviPar')
                ->setParameter('suiviPar', $filters['suiviPar']);
        }

        if (isset($filters['developpeurPrincipal'])) {
            $qb->andWhere('p.developpeurPrincipal = :developpeurPrincipal')
                ->setParameter('developpeurPrincipal', $filters['developpeurPrincipal']);
        }

        if (isset($filters['commercial'])) {
            $qb->andWhere('p.commercial = :commercial')
                ->setParameter('commercial', $filters['commercial']);
        }

        if (isset($filters['priorite'])) {
            $qb->andWhere('p.priorite = :priorite')
                ->setParameter('priorite', $filters['priorite']);
        }

        // Handle etatProjet filter
        if (isset($filters['etatProjet'])) {
            if ($filters['etatProjet'] == "projet_teste") {
                $qb->andWhere('p.projetTeste = :projetTeste')
                    ->setParameter('projetTeste', true);
            } elseif ($filters['etatProjet'] == "projet_valide") {
                $qb->andWhere('p.projetValide = :projetValide')
                    ->setParameter('projetValide', true);
            } else {
                // Only apply default filter if etatProjet is not set at all
                $qb->andWhere('(p.projetValide = :projetValide AND p.projetTeste = :projetTeste)')
                    ->setParameter('projetValide', true)
                    ->setParameter('projetTeste', true);
            }
        }

        // Handle client filter
        if (isset($filters['client'])) {
            if ($filters['client'] == "client_gratuite") {
                $qb->andWhere('p.clientGratuite = :clientGratuite')
                    ->setParameter('clientGratuite', true);
            } elseif ($filters['client'] == "client_ameliorations") {
                $qb->andWhere('p.clientAmeliorations = :clientAmeliorations')
                    ->setParameter('clientAmeliorations', true);
            } else {
                $qb->andWhere('(p.clientGratuite = :clientGratuite AND p.clientAmeliorations = :clientAmeliorations)')
                    ->setParameter('clientGratuite', true)
                    ->setParameter('clientAmeliorations', true);
            }
        }

        return $qb->getQuery();
    }
    public function countByFilters(array $filters): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)');

        // applique EXACTEMENT les mêmes filtres
        if (isset($filters['societe'])) {
            $qb->andWhere('p.societe = :societe')
            ->setParameter('societe', $filters['societe']);
        }

        // autres filtres ici...

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    public function findByTechnologieAndSearch(?string $search, Technologie $technologie)
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.technologie', 't')
            ->where('t = :technologie')
            ->setParameter('technologie', $technologie);

        if ($search) {
            $qb->andWhere('LOWER(p.nom) LIKE :search')
            ->setParameter('search', '%' . strtolower($search) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
//     * @return Projet[] Returns an array of Projet objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

    //    public function findOneBySomeField($value): ?Projet
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
