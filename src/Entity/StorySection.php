<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

// A section of the Story page, editable in Markdown by ROLE_ADMIN. The slug is computed explicitly by callers rather than auto-generated here, so uniqueness can be checked against the repository first.
#[ORM\Entity(repositoryClass: \App\Repository\StorySectionRepository::class)]
class StorySection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $title;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private $slug;

    #[ORM\Column(type: 'text')]
    private $content;

    // Display order on the page, lowest first. Changed via move up/down buttons rather than drag-and-drop, which isn't keyboard-reachable.
    #[ORM\Column(type: 'integer')]
    private $position = 0;

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }
}
