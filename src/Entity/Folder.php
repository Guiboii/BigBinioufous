<?php

namespace App\Entity;

use App\Repository\FolderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

// A node in the file manager tree (/desk/files/{space}): either a folder or, via Document, a leaf file.
#[ORM\Entity(repositoryClass: FolderRepository::class)]
class Folder
{
    // A space is an isolated tree (own root) plus a read access rule in security.yaml. Children denormalize their parent's space to avoid walking up the tree on every request.
    public const SPACE_MUSIC = 'music';
    public const SPACE_ADMIN = 'admin';
    public const SPACE_ACCOUNTING = 'accounting';
    public const SPACE_OTHER = 'other';
    public const SPACES = [self::SPACE_MUSIC, self::SPACE_ADMIN, self::SPACE_ACCOUNTING, self::SPACE_OTHER];

    // Roles allowed to write (create/move/delete/upload) in each space, checked by FolderWriteVoter. Separate from read access: a space can be readable by more people than it is writable.
    public const WRITE_ROLES = [
        self::SPACE_MUSIC => ['ROLE_BINIOUFOUS', 'ROLE_ADMIN'],
        self::SPACE_ADMIN => ['ROLE_ADMIN'],
        self::SPACE_ACCOUNTING => ['ROLE_COMPTA', 'ROLE_ADMIN'],
        self::SPACE_OTHER => ['ROLE_ADMIN'],
    ];

    // Accepted upload MIME types, same broad list for every space: a real song folder mixes audio with PDFs (sheet music), docx and cover images, so no space restricts to a narrower subset.
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
        'application/vnd.oasis.opendocument.text',
        'text/plain',
    ];

    public const ALLOWED_MIME_TYPES = [
        self::SPACE_MUSIC => self::DOCUMENT_MIME_TYPES,
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

    // Trash marker. Deletion is non-recursive: only this folder gets a date, children are left untouched, so restoring brings the whole subtree back at once without duplicate trash entries.
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
     * Parents from root to closest, excluding this folder itself. Used for breadcrumbs and search result paths.
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
