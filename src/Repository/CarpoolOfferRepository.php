<?php

namespace App\Repository;

use App\Entity\CarpoolOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CarpoolOffer|null find($id, $lockMode = null, $lockVersion = null)
 * @method CarpoolOffer|null findOneBy(array $criteria, array $orderBy = null)
 * @method CarpoolOffer[]    findAll()
 * @method CarpoolOffer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
// Queries for CarpoolOffer.
class CarpoolOfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CarpoolOffer::class);
    }

    /**
     * Offers for events that haven't happened yet, ordered by event date then departure location: past events no longer need a carpool.
     *
     * @return CarpoolOffer[]
     */
    public function findUpcomingOrderedByEventDate(): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.event', 'e')
            ->andWhere('e.date >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.date', 'ASC')
            ->addOrderBy('c.departureLocation', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
