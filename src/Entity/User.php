<?php

namespace App\Entity;

use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'This email is already used by another user, please change')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * Facultatif depuis la simplification de l'inscription (2026-08-12,
     * cf. ROADMAP.md) : plus demandé à l'inscription (juste email/pseudo/
     * mot de passe), à compléter plus tard sur /desk/profile si la personne
     * le souhaite. Jamais rendu obligatoire même sur le profil ("pas
     * forcément", retour utilisatrice).
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $firstName;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $lastName;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Email(message: 'Please enter a valid email address')]
    private $email;

    #[ORM\Column(type: 'string', length: 255)]
    private $hash;

    #[Assert\EqualTo(propertyPath: 'hash', message: 'you made a mistake')]
    public $passwordConfirm;

    /**
     * Pseudo choisi à l'inscription (avec email/mot de passe, seul
     * identifiant en plus de l'email) : reste obligatoire, contrairement
     * aux champs facultatifs ci-dessous.
     */
    #[ORM\Column(type: 'string', length: 255)]
    private $nickname;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $city;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $gender;

    #[ORM\Column(type: 'date', nullable: true)]
    private $birth;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $country;

    #[ORM\ManyToMany(targetEntity: Role::class, mappedBy: 'users')]
    private $roles;

    #[ORM\ManyToMany(targetEntity: Voice::class, mappedBy: 'users')]
    private $voices;

    #[ORM\ManyToMany(targetEntity: Document::class, mappedBy: 'favoritedBy')]
    private $favoriteDocuments;

    /**
     * Compte accepté par un·e admin (accès de base au site). Décorrélé du
     * rôle (ROLE_BINIOUFOUS, cf. claimsMembership ci-dessous et le toggle
     * "Membre"/"Pas membre" côté admin) depuis la simplification
     * de l'inscription : avant, le champ wish (retiré) déterminait à la
     * fois le rôle ET si la validation était automatique.
     */
    #[ORM\Column(type: 'boolean')]
    private $validation;

    /**
     * Déclaration facultative sur /desk/profile ("Oui, il me semble") :
     * la personne pense être à jour de cotisation HelloAsso. Purement
     * informatif, ne donne aucun accès automatiquement : un·e admin
     * vérifie la cotisation par ses propres moyens (HelloAsso n'a pas
     * d'API pour vérifier en direct) puis bascule le rôle via le toggle
     * "Membre"/"Pas membre". Remplace l'ancien memberCardNumber
     * (numéro de carte, retiré du formulaire : "plus besoin du numéro",
     * retour utilisatrice 2026-08-12) ; colonne member_card_number
     * laissée en base pour ne pas perdre les données déjà saisies, mais
     * plus utilisée dans aucun formulaire.
     */
    #[ORM\Column(type: 'boolean')]
    private $claimsMembership = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $memberCardNumber;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $picture;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $slug;

    #[ORM\ManyToOne(targetEntity: Instrument::class, inversedBy: 'users')]
    private $instrument;

    /**
     * Précision libre quand $instrument pointe vers l'entrée "Autre"
     * (cf. AppFixtures.php), sans quoi cette information serait perdue.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $otherInstrumentDetail;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    /**
     * Permet d'initialiser le slug. Basé sur le pseudo (nickname) plutôt
     * que prénom/nom : depuis la simplification de l'inscription, ces
     * derniers sont facultatifs et vides à la création du compte (juste
     * email/pseudo/mot de passe), le pseudo est la seule donnée d'identité
     * garantie présente à ce stade.
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function initializeSlug(): void
    {
        if (empty($this->slug)) {
            $slugify = new Slugify();
            $this->slug = $slugify->slugify($this->nickname ?: trim($this->firstName.' '.$this->lastName));
        }
    }

    /**
     * Remplis le champ createdAt.
     */
    #[ORM\PrePersist]
    public function initializeCreatedAt()
    {
        if (empty($this->createdAt)) {
            $this->createdAt = new \DateTime();
        }
    }

    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->voices = new ArrayCollection();
        $this->favoriteDocuments = new ArrayCollection();
    }

    /**
     * Repli sur le pseudo si prénom/nom ne sont pas renseignés (facultatifs
     * depuis la simplification de l'inscription) : évite d'afficher juste
     * un espace vide partout où ce nom "complet" est utilisé (listes admin,
     * profil...).
     */
    public function getFullName()
    {
        if (empty($this->firstName) && empty($this->lastName)) {
            return $this->nickname;
        }

        return trim("{$this->firstName} {$this->lastName}");
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles->map(function ($role) {
            return $role->getTitle();
        })->toArray();

        $roles[] = 'ROLE_USER';

        return $roles;
    }

    public function getPassword(): ?string
    {
        return $this->hash;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getBirth(): ?\DateTimeInterface
    {
        return $this->birth;
    }

    public function setBirth(?\DateTimeInterface $birth): self
    {
        $this->birth = $birth;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles[] = $role;
            $role->addUser($this);
        }

        return $this;
    }

    public function removeRole(Role $role): self
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
            $role->removeUser($this);
        }

        return $this;
    }

    public function getValidation(): ?bool
    {
        return $this->validation;
    }

    public function setValidation(bool $validation): self
    {
        $this->validation = $validation;

        return $this;
    }

    public function getClaimsMembership(): bool
    {
        return $this->claimsMembership;
    }

    public function setClaimsMembership(bool $claimsMembership): self
    {
        $this->claimsMembership = $claimsMembership;

        return $this;
    }

    public function getMemberCardNumber(): ?string
    {
        return $this->memberCardNumber;
    }

    public function setMemberCardNumber(?string $memberCardNumber): self
    {
        $this->memberCardNumber = $memberCardNumber;

        return $this;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): self
    {
        $this->picture = $picture;

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

    public function getInstrument(): ?Instrument
    {
        return $this->instrument;
    }

    public function setInstrument(?Instrument $instrument): self
    {
        $this->instrument = $instrument;

        return $this;
    }

    public function getOtherInstrumentDetail(): ?string
    {
        return $this->otherInstrumentDetail;
    }

    public function setOtherInstrumentDetail(?string $otherInstrumentDetail): self
    {
        $this->otherInstrumentDetail = $otherInstrumentDetail;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

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
            $voice->addUser($this);
        }

        return $this;
    }

    public function removeVoice(Voice $voice): self
    {
        if ($this->voices->removeElement($voice)) {
            $voice->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|Document[]
     */
    public function getFavoriteDocuments(): Collection
    {
        return $this->favoriteDocuments;
    }

    public function addFavoriteDocument(Document $document): self
    {
        if (!$this->favoriteDocuments->contains($document)) {
            $this->favoriteDocuments[] = $document;
            $document->addFavoritedBy($this);
        }

        return $this;
    }

    public function removeFavoriteDocument(Document $document): self
    {
        if ($this->favoriteDocuments->removeElement($document)) {
            $document->removeFavoritedBy($this);
        }

        return $this;
    }
}
