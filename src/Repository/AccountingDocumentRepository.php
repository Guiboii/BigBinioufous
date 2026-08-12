<?php

namespace App\Repository;

use App\Entity\AccountingDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AccountingDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccountingDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccountingDocument[]    findAll()
 * @method AccountingDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountingDocumentRepository extends ServiceEntityRepository
{
    /**
     * Point de départ du compteur choisi par l'utilisatrice (2026-08-12) :
     * les devis/factures papier existants s'arrêtent à DE045/FA025 (cf. les
     * modèles PDF dans public/uploads/documents), sans que leur historique
     * soit ressaisi dans l'appli. Le prochain numéro attribué par
     * findNextNumber() ne descend jamais sous ce plancher, même base vide.
     */
    private const START_NUMBERS = [
        AccountingDocument::TYPE_QUOTE => 46,
        AccountingDocument::TYPE_INVOICE => 26,
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountingDocument::class);
    }

    public function findNextNumber(string $type): int
    {
        $maxInDb = $this->createQueryBuilder('d')
            ->select('MAX(d.number)')
            ->where('d.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();

        $floor = self::START_NUMBERS[$type] ?? 1;

        return null === $maxInDb ? $floor : max((int) $maxInDb + 1, $floor);
    }

    /**
     * @return AccountingDocument[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.date', 'DESC')
            ->addOrderBy('d.number', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
