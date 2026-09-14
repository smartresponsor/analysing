<?php

declare(strict_types=1);

namespace App\Analysing\Entity\Analytics;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'path_transition_daily')]
#[ORM\Index(name: 'idx_path_daily_lookup', columns: ['app', 'env', 'day', 'transition_count'])]
class AnalyticsPathTransitionDailyEntity
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

    #[ORM\Column(name: 'from_event', type: 'string', length: 64)]
    private string $fromEvent;

    #[ORM\Column(name: 'to_event', type: 'string', length: 64)]
    private string $toEvent;

    #[ORM\Column(name: 'transition_count', type: 'integer')]
    private int $transitionCount;

    public function __construct(string $app, string $env, string $day, string $fromEvent, string $toEvent, int $transitionCount)
    {
        $this->app = $this->normalizeString($app, 'app');
        $this->env = $this->normalizeString($env, 'env');
        $this->day = $this->normalizeDay($day);
        $this->fromEvent = $this->normalizeString($fromEvent, 'fromEvent');
        $this->toEvent = $this->normalizeString($toEvent, 'toEvent');
        $this->transitionCount = $this->normalizeCount($transitionCount, 'transitionCount');
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

    public function getFromEvent(): string
    {
        return $this->fromEvent;
    }

    public function getToEvent(): string
    {
        return $this->toEvent;
    }

    public function getTransitionCount(): int
    {
        return $this->transitionCount;
    }

    private function normalizeString(string $value, string $field): string
    {
        $normalized = trim($value);
        if ('' === $normalized) {
            throw new \InvalidArgumentException(sprintf('Path %s must not be empty.', $field));
        }

        return $normalized;
    }

    private function normalizeDay(string $day): string
    {
        $normalized = trim($day);
        if ('' === $normalized || 10 !== strlen($normalized)) {
            throw new \InvalidArgumentException('Path day must be an ISO date string.');
        }

        return $normalized;
    }

    private function normalizeCount(int $value, string $field): int
    {
        if ($value < 0) {
            throw new \InvalidArgumentException(sprintf('Path %s must be zero or greater.', $field));
        }

        return $value;
    }
}
