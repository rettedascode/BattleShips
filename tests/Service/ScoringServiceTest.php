<?php

namespace App\Tests\Service;

use App\Entity\Game;
use App\Entity\User;
use App\Service\ScoringService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\RankingSnapshotRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ScoringServiceTest extends TestCase
{
    private ScoringService $scoringService;
    private MockObject $entityManager;
    private MockObject $rankingSnapshotRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->rankingSnapshotRepository = $this->createMock(RankingSnapshotRepository::class);
        
        $this->scoringService = new ScoringService(
            $this->entityManager,
            $this->rankingSnapshotRepository
        );
    }

    public function testApplyGameResultWinner(): void
    {
        $game = new Game();
        $winner = new User();
        $loser = new User();
        
        $winner->setPoints(100);
        $winner->setWins(5);
        $winner->setLosses(3);
        $winner->setGamesPlayed(8);
        $winner->setHitCountTotal(50);

        $loser->setPoints(80);
        $loser->setWins(3);
        $loser->setLosses(5);
        $loser->setGamesPlayed(8);
        $loser->setHitCountTotal(40);

        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->exactly(2))->method('persist');

        $this->scoringService->applyGameResult($game, $winner, $loser, 2, 6);

        // Winner should get 20 + 2 = 22 points
        $this->assertEquals(122, $winner->getPoints());
        $this->assertEquals(6, $winner->getWins());
        $this->assertEquals(9, $winner->getGamesPlayed());
        $this->assertEquals(50, $winner->getHitCountTotal());

        // Loser should get 5 points (6 hits >= 5)
        $this->assertEquals(85, $loser->getPoints());
        $this->assertEquals(6, $loser->getLosses());
        $this->assertEquals(9, $loser->getGamesPlayed());
        $this->assertEquals(46, $loser->getHitCountTotal());
    }

    public function testApplyGameResultLoserLowHits(): void
    {
        $game = new Game();
        $winner = new User();
        $loser = new User();
        
        $winner->setPoints(100);
        $loser->setPoints(80);

        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->exactly(2))->method('persist');

        $this->scoringService->applyGameResult($game, $winner, $loser, 2, 3);

        // Winner should get 20 + 2 = 22 points
        $this->assertEquals(122, $winner->getPoints());

        // Loser should get 0 points (3 hits < 5)
        $this->assertEquals(80, $loser->getPoints());
    }

    public function testApplyGameResultForfeit(): void
    {
        $game = new Game();
        $forfeiter = new User();
        $forfeiter->setPoints(50);

        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->once())->method('persist');

        $this->scoringService->applyGameResult($game, null, $forfeiter);

        // Forfeiter should lose 10 points (minimum 0)
        $this->assertEquals(40, $forfeiter->getPoints());
    }

    public function testApplyGameResultForfeitMinimumPoints(): void
    {
        $game = new Game();
        $forfeiter = new User();
        $forfeiter->setPoints(5);

        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->once())->method('persist');

        $this->scoringService->applyGameResult($game, null, $forfeiter);

        // Forfeiter should have minimum 0 points
        $this->assertEquals(0, $forfeiter->getPoints());
    }

    public function testGetLeaderboard(): void
    {
        $mockSnapshots = [
            (object)['user' => 'user1', 'points' => 100],
            (object)['user' => 'user2', 'points' => 80]
        ];

        $this->rankingSnapshotRepository
            ->expects($this->once())
            ->method('findLatestRankings')
            ->with(100)
            ->willReturn($mockSnapshots);

        $result = $this->scoringService->getLeaderboard(100);
        
        $this->assertEquals($mockSnapshots, $result);
    }

    public function testCalculateWinRate(): void
    {
        $user = new User();
        $user->setWins(6);
        $user->setLosses(4);
        $user->setGamesPlayed(10);

        $winRate = $this->scoringService->calculateWinRate($user);
        
        $this->assertEquals(0.6, $winRate);
    }

    public function testCalculateWinRateZeroGames(): void
    {
        $user = new User();
        $user->setWins(0);
        $user->setLosses(0);
        $user->setGamesPlayed(0);

        $winRate = $this->scoringService->calculateWinRate($user);
        
        $this->assertEquals(0.0, $winRate);
    }

    public function testGetUserRankingPosition(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');

        $mockSnapshot1 = $this->createMock(\App\Entity\RankingSnapshot::class);
        $mockSnapshot1->method('getUser')->willReturn($user);
        
        $mockSnapshot2 = $this->createMock(\App\Entity\RankingSnapshot::class);
        $mockSnapshot2->method('getUser')->willReturn(new User());

        $mockSnapshots = [$mockSnapshot1, $mockSnapshot2];

        $this->rankingSnapshotRepository
            ->expects($this->once())
            ->method('findLatestRankings')
            ->with(1000)
            ->willReturn($mockSnapshots);

        $position = $this->scoringService->getUserRankingPosition($user);
        
        $this->assertEquals(1, $position);
    }

    public function testGetUserRankingPositionNotFound(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');

        $mockSnapshot = $this->createMock(\App\Entity\RankingSnapshot::class);
        $mockSnapshot->method('getUser')->willReturn(new User());

        $mockSnapshots = [$mockSnapshot];

        $this->rankingSnapshotRepository
            ->expects($this->once())
            ->method('findLatestRankings')
            ->with(1000)
            ->willReturn($mockSnapshots);

        $position = $this->scoringService->getUserRankingPosition($user);
        
        $this->assertEquals(0, $position);
    }

    public function testGetRankingStats(): void
    {
        $mockSnapshot = $this->createMock(\App\Entity\RankingSnapshot::class);
        $mockSnapshot->method('getPoints')->willReturn(100);
        
        $mockSnapshots = [
            $mockSnapshot,
            $mockSnapshot,
            $mockSnapshot
        ];

        $this->rankingSnapshotRepository
            ->expects($this->once())
            ->method('findLatestRankings')
            ->with(100)
            ->willReturn($mockSnapshots);

        $stats = $this->scoringService->getRankingStats();
        
        $this->assertEquals(3, $stats['totalPlayers']);
        $this->assertEquals(100.0, $stats['averagePoints']);
        $this->assertNotNull($stats['topPlayer']);
    }
}
