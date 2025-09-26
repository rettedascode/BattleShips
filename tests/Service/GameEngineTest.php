<?php

namespace App\Tests\Service;

use App\Entity\Board;
use App\Entity\Game;
use App\Entity\User;
use App\Service\GameEngine;
use PHPUnit\Framework\TestCase;

class GameEngineTest extends TestCase
{
    private GameEngine $gameEngine;

    protected function setUp(): void
    {
        $this->gameEngine = new GameEngine();
    }

    public function testValidateShipPlacementValid(): void
    {
        $board = new Board();
        $board->setWidth(10);
        $board->setHeight(10);

        $ship = [
            'type' => 'Destroyer',
            'size' => 2,
            'start' => ['x' => 0, 'y' => 0],
            'orientation' => 'H',
            'cells' => [[0, 0], [1, 0]]
        ];

        $errors = $this->gameEngine->validateShipPlacement($ship, $board);
        $this->assertEmpty($errors);
    }

    public function testValidateShipPlacementOutOfBounds(): void
    {
        $board = new Board();
        $board->setWidth(10);
        $board->setHeight(10);

        $ship = [
            'type' => 'Destroyer',
            'size' => 2,
            'start' => ['x' => 9, 'y' => 0],
            'orientation' => 'H',
            'cells' => [[9, 0], [10, 0]] // x=10 is out of bounds
        ];

        $errors = $this->gameEngine->validateShipPlacement($ship, $board);
        $this->assertContains('Ship is outside board boundaries', $errors);
    }

    public function testValidateShipPlacementOverlap(): void
    {
        $board = new Board();
        $board->setWidth(10);
        $board->setHeight(10);

        $existingFleet = [
            [
                'type' => 'Destroyer',
                'size' => 2,
                'start' => ['x' => 0, 'y' => 0],
                'orientation' => 'H',
                'cells' => [[0, 0], [1, 0]]
            ]
        ];

        $board->setFleetJSON($existingFleet);

        $newShip = [
            'type' => 'Cruiser',
            'size' => 3,
            'start' => ['x' => 1, 'y' => 0],
            'orientation' => 'H',
            'cells' => [[1, 0], [2, 0], [3, 0]] // Overlaps at [1,0]
        ];

        $errors = $this->gameEngine->validateShipPlacement($newShip, $board);
        $this->assertContains('Ship overlaps with existing ship', $errors);
    }

    public function testValidateFleetPlacementComplete(): void
    {
        $board = new Board();
        $board->setWidth(10);
        $board->setHeight(10);

        $fleet = [
            [
                'type' => 'Carrier',
                'size' => 5,
                'start' => ['x' => 0, 'y' => 0],
                'orientation' => 'H',
                'cells' => [[0, 0], [1, 0], [2, 0], [3, 0], [4, 0]]
            ],
            [
                'type' => 'Battleship',
                'size' => 4,
                'start' => ['x' => 0, 'y' => 1],
                'orientation' => 'H',
                'cells' => [[0, 1], [1, 1], [2, 1], [3, 1]]
            ],
            [
                'type' => 'Cruiser',
                'size' => 3,
                'start' => ['x' => 0, 'y' => 2],
                'orientation' => 'H',
                'cells' => [[0, 2], [1, 2], [2, 2]]
            ],
            [
                'type' => 'Cruiser',
                'size' => 3,
                'start' => ['x' => 0, 'y' => 3],
                'orientation' => 'H',
                'cells' => [[0, 3], [1, 3], [2, 3]]
            ],
            [
                'type' => 'Submarine',
                'size' => 3,
                'start' => ['x' => 0, 'y' => 4],
                'orientation' => 'H',
                'cells' => [[0, 4], [1, 4], [2, 4]]
            ],
            [
                'type' => 'Destroyer',
                'size' => 2,
                'start' => ['x' => 0, 'y' => 5],
                'orientation' => 'H',
                'cells' => [[0, 5], [1, 5]]
            ]
        ];

        $errors = $this->gameEngine->validateFleetPlacement($fleet, $board);
        $this->assertEmpty($errors);
    }

