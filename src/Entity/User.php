<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\Index(columns: ['username'], name: 'idx_user_username')]
#[ORM\Index(columns: ['created_at'], name: 'idx_user_created_at')]
#[ORM\Index(columns: ['user_login_id'], name: 'idx_user_login_id')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    #[Assert\NotBlank]
    private ?string $userLoginId = null;

    #[ORM\Column(type: 'string', length: 180)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 180)]
    private ?string $username = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Room>
     */
    #[ORM\ManyToMany(targetEntity: Room::class, mappedBy: 'users')]
    private Collection $userRooms;

    /**
     * @var Collection<int, Room>
     */
    #[ORM\OneToMany(targetEntity: Room::class, mappedBy: 'createdBy')]
    private Collection $createdRooms;

    /**
     * @var Collection<int, RoomAdmin>
     */
    #[ORM\OneToMany(targetEntity: RoomAdmin::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $roomAdmins;

    /**
     * @var Collection<int, UserVote>
     */
    #[ORM\OneToMany(targetEntity: UserVote::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $userVotes;

    public function __construct()
    {
        $this->userLoginId = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->userRooms = new ArrayCollection();
        $this->createdRooms = new ArrayCollection();
        $this->roomAdmins = new ArrayCollection();
        $this->userVotes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserLoginId(): ?string
    {
        return $this->userLoginId;
    }

    public function setUserLoginId(string $userLoginId): static
    {
        $this->userLoginId = $userLoginId;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
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

    /**
     * @return Collection<int, Room>
     */
    public function getUserRooms(): Collection
    {
        return $this->userRooms;
    }

    public function addUserRoom(Room $userRoom): static
    {
        if (!$this->userRooms->contains($userRoom)) {
            $this->userRooms->add($userRoom);
            $userRoom->addUser($this);
        }

        return $this;
    }

    public function removeUserRoom(Room $userRoom): static
    {
        if ($this->userRooms->removeElement($userRoom)) {
            $userRoom->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Room>
     */
    public function getCreatedRooms(): Collection
    {
        return $this->createdRooms;
    }

    public function addCreatedRoom(Room $createdRoom): static
    {
        if (!$this->createdRooms->contains($createdRoom)) {
            $this->createdRooms->add($createdRoom);
            $createdRoom->setCreatedBy($this);
        }

        return $this;
    }

    public function removeCreatedRoom(Room $createdRoom): static
    {
        if ($this->createdRooms->removeElement($createdRoom)) {
            // set the owning side to null (unless already changed)
            if ($createdRoom->getCreatedBy() === $this) {
                $createdRoom->setCreatedBy(null);
            }
        }

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
            $roomAdmin->setUser($this);
        }

        return $this;
    }

    public function removeRoomAdmin(RoomAdmin $roomAdmin): static
    {
        if ($this->roomAdmins->removeElement($roomAdmin)) {
            // set the owning side to null (unless already changed)
            if ($roomAdmin->getUser() === $this) {
                $roomAdmin->setUser(null);
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
            $userVote->setUser($this);
        }

        return $this;
    }

    public function removeUserVote(UserVote $userVote): static
    {
        if ($this->userVotes->removeElement($userVote)) {
            // set the owning side to null (unless already changed)
            if ($userVote->getUser() === $this) {
                $userVote->setUser(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->username ?? '';
    }


}
