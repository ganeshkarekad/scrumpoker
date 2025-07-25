<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[ORM\Table(name: 'room_admins')]
#[ORM\Index(columns: ['room_id'], name: 'idx_room_admin_room')]
#[ORM\Index(columns: ['user_id'], name: 'idx_room_admin_user')]
#[ORM\Index(columns: ['created_at'], name: 'idx_room_admin_created_at')]
#[ORM\UniqueConstraint(name: 'unique_room_user_admin', columns: ['room_id', 'user_id'])]
#[UniqueEntity(fields: ['room', 'user'], message: 'This user is already an admin of this room.')]
class RoomAdmin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Room::class, inversedBy: 'roomAdmins')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Room $room = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'roomAdmins')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            'Admin: %s in Room: %s',
            $this->user?->getUsername() ?? 'Unknown',
            $this->room?->getRoomKey() ?? 'Unknown'
        );
    }
}
