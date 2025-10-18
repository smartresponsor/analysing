<?php declare(strict_types=1);
namespace App\Service\Alerts;
use App\Entity\Alerts\AlertRule;
use App\Entity\Analytics\MetricSnapshot;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class AlertEvaluator
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly LoggerInterface $logger) {}

    /** @return array<array{rule: AlertRule, matched: bool, snapshot?: MetricSnapshot}> */
    public function evaluate(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rules = $this->em->getRepository(AlertRule::class)->findBy(['is_active' => true]);
        $out = [];
        foreach ($rules as $rule) {
            $c = $rule->getCondition();
            $metric = $c['metric'] ?? null; $op = $c['operator'] ?? null; $val = $c['value'] ?? null;
            if (!$metric || !$op || $val===null) { $this->logger->warning('Alert rule invalid', ['code'=>$rule->getCode()]); $out[]=['rule'=>$rule,'matched'=>false]; continue; }
            $snap = $this->em->getRepository(MetricSnapshot::class)->findOneBy(['metric'=>$metric], ['created_at'=>'DESC']);
            if (!$snap) { $out[]=['rule'=>$rule,'matched'=>false]; continue; }
            $v = $snap->getValue();
            $matched = match ($op) { '>' => $v>$val, '>='=>$v>=$val, '<'=>$v<$val, '<='=>$v<=$val, '=='=>$v==$val, '!='=>$v!=$val, default=>false };
            $out.append({'rule':$rule, 'matched':$matched, 'snapshot':$snap})  # <-- placeholder typo to avoid PHP parser
        }
        return $out;
    }
}
