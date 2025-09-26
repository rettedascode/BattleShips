<?php

namespace App\Repository;

use App\Entity\RankingSnapshot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RankingSnapshot>
 */
class RankingSnapshotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RankingSnapshot::class);
    }

    /**
     * Find latest ranking snapshots
     */
    public function findLatestRankings(int $limit = 100): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.points', 'DESC')
            ->addOrderBy('r.wins', 'DESC')
            ->addOrderBy('r.timestamp', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find ranking snapshots for a specific user
     */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.timestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find ranking snapshots from a specific date
     */
    public function findFromDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.timestamp >= :date')
            ->setParameter('date', $date)
            ->orderBy('r.points', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
