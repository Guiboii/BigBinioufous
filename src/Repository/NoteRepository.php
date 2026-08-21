<?php

namespace App\Repository;

use App\Entity\Note;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Note|null find($id, $lockMode = null, $lockVersion = null)
 * @method Note|null findOneBy(array $criteria, array $orderBy = null)
 * @method Note[]    findAll()
 * @method Note[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
// Queries for Note.
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    /**
     * Notes visible to an account: their own (private or shared) plus those shared by the rest of the board.
     *
     * @return Note[]
     */
    public function findVisibleForUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.author = :user OR n.shared = true')
            ->setParameter('user', $user)
            ->orderBy('n.updatedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
