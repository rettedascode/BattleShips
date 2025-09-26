<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_PLACEMENT = 'PLACEMENT';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_FINISHED = 'FINISHED';
    public const STATUS_CANCELLED = 'CANCELLED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_OPEN;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $player1 = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $player2 = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $currentTurnUserId = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $winnerUserId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $finishedAt = null;

    #[ORM\Column(type: Types::JSON)]
    private array $settingsJSON = [];

    #[ORM\OneToMany(mappedBy: 'game', targetEntity: Board::class, cascade: ['persist', 'remove'])]
    private Collection $boards;

    #[ORM\OneToMany(mappedBy: 'game', targetEntity: Move::class, cascade: ['persist', 'remove'])]
    private Collection $moves;

    public function __construct()
    {
        $this->boards = new ArrayCollection();
        $this->moves = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPlayer1(): ?User
    {
        return $this->player1;
    }

    public function setPlayer1(?User $player1): static
    {
        $this->player1 = $player1;

        return $this;
    }

    public function getPlayer2(): ?User
    {
        return $this->player2;
    }

    public function setPlayer2(?User $player2): static
    {
        $this->player2 = $player2;

        return $this;
    }

    public function getCurrentTurnUserId(): ?User
    {
        return $this->currentTurnUserId;
    }

    public function setCurrentTurnUserId(?User $currentTurnUserId): static
    {
        $this->currentTurnUserId = $currentTurnUserId;

        return $this;
    }

    public function getWinnerUserId(): ?User
    {
        return $this->winnerUserId;
    }

    public function setWinnerUserId(?User $winnerUserId): static
    {
        $this->winnerUserId = $winnerUserId;

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

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): ?\DateTimeInterface
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeInterface $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function getSettingsJSON(): array
    {
        return $this->settingsJSON;
    }

    public function setSettingsJSON(array $settingsJSON): static
    {
        $this->settingsJSON = $settingsJSON;

        return $this;
    }

    /**
     * @return Collection<int, Board>
     */
    public function getBoards(): Collection
    {
        return $this->boards;
    }

    public function addBoard(Board $board): static
    {
        if (!$this->boards->contains($board)) {
            $this->boards->add($board);
            $board->setGame($this);
        }

        return $this;
    }

    public function removeBoard(Board $board): static
    {
        if ($this->boards->removeElement($board)) {
            // set the owning side to null (unless already changed)
            if ($board->getGame() === $this) {
                $board->setGame(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Move>
     */
    public function getMoves(): Collection
    {
        return $this->moves;
    }

    public function addMove(Move $move): static
    {
        if (!$this->moves->contains($move)) {
            $this->moves->add($move);
            $move->setGame($this);
        }

        return $this;
    }

    public function removeMove(Move $move): static
    {
        if ($this->moves->removeElement($move)) {
            // set the owning side to null (unless already changed)
            if ($move->getGame() === $this) {
                $move->setGame(null);
            }
        }

        return $this;
    }

    public function isPlayer(User $user): bool
    {
        return $this->player1 === $user || $this->player2 === $user;
    }

    public function getOpponent(User $user): ?User
    {
        if ($this->player1 === $user) {
            return $this->player2;
        }
        
        if ($this->player2 === $user) {
            return $this->player1;
        }
        
        return null;
    }

    public function isCurrentTurn(User $user): bool
    {
        return $this->currentTurnUserId === $user;
    }

    public function getBoardForUser(User $user): ?Board
    {
        foreach ($this->boards as $board) {
            if ($board->getUser() === $user) {
                return $board;
            }
        }
        
        return null;
    }
}
