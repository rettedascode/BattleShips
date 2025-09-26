<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\User;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RealtimeNotifier
{
    public function __construct(
        private ?HubInterface $hub,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Notify game state update
     */
    public function notifyGameStateUpdate(Game $game): void
    {
        if (!$this->hub) {
            return; // Mercure not available
        }

        $update = new Update(
            sprintf('/games/%d', $game->getId()),
            json_encode([
                'type' => 'state.updated',
                'gameId' => $game->getId(),
                'status' => $game->getStatus(),
                'currentTurn' => $game->getCurrentTurnUserId()?->getId(),
                'timestamp' => (new \DateTime())->format('c')
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Notify move made
     */
    public function notifyMoveMade(Game $game, User $attacker, int $x, int $y, string $result): void
    {
        if (!$this->hub) {
            return;
        }

        $update = new Update(
            sprintf('/games/%d', $game->getId()),
            json_encode([
                'type' => 'move.made',
                'gameId' => $game->getId(),
                'attacker' => $attacker->getId(),
                'x' => $x,
                'y' => $y,
                'result' => $result,
                'timestamp' => (new \DateTime())->format('c')
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Notify game finished
     */
    public function notifyGameFinished(Game $game, ?User $winner): void
    {
        if (!$this->hub) {
            return;
        }

        $update = new Update(
            sprintf('/games/%d', $game->getId()),
            json_encode([
                'type' => 'game.finished',
                'gameId' => $game->getId(),
                'winner' => $winner?->getId(),
                'timestamp' => (new \DateTime())->format('c')
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Notify turn timeout
     */
    public function notifyTurnTimeout(Game $game, User $timedOutUser): void
    {
        if (!$this->hub) {
            return;
        }

        $update = new Update(
            sprintf('/games/%d', $game->getId()),
            json_encode([
                'type' => 'turn.timeout',
                'gameId' => $game->getId(),
                'timedOutUser' => $timedOutUser->getId(),
                'timestamp' => (new \DateTime())->format('c')
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Notify user-specific events
     */
    public function notifyUser(User $user, string $type, array $data = []): void
    {
        if (!$this->hub) {
            return;
        }

        $update = new Update(
            sprintf('/users/%d/notifications', $user->getId()),
            json_encode(array_merge([
                'type' => $type,
                'timestamp' => (new \DateTime())->format('c')
            ], $data))
        );

        $this->hub->publish($update);
    }

    /**
     * Check if Mercure is available
     */
    public function isMercureAvailable(): bool
    {
        return $this->hub !== null;
    }

    /**
     * Get polling fallback URL for game state
     */
    public function getGameStateUrl(Game $game): string
    {
        return $this->urlGenerator->generate('game_state', ['id' => $game->getId()]);
    }

    /**
     * Get polling fallback URL for user notifications
     */
    public function getUserNotificationsUrl(User $user): string
    {
        return $this->urlGenerator->generate('user_notifications', ['id' => $user->getId()]);
    }
}
