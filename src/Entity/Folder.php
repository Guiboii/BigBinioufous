<?php

namespace App\Entity;

use App\Repository\FolderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FolderRepository::class)]
class Folder
{
    /**
     * Un espace = une arborescence isolée (racine dédiée) + un rôle
     * d'accès dans security.yaml (^/desk/files/{space}). Un sous-dossier
     * hérite du space de son parent à la création (dénormalisé pour éviter
     * de remonter l'arbre à chaque requête), cf. FolderRepository::findOrCreateRoot().
     */
    public const SPACE_MUSIC = 'music';
    public const SPACE_ADMIN = 'admin';
    public const SPACE_ACCOUNTING = 'accounting';
    public const SPACE_OTHER = 'other';
    public const SPACES = [self::SPACE_MUSIC, self::SPACE_ADMIN, self::SPACE_ACCOUNTING, self::SPACE_OTHER];

    /**
     * Rôles autorisés à écrire (créer/déplacer/supprimer un dossier ou
     * document) dans chaque espace, vérifiés par FolderWriteVoter. Distinct
     * de l'accès en lecture (^/desk/files/{space} dans security.yaml) : un
     * espace peut être lisible par plus de monde qu'il n'est écrivable.
     */
    public const WRITE_ROLES = [
        self::SPACE_MUSIC => ['ROLE_BINIOUFOUS', 'ROLE_ADMIN'],
        self::SPACE_ADMIN => ['ROLE_ADMIN'],
        self::SPACE_ACCOUNTING => ['ROLE_COMPTA', 'ROLE_ADMIN'],
        self::SPACE_OTHER => ['ROLE_ADMIN'],
    ];

    /**
     * Types MIME acceptés à l'upload (DocumentController::upload()) par
     * espace : l'espace musique se limite au son/vidéo, les autres gardent
     * la liste large (documents/images/office...).
     */
    private const DOCUMENT_MIME_TYPES = [
        'application/pdf',
        'video/mp4',
        'video/quicktime',
        'video/webm',
        'video/x-msvideo',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'audio/mpeg',
        'audio/mp3',
        'audio/mp4',
        'audio/x-m4a',
        'audio/wav',
        'audio/x-wav',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
    ];

    private const AUDIO_VIDEO_MIME_TYPES = [
        'video/mp4',
        'video/quicktime',
        'video/webm',
        'video/x-msvideo',
        'audio/mpeg',
        'audio/mp3',
        'audio/mp4',
        'audio/x-m4a',
        'audio/wav',
        'audio/x-wav',
    ];

    public const ALLOWED_MIME_TYPES = [
        self::SPACE_MUSIC => self::AUDIO_VIDEO_MIME_TYPES,
        self::SPACE_ADMIN => self::DOCUMENT_MIME_TYPES,
        self::SPACE_ACCOUNTING => self::DOCUMENT_MIME_TYPES,
        self::SPACE_OTHER => self::DOCUMENT_MIME_TYPES,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'string', length: 20)]
    private $space;

    #[ORM\ManyToOne(targetEntity: Folder::class, inversedBy: 'children')]
    private $parent;

    #[ORM\OneToMany(targetEntity: Folder::class, mappedBy: 'parent', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $children;

    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'folder', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $documents;

    /**
     * Corbeille : suppression non récursive (cf. FolderController::delete()) :
     * seul le dossier lui-même reçoit une date, ses enfants ne sont pas
     * touchés. La restauration (FolderController::restore()) ramène donc
     * tout l'arbre d'un coup, sans double entrée dans la corbeille.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $deletedAt;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->documents = new ArrayCollection();
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

    public function getSpace(): ?string
    {
        return $this->space;
    }

    public function setSpace(string $space): self
    {
        $this->space = $space;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|Folder[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @return Collection|Document[]
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
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

    /**
     * Parents du plus ancien (racine) au plus proche, sans se compter
     * elle-même : sert au breadcrumb (DeskController::files()) et à
     * l'affichage du chemin des résultats de recherche
     * (FolderRepository::search()/DocumentRepository::search()).
     *
     * @return Folder[]
     */
    public function getAncestors(): array
    {
        $ancestors = [];
        $current = $this->parent;
        while ($current) {
            array_unshift($ancestors, $current);
            $current = $current->getParent();
        }

        return $ancestors;
    }
}
