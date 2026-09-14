<?php

declare(strict_types=1);

namespace App\Analysing\Entity\Analytics;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'retention_cohort_daily')]
#[ORM\Index(name: 'idx_retention_daily_lookup', columns: ['app', 'env', 'cohort', 'day_offset'])]
class AnalyticsRetentionCohortDailyEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\Column(type: 'string', length: 10)]
    private string $cohort;

    #[ORM\Column(name: 'day_offset', type: 'integer')]
    private int $dayOffset;

    #[ORM\Column(type: 'string', length: 64)]
    private string $app;

    #[ORM\Column(type: 'string', length: 64)]
    private string $env;

    #[ORM\Column(name: 'active_user', type: 'integer')]
    private int $activeUser;

    public function __construct(string $app, string $env, string $cohort, int $dayOffset, int $activeUser)
    {
        $this->app = $this->normalizeString($app, 'app');
        $this->env = $this->normalizeString($env, 'env');
        $this->cohort = $this->normalizeDay($cohort, 'cohort');
        $this->dayOffset = $this->normalizeCount($dayOffset, 'dayOffset');
        $this->activeUser = $this->normalizeCount($activeUser, 'activeUser');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCohort(): string
    {
        return $this->cohort;
    }

    public function getDayOffset(): int
    {
        return $this->dayOffset;
    }

    public function getApp(): string
    {
        return $this->app;
    }

    public function getEnv(): string
    {
        return $this->env;
    }

    public function getActiveUser(): int
    {
        return $this->activeUser;
    }

    private function normalizeString(string $value, string $field): string
    {
        $normalized = trim($value);
        if ('' === $normalized) {
            throw new \InvalidArgumentException(sprintf('Retention %s must not be empty.', $field));
        }

        return $normalized;
    }

    private function normalizeDay(string $value, string $field): string
    {
        $normalized = trim($value);
        if ('' === $normalized || 10 !== strlen($normalized)) {
            throw new \InvalidArgumentException(sprintf('Retention %s must be an ISO date string.', $field));
        }

        return $normalized;
    }

    private function normalizeCount(int $value, string $field): int
    {
        if ($value < 0) {
            throw new \InvalidArgumentException(sprintf('Retention %s must be zero or greater.', $field));
        }

        return $value;
    }
}
