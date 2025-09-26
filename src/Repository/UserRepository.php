<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Find users by username or email
     */
    public function findByUsernameOrEmail(string $usernameOrEmail): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $usernameOrEmail)
            ->setParameter('email', $usernameOrEmail)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get leaderboard users ordered by points, win rate, and last activity
     */
    public function findLeaderboard(int $limit = 100): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isBanned = false')
            ->orderBy('u.points', 'DESC')
            ->addOrderBy('u.wins', 'DESC')
            ->addOrderBy('u.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users with open games
     */
    public function findUsersWithOpenGames(): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.gamesAsPlayer1', 'g1', 'WITH', 'g1.status = :status')
            ->leftJoin('u.gamesAsPlayer2', 'g2', 'WITH', 'g2.status = :status')
            ->where('u.isBanned = false')
            ->setParameter('status', 'OPEN')
            ->getQuery()
            ->getResult();
    }
}