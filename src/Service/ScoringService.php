<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\RankingSnapshot;
use App\Entity\User;
use App\Repository\RankingSnapshotRepository;
use Doctrine\ORM\EntityManagerInterface;

class ScoringService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RankingSnapshotRepository $rankingSnapshotRepository
    ) {
    }

    /**
     * Apply game result and update player statistics
     */
    public function applyGameResult(Game $game, ?User $winner, ?User $loser, int $remainingShipsWinner = 0, int $attackerHitCountLoser = 0): void
    {
        if ($winner && $loser) {
            // Normal game completion
            $this->updateWinnerStats($winner, $remainingShipsWinner);
            $this->updateLoserStats($loser, $attackerHitCountLoser);
        } else if ($loser) {
            // Forfeit/rage quit
            $this->updateForfeitStats($loser);
        }

        // Update game statistics
        if ($winner) {
            $winner->setGamesPlayed($winner->getGamesPlayed() + 1);
            $winner->setWins($winner->getWins() + 1);
        }
        
        if ($loser) {
            $loser->setGamesPlayed($loser->getGamesPlayed() + 1);
            $loser->setLosses($loser->getLosses() + 1);
        }

        $this->entityManager->flush();

        // Create ranking snapshot
        $this->createRankingSnapshot($winner);
        $this->createRankingSnapshot($loser);
    }

    /**
     * Update winner statistics
     */
    private function updateWinnerStats(User $winner, int $remainingShips): void
    {
        // Win: +20 points + (remaining own ships)
        $pointsEarned = 20 + $remainingShips;
        $winner->setPoints($winner->getPoints() + $pointsEarned);
    }

    /**
     * Update loser statistics
     */
    private function updateLoserStats(User $loser, int $hitCount): void
    {
        // Loss: +5 points if ≥5 hits, else 0
        if ($hitCount >= 5) {
            $loser->setPoints($loser->getPoints() + 5);
        }
        
        $loser->setHitCountTotal($loser->getHitCountTotal() + $hitCount);
    }

    /**
     * Update forfeit statistics
     */
    private function updateForfeitStats(User $forfeiter): void
    {
        // Rage-Quit (forfeit/timeout): −10 points
        $forfeiter->setPoints(max(0, $forfeiter->getPoints() - 10));
    }

    /**
     * Create ranking snapshot
     */
    private function createRankingSnapshot(?User $user): void
    {
        if (!$user) {
            return;
        }

        $snapshot = new RankingSnapshot();
        $snapshot->setUser($user);
        $snapshot->setPoints($user->getPoints());
        $snapshot->setWins($user->getWins());
        $snapshot->setLosses($user->getLosses());
        $snapshot->setTimestamp(new \DateTime());

        $this->entityManager->persist($snapshot);
    }

    /**
     * Get leaderboard
     */
    public function getLeaderboard(int $limit = 100): array
    {
        return $this->rankingSnapshotRepository->findLatestRankings($limit);
    }

    /**
     * Calculate win rate for a user
     */
    public function calculateWinRate(User $user): float
    {
        return $user->getWinRate();
    }

    /**
     * Get user ranking position
     */
    public function getUserRankingPosition(User $user): int
    {
        $leaderboard = $this->getLeaderboard(1000); // Get more entries for accurate ranking
        
        foreach ($leaderboard as $index => $snapshot) {
            if ($snapshot->getUser() === $user) {
                return $index + 1;
            }
        }
        
        return 0; // Not in top 1000
    }

    /**
     * Get ranking statistics
     */
    public function getRankingStats(): array
    {
        $leaderboard = $this->getLeaderboard(100);
        
        $totalPlayers = count($leaderboard);
        $totalPoints = array_sum(array_map(fn($snapshot) => $snapshot->getPoints(), $leaderboard));
        $averagePoints = $totalPlayers > 0 ? $totalPoints / $totalPlayers : 0;
        
        return [
            'totalPlayers' => $totalPlayers,
            'averagePoints' => round($averagePoints, 2),
            'topPlayer' => $leaderboard[0] ?? null,
        ];
    }
}
