<?php

namespace App\Entity;

use App\Repository\MoveRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MoveRepository::class)]
class Move
{
    public const RESULT_HIT = 'HIT';
    public const RESULT_MISS = 'MISS';
    public const RESULT_SUNK = 'SUNK';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'moves')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'moves')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $attackerUser = null;

    #[ORM\Column]
    private int $x;

    #[ORM\Column]
    private int $y;

    #[ORM\Column(length: 10)]
    private string $result;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column]
    private int $turnIndex;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;

        return $this;
    }

    public function getAttackerUser(): ?User
    {
        return $this->attackerUser;
    }

    public function setAttackerUser(?User $attackerUser): static
    {
        $this->attackerUser = $attackerUser;

        return $this;
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function setX(int $x): static
    {
        $this->x = $x;

        return $this;
    }

    public function getY(): int
    {
        return $this->y;
    }

    public function setY(int $y): static
    {
        $this->y = $y;

        return $this;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function setResult(string $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTurnIndex(): int
    {
        return $this->turnIndex;
    }

    public function setTurnIndex(int $turnIndex): static
    {
        $this->turnIndex = $turnIndex;

        return $this;
    }

    public function isHit(): bool
    {
        return $this->result === self::RESULT_HIT || $this->result === self::RESULT_SUNK;
    }

    public function isSunk(): bool
    {
        return $this->result === self::RESULT_SUNK;
    }

    public function isMiss(): bool
    {
        return $this->result === self::RESULT_MISS;
    }
}
