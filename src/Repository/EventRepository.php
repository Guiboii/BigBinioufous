<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
// Queries for Event.
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @return Event[]
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * For the public /schedule page: logged-out visitors only see concerts, rehearsals/other are reserved to logged-in accounts. Board meetings are internal and never listed here for anyone. Admins keep a full view via findAllOrderedByDate() elsewhere, independent of this filter.
     *
     * @return Event[]
     */
    public function findVisibleOrderedByDate(bool $includeAllTypes): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.type != :hidden')
            ->setParameter('hidden', 'board_meeting')
            ->orderBy('e.date', 'ASC');

        if (!$includeAllTypes) {
            $qb->andWhere('e.type = :type')->setParameter('type', 'concert');
        }

        return $qb->getQuery()->getResult();
    }
}
