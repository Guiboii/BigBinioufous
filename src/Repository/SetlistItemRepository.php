<?php

namespace App\Repository;

use App\Entity\SetlistItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SetlistItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method SetlistItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method SetlistItem[]    findAll()
 * @method SetlistItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SetlistItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SetlistItem::class);
    }

    /**
     * @return SetlistItem[]
     */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['position' => 'ASC']);
    }

    /**
     * Prochaine position libre pour un nouvel item, mis en fin de setlist.
     */
    public function nextPosition(): int
    {
        $max = $this->createQueryBuilder('s')
            ->select('MAX(s.position)')
            ->getQuery()
            ->getSingleScalarResult();

        return null === $max ? 0 : $max + 1;
    }
}
