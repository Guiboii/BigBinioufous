<?php

namespace App\Entity;

use App\Repository\CarpoolOfferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

// A carpool offer for a given Event: a driver proposes seats from a departure point, other members join until seats run out.
#[ORM\Entity(repositoryClass: CarpoolOfferRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CarpoolOffer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $event;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $driver;

    #[ORM\Column(type: 'string', length: 255)]
    private $departureLocation;

    // Optional precise departure time: the event's own date/time is already known, this only matters when it differs (e.g. leaving the day before).
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $departureTime;

    // Seats offered to passengers, not counting the driver.
    #[ORM\Column(type: 'smallint')]
    private $seatsTotal;

    #[ORM\Column(type: 'text', nullable: true)]
    private $comment;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'carpool_offer_passenger')]
    private $passengers;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    public function __construct()
    {
        $this->passengers = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function initializeCreatedAt(): void
    {
        if (empty($this->createdAt)) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getDriver(): ?User
    {
        return $this->driver;
    }

    public function setDriver(User $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    public function getDepartureLocation(): ?string
    {
        return $this->departureLocation;
    }

    public function setDepartureLocation(string $departureLocation): self
    {
        $this->departureLocation = $departureLocation;

        return $this;
    }

    public function getDepartureTime(): ?\DateTimeImmutable
    {
        return $this->departureTime;
    }

    public function setDepartureTime(?\DateTimeImmutable $departureTime): self
    {
        $this->departureTime = $departureTime;

        return $this;
    }

    public function getSeatsTotal(): ?int
    {
        return $this->seatsTotal;
    }

    public function setSeatsTotal(int $seatsTotal): self
    {
        $this->seatsTotal = $seatsTotal;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getPassengers(): Collection
    {
        return $this->passengers;
    }

    public function addPassenger(User $user): self
    {
        if (!$this->passengers->contains($user)) {
            $this->passengers[] = $user;
        }

        return $this;
    }

    public function removePassenger(User $user): self
    {
        $this->passengers->removeElement($user);

        return $this;
    }

    public function getSeatsAvailable(): int
    {
        return max(0, $this->seatsTotal - $this->passengers->count());
    }

    public function isFull(): bool
    {
        return $this->getSeatsAvailable() <= 0;
    }

    public function isDriver(User $user): bool
    {
        return $this->driver->getId() === $user->getId();
    }

    public function hasPassenger(User $user): bool
    {
        foreach ($this->passengers as $passenger) {
            if ($passenger->getId() === $user->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
