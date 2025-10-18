<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\Alerts\AlertEvaluator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:alerts:run', description: 'Run alert evaluation against active rules')]
final class AlertsRunCommand extends Command
{
    public function __construct(private readonly AlertEvaluator $evaluator)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->evaluator->run();
        $output->writeln(sprintf('<info>Alerts evaluated. %d alerts created.</info>', $count));
        return Command::SUCCESS;
    }
}
