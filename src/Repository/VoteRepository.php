<?php

namespace App\Repository;

use App\Entity\Vote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vote>
 */
class VoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vote::class);
    }

    /**
     * Find vote by label
     */
    public function findByLabel(string $label): ?Vote
    {
        return $this->findOneBy(['label' => $label]);
    }

    /**
     * Get all votes ordered by their typical Scrum Poker sequence
     */
    public function findAllOrdered(): array
    {
        $customOrder = ['0', '1', '2', '3', '5', '8', '13', '21', '?', '∞', 'BRB'];
        
        $qb = $this->createQueryBuilder('v');
        
        // Create CASE statement for custom ordering
        $orderCase = 'CASE v.label';
        foreach ($customOrder as $index => $label) {
            $orderCase .= " WHEN :label{$index} THEN {$index}";
            $qb->setParameter("label{$index}", $label);
        }
        $orderCase .= ' ELSE 999 END';
        
        return $qb
            ->orderBy($orderCase)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get standard Fibonacci sequence votes (excluding special votes)
     */
    public function findFibonacciVotes(): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.label IN (:labels)')
            ->setParameter('labels', ['0', '1', '2', '3', '5', '8', '13', '21'])
            ->orderBy('CAST(v.label AS UNSIGNED)', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get special votes (non-numeric)
     */
    public function findSpecialVotes(): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.label IN (:labels)')
            ->setParameter('labels', ['?', '∞', 'BRB'])
            ->getQuery()
            ->getResult();
    }
}
