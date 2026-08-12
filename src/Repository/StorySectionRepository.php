<?php

namespace App\Repository;

use App\Entity\StorySection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StorySection|null find($id, $lockMode = null, $lockVersion = null)
 * @method StorySection|null findOneBy(array $criteria, array $orderBy = null)
 * @method StorySection[]    findAll()
 * @method StorySection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StorySectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StorySection::class);
    }

    /**
     * @return StorySection[]
     */
    public function findAllOrderedByPosition(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findMaxPosition(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('MAX(s.position)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function isSlugTaken(string $slug, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.slug = :slug')
            ->setParameter('slug', $slug)
        ;

        if (null !== $excludeId) {
            $qb->andWhere('s.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
