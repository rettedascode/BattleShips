// Battleship Game JavaScript

// Stimulus application
import { Application } from '@hotwired/stimulus';
import { definitionsFromContext } from '@hotwired/stimulus-webpack-helpers';

const application = Application.start();
const context = require.context('./controllers', true, /\.js$/);
application.load(definitionsFromContext(context));

// Global game utilities
window.BattleshipGame = {
    // Ship types and their sizes
    SHIP_TYPES: {
        'Carrier': 5,
        'Battleship': 4,
        'Cruiser': 3,
        'Submarine': 3,
        'Destroyer': 2
    },

    // Fleet composition
    FLEET_COMPOSITION: {
        'Carrier': 1,
        'Battleship': 1,
        'Cruiser': 2,
        'Submarine': 1,
        'Destroyer': 1
    },

    // Convert coordinates to display format
    coordinatesToDisplay: function(x, y) {
        return String.fromCharCode(65 + x) + (y + 1);
    },

    // Convert display format to coordinates
    displayToCoordinates: function(display) {
        const letter = display.charAt(0).toUpperCase();
        const number = parseInt(display.substring(1));
        return {
            x: letter.charCodeAt(0) - 65,
            y: number - 1
        };
    },

    // Generate ship cells
    generateShipCells: function(startX, startY, size, orientation) {
        const cells = [];
        for (let i = 0; i < size; i++) {
            if (orientation === 'H') {
                cells.push([startX + i, startY]);
            } else {
                cells.push([startX, startY + i]);
            }
        }
        return cells;
    },

    // Check if ship placement is valid
    validateShipPlacement: function(ship, board, existingFleet) {
        const errors = [];

        // Check boundaries
        for (const cell of ship.cells) {
            if (cell[0] < 0 || cell[0] >= board.width || cell[1] < 0 || cell[1] >= board.height) {
                errors.push('Ship extends outside board boundaries');
                return errors;
            }
        }

        // Check for overlaps
        for (const existingShip of existingFleet) {
            for (const newCell of ship.cells) {
                for (const existingCell of existingShip.cells) {
                    if (newCell[0] === existingCell[0] && newCell[1] === existingCell[1]) {
                        errors.push('Ship overlaps with existing ship');
                        return errors;
                    }
                }
            }
        }

        return errors;
    },

    // Check if fleet placement is complete
    isFleetComplete: function(fleet) {
        const shipCounts = {};
        
        for (const ship of fleet) {
            shipCounts[ship.type] = (shipCounts[ship.type] || 0) + 1;
        }

        for (const [type, required] of Object.entries(this.FLEET_COMPOSITION)) {
            if ((shipCounts[type] || 0) !== required) {
                return false;
            }
        }

        return true;
    },

    // Get ship at coordinates
    getShipAt: function(fleet, x, y) {
        return fleet.find(ship => 
            ship.cells.some(cell => cell[0] === x && cell[1] === y)
        );
    },

    // Check if coordinates have been attacked
    hasBeenAttacked: function(moves, x, y) {
        return moves.some(move => move.x === x && move.y === y);
    },

    // Get move result at coordinates
    getMoveResult: function(moves, x, y) {
        const move = moves.find(move => move.x === x && move.y === y);
        return move ? move.result : null;
    },

    // Format time remaining
    formatTimeRemaining: function(seconds) {
        if (seconds <= 0) return '0s';
        if (seconds < 60) return `${seconds}s`;
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes}m ${remainingSeconds}s`;
    },

    // Show notification
    showNotification: function(message, type = 'info') {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        const container = document.querySelector('main .container');
        if (container) {
            container.insertAdjacentHTML('afterbegin', alertHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
    },

    // Make API request
    apiRequest: async function(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const mergedOptions = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, mergedOptions);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Request failed');
            }
            
            return data;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    },

    // Polling utility
    startPolling: function(callback, interval = 3000) {
        const poll = () => {
            callback().catch(error => {
                console.error('Polling error:', error);
            });
        };

        poll(); // Initial call
        return setInterval(poll, interval);
    },

    // Stop polling
    stopPolling: function(intervalId) {
        if (intervalId) {
            clearInterval(intervalId);
        }
    },

    // Debounce function
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Throttle function
    throttle: function(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
};

// Initialize tooltips and popovers
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize Bootstrap popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Export for use in other modules
export default window.BattleshipGame;
