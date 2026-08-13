<?php

namespace App\Repository;

use App\Entity\Artist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Artist|null find($id, $lockMode = null, $lockVersion = null)
 * @method Artist|null findOneBy(array $criteria, array $orderBy = null)
 * @method Artist[]    findAll()
 * @method Artist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArtistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Artist::class);
    }

    /**
     * Recherche insensible à la casse : sert à retrouver un artiste déjà
     * existant avant d'en créer un nouveau depuis le formulaire de la
     * setlist (SetlistController::new()/edit()), pour éviter les doublons
     * "Metallica"/"metallica" tapés à des moments différents.
     */
    public function findOneByName(string $name): ?Artist
    {
        return $this->createQueryBuilder('a')
            ->andWhere('LOWER(a.name) = LOWER(:name)')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
