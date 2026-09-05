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
     * For the /schedule page: logged-out visitors only see concerts, rehearsals/other are reserved to logged-in accounts, and board meetings stay hidden unless the viewer is an admin.
     *
     * @return Event[]
     */
    public function findVisibleOrderedByDate(bool $includeInternalTypes, bool $includeBoardMeetings = false): array
    {
        $qb = $this->createQueryBuilder('e')->orderBy('e.date', 'ASC');

        if (!$includeBoardMeetings) {
            $qb->andWhere('e.type != :hidden')->setParameter('hidden', 'board_meeting');
        }

        if (!$includeInternalTypes) {
            $qb->andWhere('e.type = :type')->setParameter('type', 'concert');
        }

        return $qb->getQuery()->getResult();
    }
}
