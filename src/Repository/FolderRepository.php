<?php

namespace App\Repository;

use App\Entity\Folder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Folder|null find($id, $lockMode = null, $lockVersion = null)
 * @method Folder|null findOneBy(array $criteria, array $orderBy = null)
 * @method Folder[]    findAll()
 * @method Folder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
// Queries and tree helpers for Folder: root lookup/creation, trash, search, cycle detection.
class FolderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Folder::class);
    }

    // Each space has a dedicated root folder, lazily created on first access rather than seeded in fixtures.
    public function findOrCreateRoot(string $space, EntityManagerInterface $manager): Folder
    {
        $root = $this->findOneBy(['space' => $space, 'parent' => null]);
        if ($root) {
            return $root;
        }

        $labels = [
            Folder::SPACE_MUSIC => 'Musique',
            Folder::SPACE_ADMIN => 'Administratif',
            Folder::SPACE_ACCOUNTING => 'Comptabilité',
            Folder::SPACE_OTHER => 'Autre',
        ];

        $root = new Folder();
        $root->setName($labels[$space] ?? $space)->setSpace($space);
        $manager->persist($root);
        $manager->flush();

        return $root;
    }

    // Resolves a slash-separated path (e.g. "Medley/Oboe") into nested folders under the space's root, creating what's missing, and returns the deepest one. Ignores trashed folders when matching by name, otherwise a new upload would silently resurrect a deleted folder.
    public function findOrCreateByPath(string $space, string $path, EntityManagerInterface $manager): Folder
    {
        $parent = $this->findOrCreateRoot($space, $manager);

        $segments = array_filter(explode('/', $path), static fn (string $s) => '' !== trim($s));

        foreach ($segments as $name) {
            $name = trim($name);
            $existing = $this->findOneBy(['parent' => $parent, 'name' => $name, 'deletedAt' => null]);
            if ($existing) {
                $parent = $existing;
                continue;
            }

            $folder = new Folder();
            $folder->setName($name)->setParent($parent)->setSpace($space);
            $manager->persist($folder);
            $manager->flush();
            $parent = $folder;
        }

        return $parent;
    }

    /**
     * Active top-level folders (direct children of the root) of a space, sorted by name. Read-only: unlike findOrCreateRoot(), it does not create the root if missing, to avoid a side effect on a GET request.
     *
     * @return Folder[]
     */
    public function findTopLevel(string $space): array
    {
        $root = $this->findOneBy(['space' => $space, 'parent' => null]);
        if (!$root) {
            return [];
        }

        return $this->findActiveChildren($root);
    }

    // True when $candidate is $ancestor itself or one of its descendants; used to reject a move that would create a cycle.
    public function isSelfOrDescendantOf(Folder $candidate, Folder $ancestor): bool
    {
        $current = $candidate;
        while ($current) {
            if ($current->getId() === $ancestor->getId()) {
                return true;
            }
            $current = $current->getParent();
        }

        return false;
    }

    // True when $folder or one of its ancestors is trashed; blocks direct URL access to a subfolder whose parent was deleted.
    public function hasDeletedAncestor(Folder $folder): bool
    {
        $current = $folder->getParent();
        while ($current) {
            if ($current->isDeleted()) {
                return true;
            }
            $current = $current->getParent();
        }

        return false;
    }

    /**
     * Active (non-trashed) subfolders of a folder, sorted by name.
     *
     * @return Folder[]
     */
    public function findActiveChildren(Folder $parent): array
    {
        return $this->findBy(['parent' => $parent, 'deletedAt' => null], ['name' => 'ASC']);
    }

    /**
     * Trashed folders of a space, most recently deleted first.
     *
     * @return Folder[]
     */
    public function findTrashed(string $space): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.space = :space')
            ->andWhere('f.deletedAt IS NOT NULL')
            ->setParameter('space', $space)
            ->orderBy('f.deletedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recursive search by name across the whole space, not just the current folder. Escapes % and _ in the query so they aren't treated as LIKE wildcards.
     *
     * @return Folder[]
     */
    public function search(string $space, string $query): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.space = :space')
            ->andWhere('f.deletedAt IS NULL')
            ->andWhere('LOWER(f.name) LIKE LOWER(:query)')
            ->setParameter('space', $space)
            ->setParameter('query', '%'.addcslashes($query, '%_').'%')
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
