<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\Move;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Move>
 */
class MoveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Move::class);
    }

    /**
     * Find moves for a specific game
     */
    public function findByGame(Game $game): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.game = :game')
            ->setParameter('game', $game)
            ->orderBy('m.turnIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find moves by a specific user in a game
     */
    public function findByGameAndUser(Game $game, User $user): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.game = :game')
            ->andWhere('m.attackerUser = :user')
            ->setParameter('game', $game)
            ->setParameter('user', $user)
            ->orderBy('m.turnIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a move already exists at given coordinates
     */
    public function moveExistsAt(Game $game, int $x, int $y): bool
    {
        $result = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.game = :game')
            ->andWhere('m.x = :x')
            ->andWhere('m.y = :y')
            ->setParameter('game', $game)
            ->setParameter('x', $x)
            ->setParameter('y', $y)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * Get the last move in a game
     */
    public function findLastMoveInGame(Game $game): ?Move
    {
        return $this->createQueryBuilder('m')
            ->where('m.game = :game')
            ->setParameter('game', $game)
            ->orderBy('m.turnIndex', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
