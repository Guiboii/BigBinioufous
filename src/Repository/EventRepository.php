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
     * Pour /schedule (public) : hors connexion, seuls les concerts sont
     * publics, répétitions/autres réservés aux comptes connectés (n'importe
     * quel rôle). Les admins gardent une vue complète via
     * findAllOrderedByDate() sur /admin/event, indépendante de ce filtre.
     *
     * @return Event[]
     */
    public function findVisibleOrderedByDate(bool $includeAllTypes): array
    {
        $qb = $this->createQueryBuilder('e')->orderBy('e.date', 'ASC');

        if (!$includeAllTypes) {
            $qb->andWhere('e.type = :type')->setParameter('type', 'concert');
        }

        return $qb->getQuery()->getResult();
    }
}
