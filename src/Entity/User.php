<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $username = null;

    #[ORM\Column]
    private bool $isBanned = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    // Game statistics
    #[ORM\Column]
    private int $wins = 0;

    #[ORM\Column]
    private int $losses = 0;

    #[ORM\Column]
    private int $points = 0;

    #[ORM\Column]
    private int $hitCountTotal = 0;

    #[ORM\Column]
    private int $gamesPlayed = 0;

    #[ORM\OneToMany(mappedBy: 'player1', targetEntity: Game::class)]
    private Collection $gamesAsPlayer1;

    #[ORM\OneToMany(mappedBy: 'player2', targetEntity: Game::class)]
    private Collection $gamesAsPlayer2;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Board::class)]
    private Collection $boards;

    #[ORM\OneToMany(mappedBy: 'attackerUser', targetEntity: Move::class)]
    private Collection $moves;

    public function __construct()
    {
        $this->gamesAsPlayer1 = new ArrayCollection();
        $this->gamesAsPlayer2 = new ArrayCollection();
        $this->boards = new ArrayCollection();
        $this->moves = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function isBanned(): bool
    {
        return $this->isBanned;
    }

    public function setBanned(bool $isBanned): static
    {
        $this->isBanned = $isBanned;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getWins(): int
    {
        return $this->wins;
    }

    public function setWins(int $wins): static
    {
        $this->wins = $wins;

        return $this;
    }

    public function getLosses(): int
    {
        return $this->losses;
    }

    public function setLosses(int $losses): static
    {
        $this->losses = $losses;

        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;

        return $this;
    }

    public function getHitCountTotal(): int
    {
        return $this->hitCountTotal;
    }

    public function setHitCountTotal(int $hitCountTotal): static
    {
        $this->hitCountTotal = $hitCountTotal;

        return $this;
    }

    public function getGamesPlayed(): int
    {
        return $this->gamesPlayed;
    }

    public function setGamesPlayed(int $gamesPlayed): static
    {
        $this->gamesPlayed = $gamesPlayed;

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGamesAsPlayer1(): Collection
    {
        return $this->gamesAsPlayer1;
    }

    public function addGamesAsPlayer1(Game $gamesAsPlayer1): static
    {
        if (!$this->gamesAsPlayer1->contains($gamesAsPlayer1)) {
            $this->gamesAsPlayer1->add($gamesAsPlayer1);
            $gamesAsPlayer1->setPlayer1($this);
        }

        return $this;
    }

    public function removeGamesAsPlayer1(Game $gamesAsPlayer1): static
    {
        if ($this->gamesAsPlayer1->removeElement($gamesAsPlayer1)) {
            // set the owning side to null (unless already changed)
            if ($gamesAsPlayer1->getPlayer1() === $this) {
                $gamesAsPlayer1->setPlayer1(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGamesAsPlayer2(): Collection
    {
        return $this->gamesAsPlayer2;
    }

    public function addGamesAsPlayer2(Game $gamesAsPlayer2): static
    {
        if (!$this->gamesAsPlayer2->contains($gamesAsPlayer2)) {
            $this->gamesAsPlayer2->add($gamesAsPlayer2);
            $gamesAsPlayer2->setPlayer2($this);
        }

        return $this;
    }

    public function removeGamesAsPlayer2(Game $gamesAsPlayer2): static
    {
        if ($this->gamesAsPlayer2->removeElement($gamesAsPlayer2)) {
            // set the owning side to null (unless already changed)
            if ($gamesAsPlayer2->getPlayer2() === $this) {
                $gamesAsPlayer2->setPlayer2(null);
            }
        }

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
            $board->setUser($this);
        }

        return $this;
    }

    public function removeBoard(Board $board): static
    {
        if ($this->boards->removeElement($board)) {
            // set the owning side to null (unless already changed)
            if ($board->getUser() === $this) {
                $board->setUser(null);
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
            $move->setAttackerUser($this);
        }

        return $this;
    }

    public function removeMove(Move $move): static
    {
        if ($this->moves->removeElement($move)) {
            // set the owning side to null (unless already changed)
            if ($move->getAttackerUser() === $this) {
                $move->setAttackerUser(null);
            }
        }

        return $this;
    }

    public function getWinRate(): float
    {
        if ($this->gamesPlayed === 0) {
            return 0.0;
        }
        
        return $this->wins / $this->gamesPlayed;
    }
}