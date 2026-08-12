<?php

namespace App\Repository;

use App\Entity\LedgerEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LedgerEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method LedgerEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method LedgerEntry[]    findAll()
 * @method LedgerEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LedgerEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LedgerEntry::class);
    }

    /**
     * @return LedgerEntry[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.date', 'DESC')
            ->addOrderBy('l.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getBalance(): float
    {
        $entries = $this->findAll();

        $balance = 0.0;
        foreach ($entries as $entry) {
            $balance += $entry->getSignedAmount();
        }

        return round($balance, 2);
    }
}
