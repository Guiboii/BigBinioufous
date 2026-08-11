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
    public const SPACES = [self::SPACE_MUSIC, self::SPACE_ADMIN, self::SPACE_ACCOUNTING];

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
}
