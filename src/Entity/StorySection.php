<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Une section de la page Histoire (templates/story/minisite.html.twig),
 * éditable en Markdown par ROLE_ADMIN via /admin/story. Le nombre de
 * sections et leur ordre sont libres (contrairement au découpage fixe
 * intro/qui/quoi/pourquoi d'origine, codé en dur dans les templates/
 * translations avant cette entité). Le slug n'est pas auto-généré ici :
 * calculé explicitement par les appelants (StorySectionController::new(),
 * StorySectionSeedData via AppFixtures.php) pour pouvoir vérifier l'unicité
 * via le repository, ce qu'un callback de cycle de vie sur l'entité ne peut
 * pas faire proprement.
 */
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

    /**
     * Ordre d'affichage sur la page (le plus petit en premier). Modifié via
     * les boutons "monter"/"descendre" de la liste admin plutôt qu'un
     * glisser-déposer, non atteignable au clavier (cf. les correctifs a11y
     * du gestionnaire de fichiers en Phase 7 sur le même sujet).
     */
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
