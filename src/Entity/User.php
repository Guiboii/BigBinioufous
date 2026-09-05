<?php

namespace App\Entity;

use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

// A member account: identity, credentials, membership status, roles and instrument.
#[ORM\Entity(repositoryClass: \App\Repository\UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'This email is already used by another user, please change')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TotpTwoFactorInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    // Nullable: registration only asks for email/nickname/password, identity is optional and filled in later on the profile page.
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

    // Chosen at registration alongside email/password; stays required, unlike the optional fields below.
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

    // "I play this part": documents a member has flagged as their own part in an audio track. Distinct from $favoriteDocuments, a plain personal bookmark available on any document.
    #[ORM\ManyToMany(targetEntity: Document::class, mappedBy: 'playedBy')]
    private $playedDocuments;

    #[ORM\ManyToMany(targetEntity: Document::class, mappedBy: 'favoritedBy')]
    private $favoriteDocuments;

    // Account accepted by an admin (base access to the site). Independent from ROLE_BINIOUFOUS: an account can be validated without being a band member.
    #[ORM\Column(type: 'boolean')]
    private $validation;

    // Self-declared at registration ("already a member?"); purely informational, grants no access on its own since there's no API to verify HelloAsso payments automatically. An admin checks manually and toggles the real membership role separately.
    #[ORM\Column(type: 'boolean')]
    private $claimsMembership = false;

    // Set by an admin once the member has confirmed (offline) they agree to the band using their image in its communications. Just a recorded checkmark: consent is collected and stored outside the app.
    #[ORM\Column(type: 'boolean')]
    private $imageRightsConsent = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $memberCardNumber;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $picture;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $slug;

    #[ORM\ManyToOne(targetEntity: Instrument::class, inversedBy: 'users')]
    private $instrument;

    // Free-text detail used when $instrument points to the "Other" entry, otherwise that information would be lost.
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $otherInstrumentDetail;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    // Base32-encoded TOTP secret (2FA). Null means 2FA is disabled; it's opt-in and not restricted to any particular role in the code.
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $totpSecret;

    // Slugify from the nickname rather than first/last name: those are optional and often empty right after registration, the nickname is the only identity data guaranteed to exist.
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function initializeSlug(): void
    {
        if (empty($this->slug)) {
            $slugify = new Slugify();
            $this->slug = $slugify->slugify($this->nickname ?: trim($this->firstName.' '.$this->lastName));
        }
    }

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
        $this->playedDocuments = new ArrayCollection();
        $this->favoriteDocuments = new ArrayCollection();
    }

    // Falls back to the nickname when first/last name are empty, avoids showing a bare space wherever this "full name" is displayed.
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

    // No implicit ROLE_USER added here: an account with no business role simply returns an empty array, which is fine since authentication and roles are separate concerns for Symfony's authorization checker.
    public function getRoles(): array
    {
        return $this->roles->map(function ($role) {
            return $role->getTitle();
        })->toArray();
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

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(?string $totpSecret): self
    {
        $this->totpSecret = $totpSecret;

        return $this;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return null !== $this->totpSecret;
    }

    public function getTotpAuthenticationUsername(): ?string
    {
        return $this->nickname;
    }

    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        if (null === $this->totpSecret) {
            return null;
        }

        return new TotpConfiguration($this->totpSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
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

    public function getImageRightsConsent(): bool
    {
        return $this->imageRightsConsent;
    }

    public function setImageRightsConsent(bool $imageRightsConsent): self
    {
        $this->imageRightsConsent = $imageRightsConsent;

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
     * @return Collection|Document[]
     */
    public function getPlayedDocuments(): Collection
    {
        return $this->playedDocuments;
    }

    public function addPlayedDocument(Document $document): self
    {
        if (!$this->playedDocuments->contains($document)) {
            $this->playedDocuments[] = $document;
            $document->addPlayedBy($this);
        }

        return $this;
    }

    public function removePlayedDocument(Document $document): self
    {
        if ($this->playedDocuments->removeElement($document)) {
            $document->removePlayedBy($this);
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
