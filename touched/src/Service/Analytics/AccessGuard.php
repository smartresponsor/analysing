<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\AccessGuardInterface;
use Psr\Log\LoggerInterface;

final class AccessGuard implements AccessGuardInterface
{
    /** @var list<string> */
    private array $allow;

    public function __construct(
        private readonly LoggerInterface $logger,
        array $allow = [],
    ) {
        $this->allow = $this->normalizeAllowList($allow);
    }

    public function allow(string $subject): bool
    {
        $normalizedSubject = trim($subject);
        if ($normalizedSubject === '') {
            $this->logger->warning('Analytics access guard rejected an empty subject.');

            return false;
        }

        if ($this->allow === []) {
            return true;
        }

        return in_array($normalizedSubject, $this->allow, true);
    }

    /**
     * @param array<mixed> $allow
     * @return list<string>
     */
    private function normalizeAllowList(array $allow): array
    {
        $normalized = [];

        foreach ($allow as $entry) {
            if (!is_scalar($entry)) {
                $this->logger->warning('Analytics access guard ignored a non-scalar allow-list entry.', [
                    'entry_type' => get_debug_type($entry),
                ]);
                continue;
            }

            $value = trim((string) $entry);
            if ($value === '') {
                continue;
            }

            $normalized[] = $value;
        }

        return array_values(array_unique($normalized));
    }
}
