<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Move;
use App\Repository\GameRepository;
use App\Service\GameEngine;
use App\Service\MatchmakingService;
use App\Service\RealtimeNotifier;
use App\Service\ScoringService;
use App\Service\TurnTimeoutService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{
    #[Route('/game/{id}', name: 'game_play')]
    public function play(int $id, GameRepository $gameRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        $game = $gameRepository->find($id);
        if (!$game || !$game->isPlayer($user)) {
            throw $this->createNotFoundException('Game not found');
        }

        return $this->render('game/play.html.twig', [
            'game' => $game,
            'user' => $user,
        ]);
    }

    #[Route('/game/{id}/state', name: 'game_state')]
    public function getGameState(int $id, GameRepository $gameRepository, TurnTimeoutService $timeoutService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        $game = $gameRepository->find($id);
        if (!$game || !$game->isPlayer($user)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        $opponent = $game->getOpponent($user);
        $userBoard = $game->getBoardForUser($user);
        $opponentBoard = $game->getBoardForUser($opponent);

        $state = [
            'gameId' => $game->getId(),
            'status' => $game->getStatus(),
            'currentTurn' => $game->getCurrentTurnUserId()?->getId(),
            'isCurrentTurn' => $game->isCurrentTurn($user),
            'remainingTime' => $timeoutService->getRemainingTurnTime($game),
            'userBoard' => $this->serializeBoard($userBoard, true),
            'opponentBoard' => $this->serializeBoard($opponentBoard, false),
            'moves' => $this->serializeMoves($game->getMoves()->toArray()),
            'winner' => $game->getWinnerUserId()?->getId(),
        ];

        return new JsonResponse($state);
    }

    #[Route('/game/{id}/place-ships', name: 'place_ships', methods: ['POST'])]
    public function placeShips(int $id, Request $request, GameRepository $gameRepository, GameEngine $gameEngine, EntityManagerInterface $entityManager, RealtimeNotifier $realtimeNotifier): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        $game = $gameRepository->find($id);
        if (!$game || !$game->isPlayer($user)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        if ($game->getStatus() !== Game::STATUS_PLACEMENT) {
            return new JsonResponse(['error' => 'Game not in placement phase'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $fleet = $data['fleet'] ?? [];

        if (empty($fleet)) {
            return new JsonResponse(['error' => 'No fleet provided'], 400);
        }

        // Get or create user's board
        $userBoard = $game->getBoardForUser($user);
        if (!$userBoard) {
            $userBoard = new \App\Entity\Board();
            $userBoard->setGame($game);
            $userBoard->setUser($user);
            $game->addBoard($userBoard);
        }

        // Validate fleet placement
        $errors = $gameEngine->validateFleetPlacement($fleet, $userBoard);
        if (!empty($errors)) {
            return new JsonResponse(['error' => 'Invalid fleet placement', 'details' => $errors], 400);
        }

        // Set fleet
        $userBoard->setFleetJSON($fleet);
        $userBoard->setPlacedAt(new \DateTime());

        // Check if both players have placed ships
        $allBoardsPlaced = true;
        foreach ($game->getBoards() as $board) {
            if (!$board->isPlaced()) {
                $allBoardsPlaced = false;
                break;
            }
        }

        if ($allBoardsPlaced) {
            $game->setStatus(Game::STATUS_IN_PROGRESS);
            $game->setCurrentTurnUserId($game->getPlayer1()); // Player1 starts
        }

        $entityManager->flush();

        // Notify real-time update
        $realtimeNotifier->notifyGameStateUpdate($game);

        return new JsonResponse(['success' => true, 'gameStatus' => $game->getStatus()]);
    }

    #[Route('/game/{id}/move', name: 'make_move', methods: ['POST'])]
    public function makeMove(int $id, Request $request, GameRepository $gameRepository, GameEngine $gameEngine, EntityManagerInterface $entityManager, RealtimeNotifier $realtimeNotifier, ScoringService $scoringService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        $game = $gameRepository->find($id);
        if (!$game || !$game->isPlayer($user)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        if ($game->getStatus() !== Game::STATUS_IN_PROGRESS) {
            return new JsonResponse(['error' => 'Game not in progress'], 400);
        }

        if (!$game->isCurrentTurn($user)) {
            return new JsonResponse(['error' => 'Not your turn'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $x = $data['x'] ?? null;
        $y = $data['y'] ?? null;

        if ($x === null || $y === null) {
            return new JsonResponse(['error' => 'Invalid coordinates'], 400);
        }

        // Process the move
        $result = $gameEngine->processMove($game, $user, $x, $y);
        
        if (isset($result['error'])) {
            return new JsonResponse(['error' => $result['error']], 400);
        }

        // Create move record
        $move = new Move();
        $move->setGame($game);
        $move->setAttackerUser($user);
        $move->setX($x);
        $move->setY($y);
        $move->setResult($result['result']);
        $move->setCreatedAt(new \DateTime());
        $move->setTurnIndex($game->getMoves()->count() + 1);

        $entityManager->persist($move);
        $game->addMove($move);

        // Switch turns if not a hit
        if ($result['result'] === Move::RESULT_MISS) {
            $game->setCurrentTurnUserId($game->getOpponent($user));
        }

        // Check for game over
        if ($result['gameOver'] || $gameEngine->isGameOver($game)) {
            $winner = $gameEngine->getWinner($game);
            $game->setWinnerUserId($winner);
            $game->setStatus(Game::STATUS_FINISHED);
            $game->setFinishedAt(new \DateTime());

            // Apply scoring
            $loser = $game->getOpponent($winner);
            $remainingShips = $gameEngine->getRemainingShips($game, $winner);
            $hitCount = $gameEngine->getRemainingShips($game, $loser);
            $scoringService->applyGameResult($game, $winner, $loser, $remainingShips, $hitCount);

            $realtimeNotifier->notifyGameFinished($game, $winner);
        }

        $entityManager->flush();

        // Notify real-time updates
        $realtimeNotifier->notifyMoveMade($game, $user, $x, $y, $result['result']);
        $realtimeNotifier->notifyGameStateUpdate($game);

        return new JsonResponse([
            'success' => true,
            'result' => $result['result'],
            'ship' => $result['ship'],
            'sunk' => $result['sunk'],
            'gameOver' => $result['gameOver'],
            'winner' => $game->getWinnerUserId()?->getId(),
        ]);
    }

    #[Route('/game/{id}/surrender', name: 'surrender_game', methods: ['POST'])]
    public function surrender(int $id, GameRepository $gameRepository, MatchmakingService $matchmakingService, ScoringService $scoringService, EntityManagerInterface $entityManager, RealtimeNotifier $realtimeNotifier): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        $game = $gameRepository->find($id);
        if (!$game || !$game->isPlayer($user)) {
            return new JsonResponse(['error' => 'Game not found'], 404);
        }

        if ($game->getStatus() !== Game::STATUS_IN_PROGRESS) {
            return new JsonResponse(['error' => 'Game not in progress'], 400);
        }

        // Set winner to opponent
        $opponent = $game->getOpponent($user);
        $game->setWinnerUserId($opponent);
        $game->setStatus(Game::STATUS_FINISHED);
        $game->setFinishedAt(new \DateTime());

        // Apply forfeit penalty
        $scoringService->applyGameResult($game, $opponent, $user);

        $entityManager->flush();

        // Notify real-time updates
        $realtimeNotifier->notifyGameFinished($game, $opponent);

        return new JsonResponse(['success' => true, 'redirect' => $this->generateUrl('lobby')]);
    }

    private function serializeBoard($board, bool $isOwnBoard): array
    {
        if (!$board) {
            return null;
        }

        $data = [
            'width' => $board->getWidth(),
            'height' => $board->getHeight(),
            'fleet' => $board->getFleetJSON(),
            'placed' => $board->isPlaced(),
        ];

        if ($isOwnBoard) {
            // Show full fleet for own board
            return $data;
        } else {
            // Only show hit/miss information for opponent's board
            $data['fleet'] = array_map(function($ship) {
                return [
                    'type' => $ship['type'],
                    'size' => $ship['size'],
                    'hits' => $ship['hits'] ?? [],
                    'sunk' => $ship['sunk'] ?? false,
                ];
            }, $board->getFleetJSON());
            
            return $data;
        }
    }

    private function serializeMoves(array $moves): array
    {
        return array_map(function(Move $move) {
            return [
                'id' => $move->getId(),
                'attacker' => $move->getAttackerUser()->getId(),
                'x' => $move->getX(),
                'y' => $move->getY(),
                'result' => $move->getResult(),
                'createdAt' => $move->getCreatedAt()->format('c'),
                'turnIndex' => $move->getTurnIndex(),
            ];
        }, $moves);
    }
}
