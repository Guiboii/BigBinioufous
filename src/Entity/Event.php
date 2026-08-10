<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $title;

    #[ORM\Column(type: 'string', length: 255)]
    private $location;

    /**
     * Valeurs internes en anglais ('rehearsal'/'concert'/'other'), même
     * convention que les rôles/choix existants (ex. ROLE_ADMIN, le champ
     * `wish` de RegistrationType) : le libellé affiché est traduit
     * (event.type_*), la valeur stockée en base ne l'est pas.
     */
    #[ORM\Column(type: 'string', length: 20)]
    private $type;

    #[ORM\Column(type: 'datetime_immutable')]
    private $date;

    /**
     * Facultatif : heure de fin (même jour). Sans elle, l'export .ics/
     * Google/Outlook (ScheduleController) part sur une durée par défaut de
     * 2h plutôt que d'inventer une heure de fin.
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $endDate;

    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $posterFilename;

    #[Assert\File(
        maxSize: '5000k',
        mimeTypes: ['image/jpeg', 'image/png'],
        mimeTypesMessage: 'Please upload a valid JPEG or PNG image',
    )]
    protected $posterFile;

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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPosterFilename(): ?string
    {
        return $this->posterFilename;
    }

    public function setPosterFilename(?string $posterFilename): self
    {
        $this->posterFilename = $posterFilename;

        return $this;
    }
}
