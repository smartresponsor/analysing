<?php

declare(strict_types=1);

namespace App\Analysing\Service\Analytics;

use App\Analysing\ServiceInterface\Analytics\AccessGuardInterface;
use Psr\Log\LoggerInterface;

final class AccessGuard implements AccessGuardInterface
{
    private const int MAX_ALLOW_LIST_SIZE = 512;
    private const int MAX_SUBJECT_LENGTH = 255;

    /** @var list<string> */
    private array $allow;

    /**
     * @param array<int, scalar> $allow
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        array $allow = [],
    ) {
        $this->allow = $this->normalizeAllowList($allow);
    }

    public function allow(string $subject): bool
    {
        $normalizedSubject = trim($subject);
        if ('' === $normalizedSubject) {
            $this->logger->warning('Analytics access guard rejected an empty subject.');

            return false;
        }

        if (strlen($normalizedSubject) > self::MAX_SUBJECT_LENGTH) {
            $this->logger->warning('Analytics access guard rejected an overlong subject.', [
                'subject_length' => strlen($normalizedSubject),
                'max_length' => self::MAX_SUBJECT_LENGTH,
            ]);

            throw new \InvalidArgumentException('Access guard subject exceeds the maximum allowed length.');
        }

        if ([] === $this->allow) {
            $this->logger->info('Analytics access guard allowed a subject because the allow-list is empty.', [
                'subject' => $normalizedSubject,
            ]);

            return true;
        }

        $allowed = in_array($normalizedSubject, $this->allow, true);
        if (!$allowed) {
            $this->logger->warning('Analytics access guard denied a subject not present in the allow-list.', [
                'subject' => $normalizedSubject,
                'allow_size' => count($this->allow),
            ]);
        }

        return $allowed;
    }

    /**
     * @param array<int, scalar> $allow
     *
     * @return list<string>
     */
    private function normalizeAllowList(array $allow): array
    {
        $normalized = [];

        foreach ($allow as $index => $entry) {
            if (!is_scalar($entry)) {
                $this->logger->warning('Analytics access guard ignored a non-scalar allow-list entry.', [
                    'entry_index' => $index,
                    'entry_type' => get_debug_type($entry),
                ]);
                continue;
            }

            $value = trim((string) $entry);
            if ('' === $value) {
                continue;
            }

            if (strlen($value) > self::MAX_SUBJECT_LENGTH) {
                $this->logger->warning('Analytics access guard ignored an overlong allow-list entry.', [
                    'entry_index' => $index,
                    'entry_length' => strlen($value),
                    'max_length' => self::MAX_SUBJECT_LENGTH,
                ]);
                continue;
            }

            $normalized[$value] = true;
            if (count($normalized) > self::MAX_ALLOW_LIST_SIZE) {
                $this->logger->warning('Analytics access guard truncated the allow-list because it exceeded the maximum size.', [
                    'max_size' => self::MAX_ALLOW_LIST_SIZE,
                ]);
                break;
            }
        }

        return array_keys($normalized);
    }
}
