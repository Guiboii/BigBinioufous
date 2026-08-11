<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'string', length: 255)]
    private $filename;

    #[ORM\Column(type: 'string', length: 100)]
    private $mimeType;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private $uploadedBy;

    #[ORM\ManyToOne(targetEntity: Folder::class, inversedBy: 'documents')]
    private $folder;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'favoriteDocuments')]
    private $favoritedBy;

    #[ORM\PrePersist]
    public function initializeCreatedAt(): void
    {
        if (empty($this->createdAt)) {
            $this->createdAt = new \DateTime();
        }
    }

    public function __construct()
    {
        $this->favoritedBy = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): self
    {
        $this->uploadedBy = $uploadedBy;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function setFolder(?Folder $folder): self
    {
        $this->folder = $folder;

        return $this;
    }

    public function getExtension(): string
    {
        return strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));
    }

    public function isAudio(): bool
    {
        return str_starts_with((string) $this->mimeType, 'audio/');
    }

    /**
     * Icône + repère couleur par type de fichier (assets/main/app.css,
     * .desk-document--*) : utile pour repérer vite un fichier dans une
     * grosse arborescence, pas juste décoratif.
     */
    public function getKind(): string
    {
        $mime = (string) $this->mimeType;

        return match (true) {
            str_starts_with($mime, 'audio/') => 'audio',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'image/') => 'image',
            'application/pdf' === $mime => 'pdf',
            str_contains($mime, 'word') => 'word',
            str_contains($mime, 'sheet') || str_contains($mime, 'excel') => 'sheet',
            str_contains($mime, 'presentation') || str_contains($mime, 'powerpoint') => 'slides',
            default => 'file',
        };
    }

    public function getIconClass(): string
    {
        return match ($this->getKind()) {
            'audio' => 'ri-music-2-fill',
            'video' => 'ri-video-fill',
            'image' => 'ri-image-fill',
            'pdf' => 'ri-file-pdf-fill',
            'word' => 'ri-file-word-fill',
            'sheet' => 'ri-file-excel-fill',
            'slides' => 'ri-file-ppt-fill',
            default => 'ri-file-fill',
        };
    }

    /**
     * @return Collection|User[]
     */
    public function getFavoritedBy(): Collection
    {
        return $this->favoritedBy;
    }

    public function addFavoritedBy(User $user): self
    {
        if (!$this->favoritedBy->contains($user)) {
            $this->favoritedBy[] = $user;
        }

        return $this;
    }

    public function removeFavoritedBy(User $user): self
    {
        if ($this->favoritedBy->contains($user)) {
            $this->favoritedBy->removeElement($user);
        }

        return $this;
    }
}
