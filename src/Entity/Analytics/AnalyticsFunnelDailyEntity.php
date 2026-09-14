<?php

declare(strict_types=1);

namespace App\Analysing\Entity\Analytics;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'aggregate_funnel_daily')]
#[ORM\Index(name: 'idx_funnel_daily_lookup', columns: ['app', 'env', 'day'])]
class AnalyticsFunnelDailyEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\Column(type: 'string', length: 10)]
    private string $day;

    #[ORM\Column(type: 'string', length: 64)]
    private string $app;

    #[ORM\Column(type: 'string', length: 64)]
    private string $env;

    #[ORM\Column(name: 'step_1', type: 'string', length: 64)]
    private string $step1;

    #[ORM\Column(name: 'step_2', type: 'string', length: 64)]
    private string $step2;

    #[ORM\Column(name: 'step_3', type: 'string', length: 64)]
    private string $step3;

    #[ORM\Column(name: 'step_4', type: 'string', length: 64, nullable: true)]
    private ?string $step4;

    #[ORM\Column(name: 'user_count', type: 'integer')]
    private int $userCount;

    public function __construct(string $app, string $env, string $day, string $step1, string $step2, string $step3, ?string $step4, int $userCount)
    {
        $this->app = $this->normalizeString($app, 'app');
        $this->env = $this->normalizeString($env, 'env');
        $this->day = $this->normalizeDay($day);
        $this->step1 = $this->normalizeString($step1, 'step1');
        $this->step2 = $this->normalizeString($step2, 'step2');
        $this->step3 = $this->normalizeString($step3, 'step3');
        $this->step4 = null === $step4 ? null : $this->normalizeOptionalString($step4, 'step4');
        $this->userCount = $this->normalizeCount($userCount, 'userCount');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDay(): string
    {
        return $this->day;
    }

    public function getApp(): string
    {
        return $this->app;
    }

    public function getEnv(): string
    {
        return $this->env;
    }

    public function getStep1(): string
    {
        return $this->step1;
    }

    public function getStep2(): string
    {
        return $this->step2;
    }

    public function getStep3(): string
    {
        return $this->step3;
    }

    public function getStep4(): ?string
    {
        return $this->step4;
    }

    public function getUserCount(): int
    {
        return $this->userCount;
    }

    private function normalizeString(string $value, string $field): string
    {
        $normalized = trim($value);
        if ('' === $normalized) {
            throw new \InvalidArgumentException(sprintf('Funnel %s must not be empty.', $field));
        }

        return $normalized;
    }

    private function normalizeOptionalString(string $value, string $field): string
    {
        $normalized = trim($value);
        if ('' === $normalized) {
            throw new \InvalidArgumentException(sprintf('Funnel %s must not be empty when provided.', $field));
        }

        return $normalized;
    }

    private function normalizeDay(string $day): string
    {
        $normalized = trim($day);
        if ('' === $normalized || 10 !== strlen($normalized)) {
            throw new \InvalidArgumentException('Funnel day must be an ISO date string.');
        }

        return $normalized;
    }

    private function normalizeCount(int $value, string $field): int
    {
        if ($value < 0) {
            throw new \InvalidArgumentException(sprintf('Funnel %s must be zero or greater.', $field));
        }

        return $value;
    }
}
