<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\TrackRepository::class)]
class Track
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
    private $trackFilename;

    #[ORM\Column(type: 'integer')]
    private $minutes;

    #[ORM\Column(type: 'integer')]
    private $seconds;

    #[Assert\File(
        maxSize: '200000k',
        mimeTypes: ['application/mp3', 'application/x-mp3'],
        mimeTypesMessage: 'Please upload a valid mp3',
    )]
    protected $trackFile;

    #[ORM\OneToMany(targetEntity: Voice::class, mappedBy: 'track', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $voices;

    public function __construct()
    {
        $this->voices = new ArrayCollection();
    }

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

    public function getTrackFilename(): ?string
    {
        return $this->trackFilename;
    }

    public function setTrackFilename(?string $trackFilename): self
    {
        $this->trackFilename = $trackFilename;

        return $this;
    }

    public function getMinutes(): ?int
    {
        return $this->minutes;
    }

    public function setMinutes(int $minutes): self
    {
        $this->minutes = $minutes;

        return $this;
    }

    public function getSeconds(): ?int
    {
        return $this->seconds;
    }

    public function setSeconds(int $seconds): self
    {
        $this->seconds = $seconds;

        return $this;
    }

    /**
     * @return Collection|Voice[]
     */
    public function getVoices(): Collection
    {
        return $this->voices;
    }

    public function addVoice(Voice $voice): self
    {
        if (!$this->voices->contains($voice)) {
            $this->voices[] = $voice;
            $voice->setTrack($this);
        }

        return $this;
    }

    public function removeVoice(Voice $voice): self
    {
        if ($this->voices->removeElement($voice)) {
            if ($voice->getTrack() === $this) {
                $voice->setTrack(null);
            }
        }

        return $this;
    }
}
