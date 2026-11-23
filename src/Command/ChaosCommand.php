<?php

/**
 * `bin/console mockcard:chaos <target>` — runs MockCard's chaos scenarios against your webhook route
 * (e.g. in CI after starting the app) and exits non-zero when the run fails.
 *
 * Author: Arixeva Labs <hello@arixevalabs.com>
 */

declare(strict_types=1);

namespace MockCard\Symfony\Command;

use MockCard\ChaosRunner;
use MockCard\MockCardException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'mockcard:chaos', description: 'Run MockCard payment chaos scenarios against your webhook route')]
final class ChaosCommand extends Command
{
    /**
     * @param ChaosRunner $runner    The chaos runner.
     * @param string|null $projectId Default project from configuration.
     */
    public function __construct(private readonly ChaosRunner $runner, private readonly ?string $projectId)
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('target', InputArgument::REQUIRED, 'Your webhook URL, e.g. http://127.0.0.1:8000/webhooks/stripe')
            ->addOption('project', null, InputOption::VALUE_REQUIRED, 'Project ID (defaults to mockcard.project_id)')
            ->addOption('scenarios', null, InputOption::VALUE_REQUIRED, 'Comma-separated scenarios')
            ->addOption('min-score', null, InputOption::VALUE_REQUIRED, 'Fail below this score', '100')
            ->addOption('time-scale', null, InputOption::VALUE_REQUIRED, '0–1, shrinks waits', '0.2');
    }

    /**
     * @param InputInterface  $input  The input.
     * @param OutputInterface $output The output.
     * @return int 0 when the score meets --min-score, 1 otherwise.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $run = $this->runner->run(
                (string) ($input->getOption('project') ?: $this->projectId),
                (string) $input->getArgument('target'),
                array_filter(array_map('trim', explode(',', (string) $input->getOption('scenarios')))),
                [],
                (float) $input->getOption('time-scale'),
            );
        } catch (MockCardException $e) {
            $io->error("{$e->errorCode}: {$e->getMessage()}");
            return Command::FAILURE;
        }
        $io->table(['', 'Scenario', 'Check', 'Detail'], array_map(
            static fn (array $c) => [$c['passed'] ? '✓' : '✗', $c['scenario'], $c['name'], $c['detail']],
            $run['results']['checks'] ?? [],
        ));
        $io->writeln("Run {$run['id']}: {$run['score']}/100 ({$run['status']})");
        return ($run['score'] ?? 0) >= (int) $input->getOption('min-score') ? Command::SUCCESS : Command::FAILURE;
    }
}
