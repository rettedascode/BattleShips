<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\User;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;

class TurnTimeoutService
{
    private const TURN_TIMEOUT_SECONDS = 60; // 1 minute

    public function __construct(
        private EntityManagerInterface $entityManager,
        private GameRepository $gameRepository,
        private RealtimeNotifier $realtimeNotifier,
        private ScoringService $scoringService
    ) {
    }

    /**
     * Check for timed out turns and process them
     */
    public function checkTimedOutTurns(): int
    {
        $timeoutThreshold = new \DateTime(sprintf('-%d seconds', self::TURN_TIMEOUT_SECONDS));
        $timedOutGames = $this->gameRepository->findGamesForTimeoutCheck($timeoutThreshold);
        
        $processedCount = 0;
        
        foreach ($timedOutGames as $game) {
            $this->processTimedOutGame($game);
            $processedCount++;
        }
        
        return $processedCount;
    }

    /**
     * Process a single timed out game
     */
    private function processTimedOutGame(Game $game): void
    {
        $timedOutUser = $game->getCurrentTurnUserId();
        
        if (!$timedOutUser) {
            return;
        }
        
        // Notify about timeout
        $this->realtimeNotifier->notifyTurnTimeout($game, $timedOutUser);
        
        // Apply forfeit penalty
        $this->scoringService->applyGameResult($game, null, $timedOutUser);
        
        // Set winner to opponent
        $opponent = $game->getOpponent($timedOutUser);
        if ($opponent) {
            $game->setWinnerUserId($opponent);
        }
        
        // End the game
        $game->setStatus(Game::STATUS_FINISHED);
        $game->setFinishedAt(new \DateTime());
        
        $this->entityManager->flush();
        
        // Notify game finished
        $this->realtimeNotifier->notifyGameFinished($game, $opponent);
    }

    /**
     * Get remaining time for current turn
     */
    public function getRemainingTurnTime(Game $game): int
    {
        if ($game->getStatus() !== Game::STATUS_IN_PROGRESS) {
            return 0;
        }
        
        $lastMove = $game->getMoves()->last();
        if (!$lastMove) {
            return self::TURN_TIMEOUT_SECONDS;
        }
        
        $elapsed = (new \DateTime())->getTimestamp() - $lastMove->getCreatedAt()->getTimestamp();
        $remaining = self::TURN_TIMEOUT_SECONDS - $elapsed;
        
        return max(0, $remaining);
    }

    /**
     * Check if turn is about to timeout
     */
    public function isTurnAboutToTimeout(Game $game, int $warningSeconds = 10): bool
    {
        $remaining = $this->getRemainingTurnTime($game);
        return $remaining > 0 && $remaining <= $warningSeconds;
    }
}
