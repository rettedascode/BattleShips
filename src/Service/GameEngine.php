<?php

namespace App\Service;

use App\Entity\Board;
use App\Entity\Game;
use App\Entity\Move;
use App\Entity\User;

class GameEngine
{
    public const SHIP_TYPES = [
        'Carrier' => 5,
        'Battleship' => 4,
        'Cruiser' => 3,
        'Submarine' => 3,
        'Destroyer' => 2,
    ];

    public const FLEET_COMPOSITION = [
        'Carrier' => 1,
        'Battleship' => 1,
        'Cruiser' => 2,
        'Submarine' => 1,
        'Destroyer' => 1,
    ];

    /**
     * Validate ship placement
     */
    public function validateShipPlacement(array $ship, Board $board): array
    {
        $errors = [];

        // Check if ship type is valid
        if (!isset(self::SHIP_TYPES[$ship['type']])) {
            $errors[] = 'Invalid ship type';
            return $errors;
        }

        // Check if ship size matches type
        if ($ship['size'] !== self::SHIP_TYPES[$ship['type']]) {
            $errors[] = 'Ship size does not match type';
            return $errors;
        }

        // Check if ship is within board boundaries
        if (!$this->isShipWithinBounds($ship, $board)) {
            $errors[] = 'Ship is outside board boundaries';
            return $errors;
        }

        // Check for overlaps with existing ships
        if ($this->hasShipOverlap($ship, $board->getFleetJSON())) {
            $errors[] = 'Ship overlaps with existing ship';
            return $errors;
        }

        return $errors;
    }

    /**
     * Validate complete fleet placement
     */
    public function validateFleetPlacement(array $fleet, Board $board): array
    {
        $errors = [];

        // Check fleet composition
        $shipCounts = [];
        foreach ($fleet as $ship) {
            $type = $ship['type'];
            $shipCounts[$type] = ($shipCounts[$type] ?? 0) + 1;
        }

        foreach (self::FLEET_COMPOSITION as $type => $requiredCount) {
            $actualCount = $shipCounts[$type] ?? 0;
            if ($actualCount !== $requiredCount) {
                $errors[] = "Expected {$requiredCount} {$type}(s), found {$actualCount}";
            }
        }

        // Check each ship placement
        foreach ($fleet as $ship) {
            $shipErrors = $this->validateShipPlacement($ship, $board);
            $errors = array_merge($errors, $shipErrors);
        }

        return $errors;
    }

    /**
     * Process a move and return the result
     */
    public function processMove(Game $game, User $attacker, int $x, int $y): array
    {
        $opponent = $game->getOpponent($attacker);
        if (!$opponent) {
            return ['error' => 'No opponent found'];
        }

        $opponentBoard = $game->getBoardForUser($opponent);
        if (!$opponentBoard) {
            return ['error' => 'Opponent board not found'];
        }

        // Check if coordinates are valid
        if ($x < 0 || $x >= $opponentBoard->getWidth() || $y < 0 || $y >= $opponentBoard->getHeight()) {
            return ['error' => 'Invalid coordinates'];
        }

        // Check if move already exists
        $existingMoves = $game->getMoves()->filter(function(Move $move) use ($x, $y) {
            return $move->getX() === $x && $move->getY() === $y;
        });

        if (!$existingMoves->isEmpty()) {
            return ['error' => 'Move already made at these coordinates'];
        }

        // Check if there's a ship at the target coordinates
        $ship = $opponentBoard->getShipAt($x, $y);
        
        if ($ship) {
            // Hit!
            $result = $this->processHit($ship, $opponentBoard, $x, $y);
            return [
                'result' => $result['result'],
                'ship' => $result['ship'],
                'sunk' => $result['sunk'],
                'gameOver' => $result['gameOver']
            ];
        } else {
            // Miss
            return [
                'result' => Move::RESULT_MISS,
                'ship' => null,
                'sunk' => false,
                'gameOver' => false
            ];
        }
    }

    /**
     * Check if game is over
     */
    public function isGameOver(Game $game): bool
    {
        foreach ($game->getBoards() as $board) {
            if ($board->getAliveShipCount() === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get the winner of the game
     */
    public function getWinner(Game $game): ?User
    {
        foreach ($game->getBoards() as $board) {
            if ($board->getAliveShipCount() > 0) {
                return $board->getUser();
            }
        }
        
        return null;
    }

    /**
     * Get remaining ships for a user
     */
    public function getRemainingShips(Game $game, User $user): int
    {
        $board = $game->getBoardForUser($user);
        if (!$board) {
            return 0;
        }
        
        return $board->getAliveShipCount();
    }

    /**
     * Check if ship is within board boundaries
     */
    private function isShipWithinBounds(array $ship, Board $board): bool
    {
        foreach ($ship['cells'] as $cell) {
            $x = $cell[0];
            $y = $cell[1];
            
            if ($x < 0 || $x >= $board->getWidth() || $y < 0 || $y >= $board->getHeight()) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check for ship overlaps
     */
    private function hasShipOverlap(array $newShip, array $existingFleet): bool
    {
        foreach ($existingFleet as $existingShip) {
            foreach ($newShip['cells'] as $newCell) {
                foreach ($existingShip['cells'] as $existingCell) {
                    if ($newCell[0] === $existingCell[0] && $newCell[1] === $existingCell[1]) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Process a hit on a ship
     */
    private function processHit(array $ship, Board $board, int $x, int $y): array
    {
        // Mark the cell as hit
        $ship['hits'] = $ship['hits'] ?? [];
        $ship['hits'][] = [$x, $y];
        
        // Check if ship is sunk
        $isSunk = count($ship['hits']) >= $ship['size'];
        
        if ($isSunk) {
            $ship['sunk'] = true;
        }
        
        // Update the board
        $fleet = $board->getFleetJSON();
        foreach ($fleet as &$fleetShip) {
            if ($fleetShip['type'] === $ship['type'] && 
                $fleetShip['start']['x'] === $ship['start']['x'] && 
                $fleetShip['start']['y'] === $ship['start']['y']) {
                $fleetShip = $ship;
                break;
            }
        }
        $board->setFleetJSON($fleet);
        
        // Check if game is over
        $gameOver = $board->getAliveShipCount() === 0;
        
        return [
            'result' => $isSunk ? Move::RESULT_SUNK : Move::RESULT_HIT,
            'ship' => $ship,
            'sunk' => $isSunk,
            'gameOver' => $gameOver
        ];
    }
}
