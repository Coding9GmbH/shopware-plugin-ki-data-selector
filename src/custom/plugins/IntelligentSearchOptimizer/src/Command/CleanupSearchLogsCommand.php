<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Swag\IntelligentSearchOptimizer\Service\SearchLogCleanupService;

class CleanupSearchLogsCommand extends Command
{
    protected static $defaultName = 'search-optimizer:cleanup';

    private SearchLogCleanupService $cleanupService;

    public function __construct(SearchLogCleanupService $cleanupService)
    {
        $this->cleanupService = $cleanupService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('search-optimizer:cleanup')
            ->setDescription('Clean up old search log entries')
            ->addOption(
                'sales-channel',
                's',
                InputOption::VALUE_OPTIONAL,
                'Sales channel ID to use for configuration'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Show how many entries would be deleted without actually deleting them'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $salesChannelId = $input->getOption('sales-channel');
        $dryRun = $input->getOption('dry-run');

        $io->title('Search Log Cleanup');

        if ($dryRun) {
            $count = $this->cleanupService->getOldEntriesCount($salesChannelId);
            $io->success(sprintf('Would delete %d old search log entries', $count));
        } else {
            $io->writeln('Starting cleanup...');
            $deletedCount = $this->cleanupService->cleanup($salesChannelId);
            $io->success(sprintf('Successfully deleted %d old search log entries', $deletedCount));
        }

        return Command::SUCCESS;
    }
}