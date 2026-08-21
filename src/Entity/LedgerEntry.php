<?php

namespace App\Entity;

use App\Repository\LedgerEntryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

// A cash journal line (income or expense). Named "LedgerEntry" rather than "Transaction" to avoid any collision with the SQL reserved word.
#[ORM\Entity(repositoryClass: LedgerEntryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class LedgerEntry
{
    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';
    public const TYPES = [self::TYPE_INCOME, self::TYPE_EXPENSE];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'date_immutable')]
    private $date;

    #[ORM\Column(type: 'string', length: 20)]
    private $type;

    #[ORM\Column(type: 'string', length: 255)]
    private $label;

    // Always positive: the income/expense sign comes from $type, not from the amount itself.
    #[ORM\Column(type: 'float')]
    #[Assert\Positive]
    private $amount;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $category;

    // Optional link to the quote/invoice this entry relates to (e.g. a payment received). Purely informational, nothing creates it automatically.
    #[ORM\ManyToOne(targetEntity: AccountingDocument::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private $relatedDocument;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private $createdBy;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

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

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getRelatedDocument(): ?AccountingDocument
    {
        return $this->relatedDocument;
    }

    public function setRelatedDocument(?AccountingDocument $relatedDocument): self
    {
        $this->relatedDocument = $relatedDocument;

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

    public function isIncome(): bool
    {
        return self::TYPE_INCOME === $this->type;
    }

    // Signed amount (positive for income, negative for an expense): what to sum to get a balance.
    public function getSignedAmount(): float
    {
        return $this->isIncome() ? $this->amount : -$this->amount;
    }
}
