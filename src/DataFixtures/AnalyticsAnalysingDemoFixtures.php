<?php

declare(strict_types=1);

namespace App\Analysing\DataFixtures;

use App\Analysing\Entity\Alerts\AnalyticsAlertLogEntity;
use App\Analysing\Entity\Alerts\AnalyticsAlertRuleEntity;
use App\Analysing\Entity\Analytics\AnalyticsDashboardMetricSnapshotEntity;
use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;
use App\Analysing\Entity\Analytics\AnalyticsFunnelDailyEntity;
use App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity;
use App\Analysing\Entity\Analytics\AnalyticsPathTransitionDailyEntity;
use App\Analysing\Entity\Analytics\AnalyticsRetentionCohortDailyEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

final class AnalyticsAnalysingDemoFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $apps = ['billing', 'catalog', 'vendoring'];
        $envs = ['dev', 'stage', 'prod'];

        foreach ($apps as $appIndex => $app) {
            $env = $envs[$appIndex % \count($envs)];

            for ($offset = 0; $offset < 3; ++$offset) {
                $day = (new \DateTimeImmutable(sprintf('-%d day', $offset)))->format('Y-m-d');
                $manager->persist(new AnalyticsRetentionCohortDailyEntity(
                    $app,
                    $env,
                    $faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
                    $offset,
                    120 + ($appIndex * 20) + ($offset * 7),
                ));
                $manager->persist(new AnalyticsPathTransitionDailyEntity(
                    $app,
                    $env,
                    $day,
                    'landing',
                    $this->scalarString($faker->randomElement(['catalog', 'checkout', 'register'])),
                    30 + ($appIndex * 10) + $offset,
                ));
                $manager->persist(new AnalyticsFunnelDailyEntity(
                    $app,
                    $env,
                    $day,
                    'landing',
                    'browse',
                    'convert',
                    0 === $offset ? 'purchase' : null,
                    10 + ($appIndex * 5) + $offset,
                ));
            }
        }

        for ($index = 1; $index <= 6; ++$index) {
            $metric = sprintf('%s.%s', $this->scalarString($faker->randomElement(['pageviews', 'signups', 'revenue', 'conversion'])), $this->scalarString($faker->randomElement(['daily', 'weekly'])));
            $start = new \DateTimeImmutable(sprintf('-%d day', 7 + $index));
            $end = $start->modify('+1 day');

            $manager->persist(new AnalyticsMetricSnapshotEntity(
                $metric,
                $faker->randomFloat(2, 10, 5000),
                $start,
                $end,
                [
                    'app' => $faker->randomElement($apps),
                    'env' => $faker->randomElement($envs),
                    'region' => $faker->randomElement(['us', 'eu', 'ua']),
                ],
            ));

            $manager->persist(new AnalyticsDashboardMetricSnapshotEntity(
                1000 + $index,
                $this->scalarString($faker->randomElement(['USD', 'EUR', 'UAH'])),
                new \DateTimeImmutable(sprintf('-%d day', $index)),
                50000 + ($index * 1200),
                40000 + ($index * 1000),
            ));
        }

        foreach (range(1, 4) as $index) {
            $rule = new AnalyticsAlertRuleEntity(
                sprintf('alert.%02d', $index),
                sprintf('Alert %02d', $index),
                [
                    'metric' => $faker->randomElement(['pageviews', 'revenue', 'conversion']),
                    'operator' => $faker->randomElement(['gt', 'lt', 'eq']),
                    'threshold' => $faker->numberBetween(10, 1000),
                ],
                [$faker->randomElement(['slack', 'email', 'pagerduty'])],
            );
            if (0 === $index % 2) {
                $rule->setActive(false);
            }
            $manager->persist($rule);

            $manager->persist(new AnalyticsAlertLogEntity(
                $faker->numberBetween(1000, 9999),
                $this->scalarString($faker->randomElement(['warning', 'critical', 'info'])),
                sprintf('Alert log %02d generated from demo fixture.', $index),
                [
                    'component' => $faker->randomElement($apps),
                    'severity' => $faker->randomElement(['warning', 'critical', 'info']),
                    'observed_at' => $faker->dateTimeBetween('-10 days', 'now')->format(DATE_ATOM),
                ],
            ));
        }

        for ($index = 1; $index <= 3; ++$index) {
            $job = new AnalyticsExportJobEntity(
                $this->scalarString($faker->randomElement(['vendor_export', 'dashboard_export', 'path_export'])),
                [
                    'app' => $faker->randomElement($apps),
                    'env' => $faker->randomElement($envs),
                    'requestedBy' => $faker->userName(),
                ],
            );

            if (0 === $index % 3) {
                $job->fail('Fixture export failed intentionally.');
                $job->incAttempts();
            } elseif (0 === $index % 2) {
                $job->start();
                $job->done();
            }

            $manager->persist($job);
        }

        $manager->flush();
    }

    /**
     * Normalize Faker scalar output to the string required by typed analytics fixtures.
     */
    private function scalarString(mixed $value): string
    {
        if (!is_scalar($value) && null !== $value) {
            throw new \UnexpectedValueException('Expected Faker to return a scalar or null value.');
        }

        return (string) $value;
    }
}
