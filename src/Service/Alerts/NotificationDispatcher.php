<?php declare(strict_types=1);
namespace App\Service\Alerts;
use App\Entity\Alerts\AlertRule;
use Psr\Log\LoggerInterface;

final class NotificationDispatcher
{
    public function __construct(private readonly LoggerInterface $logger) {}
    public function dispatch(AlertRule $rule, string $message): void
    {
        $channels = $rule->getChannels(); if (empty($channels)) { $channels=['log']; }
        $this->logger->info('[ALERT] '.$message, ['rule'=>$rule->getCode(), 'channels'=>$channels]);
    }
}
