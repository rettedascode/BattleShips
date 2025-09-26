<?php

namespace App\Controller;

use App\Service\MatchmakingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LobbyController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('lobby');
        }

        return $this->render('lobby/home.html.twig');
    }

    #[Route('/lobby', name: 'lobby')]
    public function lobby(MatchmakingService $matchmakingService): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        $activeGame = $matchmakingService->getActiveGameForUser($user);
        $availableGames = $matchmakingService->getAvailableGames();
        $gameHistory = $matchmakingService->getUserGameHistory($user, 5);

        return $this->render('lobby/lobby.html.twig', [
            'user' => $user,
            'activeGame' => $activeGame,
            'availableGames' => $availableGames,
            'gameHistory' => $gameHistory,
        ]);
    }

    #[Route('/lobby/find-match', name: 'find_match', methods: ['POST'])]
    public function findMatch(MatchmakingService $matchmakingService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        if ($user->isBanned()) {
            return new JsonResponse(['error' => 'Account is banned'], 403);
        }

        try {
            $game = $matchmakingService->findOrCreateMatch($user);
            
            return new JsonResponse([
                'success' => true,
                'gameId' => $game->getId(),
                'status' => $game->getStatus(),
                'redirect' => $this->generateUrl('game_play', ['id' => $game->getId()])
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to find match'], 500);
        }
    }

    #[Route('/lobby/create-private', name: 'create_private_game', methods: ['POST'])]
    public function createPrivateGame(MatchmakingService $matchmakingService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        if ($user->isBanned()) {
            return new JsonResponse(['error' => 'Account is banned'], 403);
        }

        try {
            $game = $matchmakingService->createPrivateGame($user);
            
            return new JsonResponse([
                'success' => true,
                'gameId' => $game->getId(),
                'inviteUrl' => $this->generateUrl('join_private_game', ['id' => $game->getId()])
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to create private game'], 500);
        }
    }

    #[Route('/lobby/join/{id}', name: 'join_private_game', methods: ['POST'])]
    public function joinPrivateGame(int $id, MatchmakingService $matchmakingService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        if ($user->isBanned()) {
            return new JsonResponse(['error' => 'Account is banned'], 403);
        }

        try {
            $game = $matchmakingService->joinPrivateGame($user, $id);
            
            if (!$game) {
                return new JsonResponse(['error' => 'Game not found or unavailable'], 404);
            }
            
            return new JsonResponse([
                'success' => true,
                'gameId' => $game->getId(),
                'status' => $game->getStatus(),
                'redirect' => $this->generateUrl('game_play', ['id' => $game->getId()])
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to join game'], 500);
        }
    }
}
