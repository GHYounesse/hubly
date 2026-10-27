<?php

namespace App\Repository;

use App\Entity\Projet;
use App\Entity\UptimeCheck;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UptimeCheck>
 */
class UptimeCheckRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UptimeCheck::class);
    }

    /**
     * The latest checks of a project, newest first.
     *
     * @return UptimeCheck[]
     */
    public function findRecentForProjet(Projet $projet, int $limit = 30): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.projet = :projet')
            ->setParameter('projet', $projet)
            ->orderBy('c.checkedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Share of successful checks over the last $days days, from 0 to 100, or null when there is no data.
     */
    public function uptimePercentage(Projet $projet, int $days = 30): ?float
    {
        $row = $this->createQueryBuilder('c')
            ->select('COUNT(c.id) AS total', 'SUM(CASE WHEN c.success = true THEN 1 ELSE 0 END) AS ok')
            ->andWhere('c.projet = :projet')
            ->andWhere('c.checkedAt >= :since')
            ->setParameter('projet', $projet)
            ->setParameter('since', new \DateTimeImmutable(sprintf('-%d days', $days)), Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getSingleResult();

        $total = (int) $row['total'];
        if ($total === 0) {
            return null;
        }

        return round(((int) $row['ok'] / $total) * 100, 1);
    }

    /**
     * Housekeeping: delete checks older than $days days. Returns the number of deleted rows.
     */
    public function purgeOlderThan(int $days): int
    {
        return (int) $this->createQueryBuilder('c')
            ->delete()
            ->andWhere('c.checkedAt < :limit')
            ->setParameter('limit', new \DateTimeImmutable(sprintf('-%d days', $days)), Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->execute();
    }
}
