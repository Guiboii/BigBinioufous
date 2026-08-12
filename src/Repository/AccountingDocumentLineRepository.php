<?php

namespace App\Repository;

use App\Entity\AccountingDocumentLine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AccountingDocumentLine|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccountingDocumentLine|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccountingDocumentLine[]    findAll()
 * @method AccountingDocumentLine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountingDocumentLineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountingDocumentLine::class);
    }
}
