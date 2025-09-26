<?php

namespace App\Controller;

use App\Service\ScoringService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile')]
    public function profile(ScoringService $scoringService): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        $userRanking = $scoringService->getUserRankingPosition($user);
        $winRate = $scoringService->calculateWinRate($user);

        return $this->render('profile/profile.html.twig', [
            'user' => $user,
            'userRanking' => $userRanking,
            'winRate' => $winRate,
        ]);
    }

    #[Route('/profile/stats', name: 'profile_stats')]
    public function profileStats(ScoringService $scoringService): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        $stats = [
            'points' => $user->getPoints(),
            'wins' => $user->getWins(),
            'losses' => $user->getLosses(),
            'gamesPlayed' => $user->getGamesPlayed(),
            'hitCountTotal' => $user->getHitCountTotal(),
            'winRate' => $scoringService->calculateWinRate($user),
            'ranking' => $scoringService->getUserRankingPosition($user),
            'createdAt' => $user->getCreatedAt(),
            'updatedAt' => $user->getUpdatedAt(),
        ];

        return $this->json($stats);
    }
}
