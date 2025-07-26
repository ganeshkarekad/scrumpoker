<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Find user by userLoginId
     */
    public function findByUserLoginId(string $userLoginId): ?User
    {
        return $this->findOneBy(['userLoginId' => $userLoginId]);
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }

    /**
     * Find users by partial username match
     */
    public function findByUsernamePartial(string $partialUsername): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.username LIKE :username')
            ->setParameter('username', '%' . $partialUsername . '%')
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recently created users
     */
    public function findRecentUsers(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total users
     */
    public function countUsers(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find users who are admins of any room
     */
    public function findUsersWithAdminRoles(): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.roomAdmins', 'ra')
            ->distinct()
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users who have voted in any room
     */
    public function findUsersWithVotes(): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.userVotes', 'uv')
            ->distinct()
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
