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

    /**
     * Taille en octets, capturée à l'upload (DocumentController::upload(),
     * via $file->getSize() avant move()) plutôt que recalculée à la volée
     * avec filesize() : évite un accès disque par document à chaque
     * affichage de dossier, et permet un vrai tri SQL par taille.
     */
    #[ORM\Column(type: 'integer')]
    private $size = 0;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private $uploadedBy;

    #[ORM\ManyToOne(targetEntity: Folder::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private $folder;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'favoriteDocuments')]
    private $favoritedBy;

    /**
     * Membres qui jouent cette partie (documents audio de l'espace musique,
     * cf. User::$playedDocuments). Remplace Voice::$users depuis la fusion
     * Track/Voice dans Folder/Document. JoinTable nommée explicitement :
     * sans ça, Doctrine nomme par défaut la table de jonction d'après les 2
     * entités seules ("document_user"), qui entrerait en collision avec
     * celle déjà utilisée par $favoritedBy ci-dessus.
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'playedDocuments')]
    #[ORM\JoinTable(name: 'document_played_by')]
    private $playedBy;

    /**
     * Corbeille (cf. Folder::$deletedAt pour le fonctionnement d'ensemble).
     * Un document n'a pas de descendants, donc pas de subtilité récursive
     * ici contrairement au dossier.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $deletedAt;

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
        $this->playedBy = new ArrayCollection();
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

    public function setFolder(Folder $folder): self
    {
        $this->folder = $folder;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Formatage lisible (ex. "2,4 Mo") pour l'affichage, cf.
     * templates/desk/files.html.twig. Pas de virgule fixe : arrondi à 1
     * décimale au-delà du Ko, entier en dessous.
     */
    public function getHumanSize(): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $size = (float) $this->size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < \count($units) - 1) {
            $size /= 1024;
            ++$unitIndex;
        }

        $formatted = 0 === $unitIndex ? (string) (int) $size : number_format($size, 1, ',', ' ');

        return $formatted.' '.$units[$unitIndex];
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function getExtension(): string
    {
        return strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));
    }

    public function isAudio(): bool
    {
        return str_starts_with((string) $this->mimeType, 'audio/');
    }

    public function isVideo(): bool
    {
        return str_starts_with((string) $this->mimeType, 'video/');
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

    /**
     * @return Collection|User[]
     */
    public function getPlayedBy(): Collection
    {
        return $this->playedBy;
    }

    public function addPlayedBy(User $user): self
    {
        if (!$this->playedBy->contains($user)) {
            $this->playedBy[] = $user;
        }

        return $this;
    }

    public function removePlayedBy(User $user): self
    {
        if ($this->playedBy->contains($user)) {
            $this->playedBy->removeElement($user);
        }

        return $this;
    }
}
