<?php

namespace App\Controller;

use App\Service\ScoringService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LeaderboardController extends AbstractController
{
    #[Route('/leaderboard', name: 'leaderboard')]
    public function leaderboard(ScoringService $scoringService, Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $leaderboard = $scoringService->getLeaderboard($limit + 1); // Get one extra to check if there are more
        $hasMore = count($leaderboard) > $limit;
        
        if ($hasMore) {
            array_pop($leaderboard); // Remove the extra entry
        }

        $stats = $scoringService->getRankingStats();
        $userRanking = null;
        
        if ($this->getUser()) {
            $userRanking = $scoringService->getUserRankingPosition($this->getUser());
        }

        return $this->render('leaderboard/leaderboard.html.twig', [
            'leaderboard' => $leaderboard,
            'stats' => $stats,
            'userRanking' => $userRanking,
            'currentPage' => $page,
            'hasMore' => $hasMore,
            'nextPage' => $hasMore ? $page + 1 : null,
            'prevPage' => $page > 1 ? $page - 1 : null,
        ]);
    }

    #[Route('/leaderboard/api', name: 'leaderboard_api')]
    public function leaderboardApi(ScoringService $scoringService, Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 50;
        
        $leaderboard = $scoringService->getLeaderboard($limit);
        
        $data = array_map(function($snapshot) {
            return [
                'user' => [
                    'id' => $snapshot->getUser()->getId(),
                    'username' => $snapshot->getUser()->getUsername(),
                ],
                'points' => $snapshot->getPoints(),
                'wins' => $snapshot->getWins(),
                'losses' => $snapshot->getLosses(),
                'winRate' => $snapshot->getWinRate(),
                'timestamp' => $snapshot->getTimestamp()->format('c'),
            ];
        }, $leaderboard);

        return $this->json([
            'leaderboard' => $data,
            'page' => $page,
            'limit' => $limit,
        ]);
    }
}
