<?php

namespace App\Repository;

use App\Entity\Hebergement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hebergement>
 */
class HebergementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hebergement::class);
    }

    /**
     * Hosting plans that expire within the next $days days (or already expired), soonest first.
     *
     * @return Hebergement[]
     */
    public function findExpiringSoon(int $days = 30, int $limit = 5): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.dateExpiration IS NOT NULL')
            ->andWhere('h.dateExpiration <= :limitDate')
            ->setParameter('limitDate', new \DateTimeImmutable(sprintf('+%d days', $days)), \Doctrine\DBAL\Types\Types::DATE_IMMUTABLE)
            ->orderBy('h.dateExpiration', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Hebergement[] Returns an array of Hebergement objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('h.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Hebergement
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
