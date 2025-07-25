<?php

namespace App\Repository;

use App\Entity\Room;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Room>
 */
class RoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }

    /**
     * Find room by room key
     */
    public function findByRoomKey(string $roomKey): ?Room
    {
        return $this->findOneBy(['roomKey' => $roomKey]);
    }

    /**
     * Find rooms created by a specific user
     */
    public function findByCreator(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find rooms where user is a member
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.users', 'u')
            ->where('u = :user')
            ->setParameter('user', $user)
            ->orderBy('r.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find rooms where user is an admin
     */
    public function findByAdmin(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.roomAdmins', 'ra')
            ->where('ra.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recently active rooms
     */
    public function findRecentlyActive(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find rooms with vote activity
     */
    public function findRoomsWithVotes(): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.userVotes', 'uv')
            ->distinct()
            ->orderBy('r.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total rooms
     */
    public function countRooms(): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find rooms created in the last N days
     */
    public function findRecentRooms(int $days = 7): array
    {
        $date = new \DateTimeImmutable("-{$days} days");
        
        return $this->createQueryBuilder('r')
            ->where('r.createdAt >= :date')
            ->setParameter('date', $date)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find rooms with their vote counts
     */
    public function findRoomsWithVoteCounts(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r', 'COUNT(uv.id) as voteCount')
            ->leftJoin('r.userVotes', 'uv')
            ->groupBy('r.id')
            ->orderBy('voteCount', 'DESC')
            ->addOrderBy('r.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
