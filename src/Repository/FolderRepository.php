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
     * et SetlistController (dossier du morceau). Ignore les dossiers en
     * corbeille dans sa recherche par nom : sinon un upload/une création
     * "ressusciterait" silencieusement un ancien dossier supprimé au lieu
     * d'en recréer un propre.
     */
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
     * Dossiers de 1er niveau (enfants directs de la racine) d'un espace,
     * actifs, triés par nom : sert au select "dossier du morceau" de
     * /music (SetlistController), pour lier un morceau à un dossier déjà
     * créé (et déjà rempli via /desk/files/music) plutôt que d'en faire
     * créer un à la volée par un champ texte libre (source de doublons par
     * faute de frappe, retour utilisatrice le 2026-08-13). Ne crée pas la
     * racine si elle n'existe pas encore (contrairement à
     * findOrCreateRoot()) : lecture seule, pas d'effet de bord sur un GET.
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

    /**
     * Vrai si $folder ou l'un de ses ancêtres est en corbeille : sert à
     * bloquer l'accès direct par URL (?folder=ID) à un sous-dossier dont le
     * parent a été supprimé (DeskController::files()), plutôt que de
     * laisser un dossier "orphelin caché" rester consultable.
     */
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
     * Sous-dossiers actifs (hors corbeille) d'un dossier, triés par nom :
     * remplace le findBy(['parent' => ...]) brut de DeskController::files()
     * pour exclure ce qui est en corbeille.
     *
     * @return Folder[]
     */
    public function findActiveChildren(Folder $parent): array
    {
        return $this->findBy(['parent' => $parent, 'deletedAt' => null], ['name' => 'ASC']);
    }

    /**
     * Dossiers en corbeille d'un espace, les plus récemment supprimés
     * d'abord : DeskController::trash().
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
     * Recherche récursive par nom sur tout l'espace (pas juste le dossier
     * courant), cf. DeskController::files() en mode recherche. % et _ du
     * terme utilisateur sont échappés (backslash, comportement par défaut
     * de LIKE sous MySQL) pour qu'ils ne soient pas interprétés comme des
     * jokers.
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
