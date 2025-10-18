<?php declare(strict_types=1);
namespace App\Command;
use App\Service\Alerts\AlertEvaluator;
use App\Service\Alerts\NotificationDispatcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'analytics:alerts:run', description: 'Evaluate analytics alert rules and dispatch notifications')]
final class AlertsRunCommand extends BaseCommand
{
    public function __construct(private readonly AlertEvaluator $evaluator, private readonly NotificationDispatcher $dispatcher) { parent::__construct(); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $from = new \DateTimeImmutable('-15 minutes'); $to = new \DateTimeImmutable('now');
        $results = $this->evaluator->evaluate($from, $to);
        foreach ($results as $r) {
            if (($r['matched'] ?? false) === true) {
                $rule = $r['rule']; $snap = $r['snapshot'];
                $this->dispatcher->dispatch($rule, sprintf('Rule "%s" matched on %s=%s', $rule->getCode(), $snap->getMetric(), (string)$snap->getValue()));
            }
        }
        $output->writeln('<info>Alerts evaluation finished.</info>'); return self::SUCCESS;
    }
}
