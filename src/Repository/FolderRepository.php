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
class FolderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Folder::class);
    }

    /**
     * Chaque espace (musique/admin/compta) a une racine dédiée, créée à la
     * volée au premier accès plutôt que seedée en fixtures : même logique
     * que DocumentController::resolveFolder() pour les sous-dossiers créés
     * par glisser-déposer.
     */
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

    /**
     * "02 Medley XXI/Hautbois Facile" -> crée/retrouve les 2 Folder imbriqués
     * sous la racine de l'espace, et renvoie le plus profond. Vide -> racine.
     * Partagé par DocumentController::upload() (chemin du glisser-déposer)
     * et SetlistController (dossier du morceau).
     */
    public function findOrCreateByPath(string $space, string $path, EntityManagerInterface $manager): Folder
    {
        $parent = $this->findOrCreateRoot($space, $manager);

        $segments = array_filter(explode('/', $path), static fn (string $s) => '' !== trim($s));

        foreach ($segments as $name) {
            $name = trim($name);
            $existing = $this->findOneBy(['parent' => $parent, 'name' => $name]);
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
     * Vrai si $candidate est $ancestor lui-même ou un de ses descendants :
     * sert à refuser un déplacement de dossier qui créerait un cycle
     * (déplacer un dossier dans un de ses propres sous-dossiers).
     */
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
}
