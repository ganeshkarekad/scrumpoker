<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\VoteRepository::class)]
#[ORM\Table(name: 'votes')]
#[ORM\Index(columns: ['label'], name: 'idx_vote_label')]
class Vote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 5)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 5)]
    private ?string $label = null;

    /**
     * @var Collection<int, UserVote>
     */
    #[ORM\OneToMany(targetEntity: UserVote::class, mappedBy: 'vote')]
    private Collection $userVotes;

    public function __construct()
    {
        $this->userVotes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
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
            $userVote->setVote($this);
        }

        return $this;
    }

    public function removeUserVote(UserVote $userVote): static
    {
        if ($this->userVotes->removeElement($userVote)) {
            // set the owning side to null (unless already changed)
            if ($userVote->getVote() === $this) {
                $userVote->setVote(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->label ?? '';
    }
}
