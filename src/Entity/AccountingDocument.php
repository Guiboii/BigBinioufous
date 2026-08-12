<?php

namespace App\Entity;

use App\Repository\AccountingDocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Devis ou facture, cf. les modèles PDF dans public/uploads/documents
 * (DE045-.../FA025-...) qui ont servi de référence pour les champs et la
 * mise en page (templates/accounting/documents/show.html.twig). Un seul
 * type d'entité pour les deux plutôt que Quote/Invoice séparées : structure
 * strictement identique, seul $type change (préfixe de référence + titre
 * affiché), cf. AccountingDocumentRepository::START_NUMBERS.
 */
#[ORM\Entity(repositoryClass: AccountingDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class AccountingDocument
{
    public const TYPE_QUOTE = 'quote';
    public const TYPE_INVOICE = 'invoice';
    public const TYPES = [self::TYPE_QUOTE, self::TYPE_INVOICE];

    private const PREFIXES = [
        self::TYPE_QUOTE => 'DE',
        self::TYPE_INVOICE => 'FA',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 20)]
    private $type;

    /**
     * Partie numérique de la référence (ex. 46 pour "DE046"), attribuée à
     * la création par AccountingDocumentRepository::findNextNumber().
     */
    #[ORM\Column(type: 'integer')]
    private $number;

    #[ORM\Column(type: 'date_immutable')]
    private $date;

    #[ORM\Column(type: 'string', length: 255)]
    private $clientName;

    #[ORM\Column(type: 'text')]
    private $clientAddress;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $clientContact;

    /**
     * Coordonnées de la personne référente pour ce document (pas forcément
     * qui l'a créé dans l'appli) : pré-remplies depuis le compte connecté à
     * la création, mais éditables (cf. AccountingDocumentType).
     */
    #[ORM\Column(type: 'string', length: 255)]
    private $correspondentName;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $correspondentEmail;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private $correspondentPhone;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private $createdBy;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    /**
     * #[Assert\Valid] indispensable ici : sans elle, Symfony ne valide que
     * AccountingDocument lui-même, pas les AccountingDocumentLine qu'il
     * contient (pas de cascade de validation automatique sur les
     * associations), les contraintes de AccountingDocumentLine (quantité/prix
     * positifs) ne se déclenchaient donc jamais depuis ce formulaire.
     */
    #[ORM\OneToMany(targetEntity: AccountingDocumentLine::class, mappedBy: 'document', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid]
    private $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function initializeCreatedAt(): void
    {
        if (empty($this->createdAt)) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Référence affichée type "DE046"/"FA026", cf. les modèles PDF.
     */
    public function getReference(): string
    {
        return (self::PREFIXES[$this->type] ?? '?').str_pad((string) $this->number, 3, '0', STR_PAD_LEFT);
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function setClientName(string $clientName): self
    {
        $this->clientName = $clientName;

        return $this;
    }

    public function getClientAddress(): ?string
    {
        return $this->clientAddress;
    }

    public function setClientAddress(string $clientAddress): self
    {
        $this->clientAddress = $clientAddress;

        return $this;
    }

    public function getClientContact(): ?string
    {
        return $this->clientContact;
    }

    public function setClientContact(?string $clientContact): self
    {
        $this->clientContact = $clientContact;

        return $this;
    }

    public function getCorrespondentName(): ?string
    {
        return $this->correspondentName;
    }

    public function setCorrespondentName(string $correspondentName): self
    {
        $this->correspondentName = $correspondentName;

        return $this;
    }

    public function getCorrespondentEmail(): ?string
    {
        return $this->correspondentEmail;
    }

    public function setCorrespondentEmail(?string $correspondentEmail): self
    {
        $this->correspondentEmail = $correspondentEmail;

        return $this;
    }

    public function getCorrespondentPhone(): ?string
    {
        return $this->correspondentPhone;
    }

    public function setCorrespondentPhone(?string $correspondentPhone): self
    {
        $this->correspondentPhone = $correspondentPhone;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection|AccountingDocumentLine[]
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(AccountingDocumentLine $line): self
    {
        if (!$this->lines->contains($line)) {
            $this->lines[] = $line;
            $line->setDocument($this);
        }

        return $this;
    }

    public function removeLine(AccountingDocumentLine $line): self
    {
        $this->lines->removeElement($line);

        return $this;
    }

    public function getTotal(): float
    {
        $total = 0.0;
        foreach ($this->lines as $line) {
            $total += $line->getCost();
        }

        return round($total, 2);
    }
}
