<?php

namespace App\Entity;

use App\Repository\BoardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoardRepository::class)]
class Board
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'boards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'boards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private int $width = 10;

    #[ORM\Column]
    private int $height = 10;

    #[ORM\Column(type: Types::JSON)]
    private array $fleetJSON = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $placedAt = null;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getFleetJSON(): array
    {
        return $this->fleetJSON;
    }

    public function setFleetJSON(array $fleetJSON): static
    {
        $this->fleetJSON = $fleetJSON;

        return $this;
    }

    public function getPlacedAt(): ?\DateTimeInterface
    {
        return $this->placedAt;
    }

    public function setPlacedAt(?\DateTimeInterface $placedAt): static
    {
        $this->placedAt = $placedAt;

        return $this;
    }

    public function isPlaced(): bool
    {
        return $this->placedAt !== null;
    }

    public function getShipAt(int $x, int $y): ?array
    {
        foreach ($this->fleetJSON as $ship) {
            foreach ($ship['cells'] as $cell) {
                if ($cell[0] === $x && $cell[1] === $y) {
                    return $ship;
                }
            }
        }
        
        return null;
    }

    public function hasShipAt(int $x, int $y): bool
    {
        return $this->getShipAt($x, $y) !== null;
    }

    public function getShips(): array
    {
        return $this->fleetJSON;
    }

    public function getShipCount(): int
    {
        return count($this->fleetJSON);
    }

    public function getAliveShipCount(): int
    {
        $aliveCount = 0;
        foreach ($this->fleetJSON as $ship) {
            if (!isset($ship['sunk']) || !$ship['sunk']) {
                $aliveCount++;
            }
        }
        
        return $aliveCount;
    }
}
