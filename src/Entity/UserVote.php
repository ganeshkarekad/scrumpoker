<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[ORM\Table(name: 'user_votes')]
#[ORM\Index(columns: ['room_id'], name: 'idx_user_vote_room')]
#[ORM\Index(columns: ['user_id'], name: 'idx_user_vote_user')]
#[ORM\Index(columns: ['vote_id'], name: 'idx_user_vote_vote')]
#[ORM\Index(columns: ['created_at'], name: 'idx_user_vote_created_at')]
#[ORM\Index(columns: ['updated_at'], name: 'idx_user_vote_updated_at')]
#[ORM\UniqueConstraint(name: 'unique_room_user_vote', columns: ['room_id', 'user_id'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['room', 'user'], message: 'This user has already voted in this room.')]
class UserVote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Room::class, inversedBy: 'userVotes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Room $room = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userVotes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Vote::class, inversedBy: 'userVotes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vote $vote = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
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

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getVote(): ?Vote
    {
        return $this->vote;
    }

    public function setVote(?Vote $vote): static
    {
        $this->vote = $vote;
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

    public function __toString(): string
    {
        return sprintf(
            '%s voted %s in room %s',
            $this->user?->getUsername() ?? 'Unknown',
            $this->vote?->getLabel() ?? 'Unknown',
            $this->room?->getRoomKey() ?? 'Unknown'
        );
    }
}