    public function testValidateFleetPlacementIncomplete(): void
    {
        $board = new Board();
        $board->setWidth(10);
        $board->setHeight(10);

        $fleet = [
            [
                'type' => 'Carrier',
                'size' => 5,
                'start' => ['x' => 0, 'y' => 0],
                'orientation' => 'H',
                'cells' => [[0, 0], [1, 0], [2, 0], [3, 0], [4, 0]]
            ]
            // Missing other ships
        ];

        $errors = $this->gameEngine->validateFleetPlacement($fleet, $board);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Expected 1 Battleship(s), found 0', implode(', ', $errors));
    }

    public function testProcessMoveHit(): void
    {
        $game = new Game();
        $attacker = new User();
        $opponent = new User();
        
        $game->setPlayer1($attacker);
        $game->setPlayer2($opponent);

        $opponentBoard = new Board();
        $opponentBoard->setGame($game);
        $opponentBoard->setUser($opponent);
        $opponentBoard->setWidth(10);
        $opponentBoard->setHeight(10);
        $opponentBoard->setFleetJSON([
            [
                'type' => 'Destroyer',
                'size' => 2,
                'start' => ['x' => 0, 'y' => 0],
                'orientation' => 'H',
                'cells' => [[0, 0], [1, 0]]
            ]
        ]);

        $game->addBoard($opponentBoard);

        $result = $this->gameEngine->processMove($game, $attacker, 0, 0);
        
        $this->assertEquals('HIT', $result['result']);
        $this->assertTrue($result['ship'] !== null);
        $this->assertFalse($result['sunk']);
        $this->assertFalse($result['gameOver']);
    }

    public function testProcessMoveMiss(): void
    {
        $game = new Game();
        $attacker = new User();
        $opponent = new User();
        
        $game->setPlayer1($attacker);
        $game->setPlayer2($opponent);

        $opponentBoard = new Board();
        $opponentBoard->setGame($game);
        $opponentBoard->setUser($opponent);
        $opponentBoard->setWidth(10);
        $opponentBoard->setHeight(10);
        $opponentBoard->setFleetJSON([]);

        $game->addBoard($opponentBoard);

        $result = $this->gameEngine->processMove($game, $attacker, 0, 0);
        
        $this->assertEquals('MISS', $result['result']);
        $this->assertNull($result['ship']);
        $this->assertFalse($result['sunk']);
        $this->assertFalse($result['gameOver']);
    }

    public function testIsGameOver(): void
    {
        $game = new Game();
        
        $board1 = new Board();
        $board1->setGame($game);
        $board1->setFleetJSON([
            [
                'type' => 'Destroyer',
                'size' => 2,
                'sunk' => true
            ]
        ]);

        $board2 = new Board();
        $board2->setGame($game);
        $board2->setFleetJSON([
            [
                'type' => 'Destroyer',
                'size' => 2,
                'sunk' => true
            ]
        ]);

        $game->addBoard($board1);
        $game->addBoard($board2);

        $this->assertTrue($this->gameEngine->isGameOver($game));
    }

    public function testGetWinner(): void
    {
        $game = new Game();
        $winner = new User();
        $loser = new User();
        
        $game->setPlayer1($winner);
        $game->setPlayer2($loser);

        $winnerBoard = new Board();
        $winnerBoard->setGame($game);
        $winnerBoard->setUser($winner);
        $winnerBoard->setFleetJSON([
            [
                'type' => 'Destroyer',
                'size' => 2,
                'sunk' => false
            ]
        ]);

        $loserBoard = new Board();
        $loserBoard->setGame($game);
        $loserBoard->setUser($loser);
        $loserBoard->setFleetJSON([
            [
                'type' => 'Destroyer',
                'size' => 2,
                'sunk' => true
            ]
        ]);

        $game->addBoard($winnerBoard);
        $game->addBoard($loserBoard);

        $this->assertEquals($winner, $this->gameEngine->getWinner($game));
    }
}
