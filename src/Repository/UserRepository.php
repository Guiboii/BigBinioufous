<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findUnvalids()
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.validation = false')
            ->orderBy('a.wish', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAdmins($roleAdmin)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('r.id = :val')
            ->join('u.roles', 'r')
            ->setParameter('val', $roleAdmin)
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAccountants($roleAccountant)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('r.id = :val')
            ->join('u.roles', 'r')
            ->setParameter('val', $roleAccountant)
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findBinioufous($roleBinioufous)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('r.id = :val')
            ->leftJoin('u.roles', 'r')
            ->setParameter('val', $roleBinioufous)
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMembers($roleMember)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('r.id = :val')
            ->leftJoin('u.roles', 'r')
            ->setParameter('val', $roleMember)
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findSimples($roleSimple)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('r.id = :val')
            ->leftJoin('u.roles', 'r')
            ->setParameter('val', $roleSimple)
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
