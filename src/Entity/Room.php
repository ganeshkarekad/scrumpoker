<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: \App\Repository\RoomRepository::class)]
#[ORM\Table(name: 'rooms')]
#[ORM\Index(columns: ['room_key'], name: 'idx_room_key')]
#[ORM\Index(columns: ['created_at'], name: 'idx_room_created_at')]
#[ORM\Index(columns: ['updated_at'], name: 'idx_room_updated_at')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['roomKey'], message: 'This room key already exists.')]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $roomKey = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'createdRooms')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $votesVisible = false;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'userRooms')]
    #[ORM\JoinTable(name: 'room_users')]
    private Collection $users;

    /**
     * @var Collection<int, RoomAdmin>
     */
    #[ORM\OneToMany(targetEntity: RoomAdmin::class, mappedBy: 'room', cascade: ['remove'])]
    private Collection $roomAdmins;

    /**
     * @var Collection<int, UserVote>
     */
    #[ORM\OneToMany(targetEntity: UserVote::class, mappedBy: 'room', cascade: ['remove'])]
    private Collection $userVotes;

    public function __construct()
    {
        $this->roomKey = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
        $this->users = new ArrayCollection();
        $this->roomAdmins = new ArrayCollection();
        $this->userVotes = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoomKey(): ?string
    {
        return $this->roomKey;
    }

    public function setRoomKey(string $roomKey): static
    {
        $this->roomKey = $roomKey;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getVotesVisible(): bool
    {
        return $this->votesVisible;
    }

    public function setVotesVisible(bool $votesVisible): static
    {
        $this->votesVisible = $votesVisible;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);
        return $this;
    }

    /**
     * @return Collection<int, RoomAdmin>
     */
    public function getRoomAdmins(): Collection
    {
        return $this->roomAdmins;
    }

    public function addRoomAdmin(RoomAdmin $roomAdmin): static
    {
        if (!$this->roomAdmins->contains($roomAdmin)) {
            $this->roomAdmins->add($roomAdmin);
            $roomAdmin->setRoom($this);
        }

        return $this;
    }

    public function removeRoomAdmin(RoomAdmin $roomAdmin): static
    {
        if ($this->roomAdmins->removeElement($roomAdmin)) {
            // set the owning side to null (unless already changed)
            if ($roomAdmin->getRoom() === $this) {
                $roomAdmin->setRoom(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserVote>
     */
    public function getUserVotes(): Collection
    {
        return $this->userVotes;
    }

    public function addUserVote(UserVote $userVote): static
    {
        if (!$this->userVotes->contains($userVote)) {
            $this->userVotes->add($userVote);
            $userVote->setRoom($this);
        }

        return $this;
    }

    public function removeUserVote(UserVote $userVote): static
    {
        if ($this->userVotes->removeElement($userVote)) {
            // set the owning side to null (unless already changed)
            if ($userVote->getRoom() === $this) {
                $userVote->setRoom(null);
            }
        }

        return $this;
    }

    /**
     * Check if a user is an admin of this room
     */
    public function isUserAdmin(User $user): bool
    {
        foreach ($this->roomAdmins as $roomAdmin) {
            if ($roomAdmin->getUser() === $user) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the vote of a specific user in this room
     */
    public function getUserVote(User $user): ?UserVote
    {
        foreach ($this->userVotes as $userVote) {
            if ($userVote->getUser() === $user) {
                return $userVote;
            }
        }
        return null;
    }

    public function __toString(): string
    {
        return $this->roomKey ?? '';
    }
}
