<?php

namespace App\Repository;

use App\Entity\Document;
use App\Entity\Folder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Document|null find($id, $lockMode = null, $lockVersion = null)
 * @method Document|null findOneBy(array $criteria, array $orderBy = null)
 * @method Document[]    findAll()
 * @method Document[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
// Queries for Document: folder listing, trash, search.
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * Active (non-trashed) documents of a folder, sorted per $sort/$dir. "size" only makes sense on documents, not folders, hence the sort logic living here rather than in the controller.
     *
     * @return Document[]
     */
    public function findActiveByFolder(Folder $folder, string $sort = 'name', string $dir = 'asc'): array
    {
        $column = \in_array($sort, ['date', 'size'], true) ? ('date' === $sort ? 'createdAt' : 'size') : 'name';
        $direction = 'desc' === $dir ? 'DESC' : 'ASC';

        return $this->findBy(['folder' => $folder, 'deletedAt' => null], [$column => $direction]);
    }

    /**
     * Trashed documents of a space, most recently deleted first.
     *
     * @return Document[]
     */
    public function findTrashed(string $space): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.folder', 'f')
            ->andWhere('f.space = :space')
            ->andWhere('d.deletedAt IS NOT NULL')
            ->setParameter('space', $space)
            ->orderBy('d.deletedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recursive search by name across the whole space. See FolderRepository::search() for the LIKE-escaping logic.
     *
     * @return Document[]
     */
    public function search(string $space, string $query): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.folder', 'f')
            ->andWhere('f.space = :space')
            ->andWhere('d.deletedAt IS NULL')
            ->andWhere('LOWER(d.name) LIKE LOWER(:query)')
            ->setParameter('space', $space)
            ->setParameter('query', '%'.addcslashes($query, '%_').'%')
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
