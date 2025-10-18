<?php
declare(strict_types=1);

namespace App\Service\Alerts;

use App\Entity\Alerts\AlertRule;
use App\Entity\Alerts\AlertLog;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

final class AlertEvaluator
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Connection $db,
        private readonly NotificationDispatcher $notify
    ) {}

    /**
     * Evaluate all active rules for the last day.
     * Returns number of alert logs created.
     */
    public function run(): int
    {
        $repo = $this->em->getRepository(AlertRule::class);
        $rules = $repo->findBy(['active' => true]);
        if (!$rules) return 0;

        $count = 0;
        foreach ($rules as $rule) {
            $type = $rule->getType();
            $threshold = $rule->getThreshold();

            switch ($type) {
                case 'negative_margin':
                    $count += $this->checkNegativeMargin($rule, $threshold);
                    break;
                case 'high_risk':
                    $count += $this->checkHighRisk($rule, $threshold);
                    break;
                case 'limit_breach':
                    $count += $this->checkLimitBreach($rule, $threshold);
                    break;
                default:
                    // ignore unknown
                    break;
            }
        }
        return $count;
    }

    private function checkNegativeMargin(AlertRule $rule, float $threshold): int
    {
        // margin = net/gross * 100; detect margin <= threshold (usually 0)
        $sql = "SELECT vendor_id,
                       SUM(net_minor)   AS net,
                       SUM(gross_minor) AS gross
                  FROM metric_snapshot
                 WHERE date >= DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)
              GROUP BY vendor_id";
        $rows = $this->db->fetchAllAssociative($sql);
        $count = 0;

        foreach ($rows as $r) {
            $g = (int)$r['gross'];
            $n = (int)$r['net'];
            $margin = $g > 0 ? ($n / $g) * 100.0 : -100.0;
            if ($margin <= $threshold) {
                $msg = $rule->getMessage() ?? sprintf('Negative margin %.2f%% for vendor %d', $margin, (int)$r['vendor_id']);
                $this->logAndNotify((int)$r['vendor_id'], 'negative_margin', $msg, ['margin_pct' => round($margin,2)]);
                $count++;
            }
        }
        return $count;
    }

    private function checkHighRisk(AlertRule $rule, float $threshold): int
    {
        // assume MetricSnapshot.risk_score exists per day
        $sql = "SELECT vendor_id, MAX(CAST(risk_score AS DECIMAL(6,2))) AS risk
                  FROM metric_snapshot
                 WHERE date >= DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)
              GROUP BY vendor_id";
        $rows = $this->db->fetchAllAssociative($sql);
        $count = 0;
        foreach ($rows as $r) {
            $risk = (float)$r['risk'];
            if ($risk >= $threshold) {
                $msg = $rule->getMessage() ?? sprintf('High risk %.2f for vendor %d', $risk, (int)$r['vendor_id']);
                $this->logAndNotify((int)$r['vendor_id'], 'high_risk', $msg, ['risk' => $risk]);
                $count++;
            }
        }
        return $count;
    }

    private function checkLimitBreach(AlertRule $rule, float $threshold): int
    {
        // threshold treated as daily amount (minor units) exceeding
        $sql = "SELECT l.reference_id AS vendor_id, COALESCE(SUM(l.amount_minor),0) AS amt
                  FROM ledger_entry l
                 WHERE l.created_at >= CURRENT_DATE()
              GROUP BY l.reference_id";
        $rows = $this->db->fetchAllAssociative($sql);
        $count = 0;
        foreach ($rows as $r) {
            $amt = (int)$r['amt'];
            if ($amt > (int)$threshold) {
                $msg = $rule->getMessage() ?? sprintf('Daily limit breach: %d > %d for vendor %d', $amt, (int)$threshold, (int)$r['vendor_id']);
                $this->logAndNotify((int)$r['vendor_id'], 'limit_breach', $msg, ['amount_minor' => $amt]);
                $count++;
            }
        }
        return $count;
    }

    private function logAndNotify(int $vendorId, string $type, string $message, array $ctx = []): void
    {
        $log = new AlertLog($vendorId, $type, $message, $ctx);
        $this->em->persist($log);
        $this->notify->send($message, ['vendorId' => $vendorId, 'type' => $type, 'context' => $ctx]);
        $this->em->flush();
    }
}
