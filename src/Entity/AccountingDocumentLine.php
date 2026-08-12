<?php

namespace App\Entity;

use App\Repository\AccountingDocumentLineRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AccountingDocumentLineRepository::class)]
class AccountingDocumentLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: AccountingDocument::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $document;

    #[ORM\Column(type: 'string', length: 255)]
    private $label;

    #[ORM\Column(type: 'float')]
    #[Assert\PositiveOrZero]
    private $unitPrice;

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive]
    private $quantity = 1;

    /**
     * Ordre d'affichage des lignes dans le devis/la facture (l'ordre de
     * saisie dans le formulaire, cf. AccountingDocumentType), pas d'ordre
     * alphabétique ou autre qui perdrait la logique métier de la liste.
     */
    #[ORM\Column(type: 'integer')]
    private $position = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocument(): ?AccountingDocument
    {
        return $this->document;
    }

    public function setDocument(?AccountingDocument $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getUnitPrice(): ?float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(float $unitPrice): self
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getCost(): float
    {
        return round($this->unitPrice * $this->quantity, 2);
    }
}
