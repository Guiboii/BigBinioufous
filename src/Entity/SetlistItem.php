<?php

namespace App\Entity;

use App\Repository\SetlistItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un morceau (ou medley) de la setlist affichée sur /music : titre, artiste
 * et lien YouTube facultatifs pour le grand public, plus un Folder (espace
 * musique) qui porte les fichiers audio réels. Un medley = plusieurs
 * Document dans ce même Folder (remplace Track+Voice, fusionnés dans
 * Folder/Document, cf. plan "Nettoyage de la gestion de fichiers/dossiers").
 */
#[ORM\Entity(repositoryClass: SetlistItemRepository::class)]
class SetlistItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $title;

    #[ORM\ManyToOne(targetEntity: Artist::class, inversedBy: 'songs')]
    private $artist;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $youtubeUrl;

    #[ORM\Column(type: 'integer')]
    private $position;

    #[ORM\ManyToOne(targetEntity: Folder::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private $folder;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getArtist(): ?Artist
    {
        return $this->artist;
    }

    public function setArtist(?Artist $artist): self
    {
        $this->artist = $artist;

        return $this;
    }

    public function getYoutubeUrl(): ?string
    {
        return $this->youtubeUrl;
    }

    public function setYoutubeUrl(?string $youtubeUrl): self
    {
        $this->youtubeUrl = $youtubeUrl;

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

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function setFolder(?Folder $folder): self
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * Fichiers audio du dossier du morceau (medley = plusieurs), pour le
     * lecteur/la liste "voix" de /music. Vide si pas de dossier ou pas
     * encore de fichier dedans (item posé juste avec un lien YouTube).
     *
     * @return Document[]
     */
    public function getAudioDocuments(): array
    {
        if (!$this->folder) {
            return [];
        }

        return array_values(array_filter(
            $this->folder->getDocuments()->toArray(),
            static fn (Document $document) => $document->isAudio()
        ));
    }

    public function getFirstAudioDocument(): ?Document
    {
        return $this->getAudioDocuments()[0] ?? null;
    }
}
