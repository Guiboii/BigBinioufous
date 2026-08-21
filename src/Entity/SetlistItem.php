<?php

namespace App\Entity;

use App\Repository\SetlistItemRepository;
use Doctrine\ORM\Mapping as ORM;

// A song (or medley) on the /music setlist: title, optional artist/YouTube link for the public, plus a Folder holding the real audio files.
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
     * Audio and video files in the song's folder, for the /music player and voice list. Empty when there's no folder yet, e.g. an item posted with just a YouTube link.
     *
     * @return Document[]
     */
    public function getMediaDocuments(): array
    {
        if (!$this->folder) {
            return [];
        }

        return array_values(array_filter(
            $this->folder->getDocuments()->toArray(),
            static fn (Document $document) => $document->isAudio() || $document->isVideo()
        ));
    }

    public function getFirstMediaDocument(): ?Document
    {
        return $this->getMediaDocuments()[0] ?? null;
    }

    // Extracts the video id from $youtubeUrl, already restricted to the youtube.com/watch?v=ID and youtu.be/ID formats upstream. Used to embed a video player in place of the waveform.
    public function getYoutubeId(): ?string
    {
        if (!$this->youtubeUrl) {
            return null;
        }

        if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]+)#', $this->youtubeUrl, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
