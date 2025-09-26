<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\User;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;

class MatchmakingService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GameRepository $gameRepository
    ) {
    }

    /**
     * Find or create a match for a user
     */
    public function findOrCreateMatch(User $user): Game
    {
        // Check if user already has an active game
        $activeGame = $this->getActiveGameForUser($user);
        if ($activeGame) {
            return $activeGame;
        }

        // Look for open games
        $openGames = $this->gameRepository->findOpenGames();
        
        if (!empty($openGames)) {
            // Join existing game
            $game = $openGames[0];
            $game->setPlayer2($user);
            $game->setStatus(Game::STATUS_PLACEMENT);
            $game->setCurrentTurnUserId($game->getPlayer1()); // Player1 starts
            $game->setStartedAt(new \DateTime());
            
            $this->entityManager->flush();
            
            return $game;
        }

        // Create new game
        return $this->createNewGame($user);
    }

    /**
     * Create a private game with invite code
     */
    public function createPrivateGame(User $creator): Game
    {
        $game = $this->createNewGame($creator);
        $game->setStatus(Game::STATUS_OPEN); // Keep it open for invite
        
        $this->entityManager->flush();
        
        return $game;
    }

    /**
     * Join a private game by ID
     */
    public function joinPrivateGame(User $user, int $gameId): ?Game
    {
        $game = $this->gameRepository->find($gameId);
        
        if (!$game) {
            return null;
        }
        
        if ($game->getStatus() !== Game::STATUS_OPEN) {
            return null;
        }
        
        if ($game->getPlayer1() === $user) {
            return null; // Can't join your own game
        }
        
        if ($game->getPlayer2() !== null) {
            return null; // Game already full
        }
        
        $game->setPlayer2($user);
        $game->setStatus(Game::STATUS_PLACEMENT);
        $game->setCurrentTurnUserId($game->getPlayer1());
        $game->setStartedAt(new \DateTime());
        
        $this->entityManager->flush();
        
        return $game;
    }

    /**
     * Get active game for user
     */
    public function getActiveGameForUser(User $user): ?Game
    {
        $activeGames = $this->gameRepository->findActiveGamesForUser($user);
        
        return !empty($activeGames) ? $activeGames[0] : null;
    }

    /**
     * Cancel a game
     */
    public function cancelGame(Game $game, User $user): bool
    {
        if (!$game->isPlayer($user)) {
            return false;
        }
        
        if ($game->getStatus() === Game::STATUS_FINISHED) {
            return false;
        }
        
        $game->setStatus(Game::STATUS_CANCELLED);
        $game->setFinishedAt(new \DateTime());
        
        $this->entityManager->flush();
        
        return true;
    }

    /**
     * Get available games for lobby
     */
    public function getAvailableGames(): array
    {
        return $this->gameRepository->findOpenGames();
    }

    /**
     * Get user's game history
     */
    public function getUserGameHistory(User $user, int $limit = 10): array
    {
        return $this->gameRepository->findGamesForUser($user);
    }

    /**
     * Create a new game
     */
    private function createNewGame(User $player1): Game
    {
        $game = new Game();
        $game->setPlayer1($player1);
        $game->setStatus(Game::STATUS_OPEN);
        $game->setCreatedAt(new \DateTime());
        $game->setUpdatedAt(new \DateTime());
        
        $this->entityManager->persist($game);
        $this->entityManager->flush();
        
        return $game;
    }
}
