<?php

namespace App\Command;

use App\Service\TurnTimeoutService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:turn-timeout:check',
    description: 'Check for timed out turns and process them',
)]
class TurnTimeoutCommand extends Command
{
    public function __construct(
        private TurnTimeoutService $turnTimeoutService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Turn Timeout Checker');

        try {
            $processedCount = $this->turnTimeoutService->checkTimedOutTurns();
            
            if ($processedCount > 0) {
                $io->success("Processed {$processedCount} timed out games");
            } else {
                $io->info('No timed out games found');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error processing timed out turns: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
