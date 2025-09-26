<?php

namespace App\Repository;

use App\Entity\Board;
use App\Entity\Game;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Board>
 */
class BoardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Board::class);
    }

    /**
     * Find board for a specific game and user
     */
    public function findByGameAndUser(Game $game, User $user): ?Board
    {
        return $this->createQueryBuilder('b')
            ->where('b.game = :game')
            ->andWhere('b.user = :user')
            ->setParameter('game', $game)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find boards for a specific game
     */
    public function findByGame(Game $game): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.game = :game')
            ->setParameter('game', $game)
            ->getQuery()
            ->getResult();
    }
}
