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
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * Documents actifs (hors corbeille) d'un dossier, triés selon $sort/$dir
     * (cf. DeskController::files()). "size" n'a de sens que sur les
     * documents (pas de calcul récursif sur les dossiers), d'où ce tri géré
     * ici plutôt que dans le contrôleur.
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
     * Documents en corbeille d'un espace, les plus récemment supprimés
     * d'abord : DeskController::trash().
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
     * Recherche récursive par nom sur tout l'espace, cf.
     * FolderRepository::search() pour l'échappement du terme.
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
