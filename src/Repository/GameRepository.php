<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * Find open games waiting for a second player
     */
    public function findOpenGames(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.status = :status')
            ->andWhere('g.player2 IS NULL')
            ->setParameter('status', Game::STATUS_OPEN)
            ->orderBy('g.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find games for a specific user
     */
    public function findGamesForUser(User $user): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.player1 = :user OR g.player2 = :user')
            ->setParameter('user', $user)
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active games for a specific user
     */
    public function findActiveGamesForUser(User $user): array
    {
        return $this->createQueryBuilder('g')
            ->where('(g.player1 = :user OR g.player2 = :user)')
            ->andWhere('g.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', [Game::STATUS_PLACEMENT, Game::STATUS_IN_PROGRESS])
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find games that need turn timeout checking
     */
    public function findGamesForTimeoutCheck(\DateTimeInterface $timeoutThreshold): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.status = :status')
            ->andWhere('g.updatedAt < :threshold')
            ->setParameter('status', Game::STATUS_IN_PROGRESS)
            ->setParameter('threshold', $timeoutThreshold)
            ->getQuery()
            ->getResult();
    }
}
