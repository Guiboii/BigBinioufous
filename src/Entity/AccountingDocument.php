<?php

namespace App\Entity;

use App\Repository\AccountingDocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

// A quote or an invoice. One entity for both rather than separate Quote/Invoice classes: the structure is identical, only $type (and its reference prefix) differs.
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

    // Numeric part of the reference (e.g. 46 for "DE046"), assigned at creation.
    #[ORM\Column(type: 'integer')]
    private $number;

    #[ORM\Column(type: 'date_immutable')]
    private $date;

    // Only used to prefill clientName/clientAddress/clientContact below at creation; those copied fields remain the ones actually displayed and printed.
    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private $client;

    #[ORM\Column(type: 'string', length: 255)]
    private $clientName;

    #[ORM\Column(type: 'text')]
    private $clientAddress;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $clientContact;

    // Contact person for this document, not necessarily who created it in the app. Prefilled from the logged-in account but editable.
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

    // Source quote when this document is an invoice created from one. Client and lines are copied once at creation (a snapshot); this link is only for traceability, not for keeping the two in sync afterwards.
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private $sourceQuote;

    /**
     * @var Collection|self[]
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'sourceQuote')]
    private $invoices;

    // Hides a quote from the "new invoice from quote" picker without deleting it: not every quote is meant to become an invoice, and a reversible toggle keeps that list manageable. The quote stays visible/editable everywhere else.
    #[ORM\Column(type: 'boolean')]
    private $excludedFromInvoicing = false;

    // #[Assert\Valid] is required here: without it Symfony only validates AccountingDocument itself, not the lines it contains, since validation doesn't cascade to associations automatically.
    #[ORM\OneToMany(targetEntity: AccountingDocumentLine::class, mappedBy: 'document', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid]
    private $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
        $this->invoices = new ArrayCollection();
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

    // Displayed reference, e.g. "DE046" or "FA026".
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

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

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

    public function getSourceQuote(): ?self
    {
        return $this->sourceQuote;
    }

    public function setSourceQuote(?self $sourceQuote): self
    {
        $this->sourceQuote = $sourceQuote;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function isExcludedFromInvoicing(): bool
    {
        return $this->excludedFromInvoicing;
    }

    public function setExcludedFromInvoicing(bool $excludedFromInvoicing): self
    {
        $this->excludedFromInvoicing = $excludedFromInvoicing;

        return $this;
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
