<?php

declare(strict_types=1);

namespace App\Analysing\Service\Config;

use App\Administering\Service\Config\AdministrationConfigApplyService;
use App\Administering\Service\Config\AdministrationConfigFileWriterService;
use App\Administering\Value\Config\ConfigToolDescriptor;
use App\Analysing\DTO\Config\AnalyticsEnvironmentConfigDataDTO;
use App\Analysing\Form\Config\AnalyticsAnalysingEnvironmentConfigFormType;
use App\Analysing\ServiceInterface\Config\AnalyticsAnalysingEnvironmentConfigServiceInterface;
use Symfony\Component\Yaml\Yaml;

final readonly class AnalyticsAnalysingEnvironmentConfigService implements AnalyticsAnalysingEnvironmentConfigServiceInterface
{
    public function __construct(
        private string $projectDir,
        private AdministrationConfigApplyService $applyService,
        private AdministrationConfigFileWriterService $fileWriter,
    ) {
    }

    public function descriptor(): ConfigToolDescriptor
    {
        return new ConfigToolDescriptor(
            applicationCode: 'Analysing',
            toolCode: 'analysing.environment',
            label: 'Analysing Environment',
            description: 'Safe runtime and connection flags stored in the Analysing component runtime manifest.',
            formClass: AnalyticsAnalysingEnvironmentConfigFormType::class,
            serviceClass: self::class,
            requiredPermission: 'administration.config.update',
            editableFields: [
                'clickhouseBase',
                'clickhouseUser',
                'idempotencyEnabled',
                'idempotencyRequired',
                'authRequired',
                'authPublicRead',
                'rateLimitEnabled',
                'rateLimitWindowSeconds',
                'rateLimitDefaultWriteLimit',
            ],
            sensitiveFields: [],
            readableFiles: ['config/component/analytics_runtime.yaml'],
            writableFiles: ['config/component/analytics_runtime.yaml'],
            metadata: [
                'section' => 'Configuration',
                'kind' => 'environment',
            ],
            secretNames: [],
            applyStrategy: 'component_runtime_yaml',
        );
    }

    public function loadData(): object
    {
        $data = new AnalyticsEnvironmentConfigDataDTO();
        $runtime = $this->runtimeManifest();
        $analytics = is_array($runtime['analytics'] ?? null) ? $runtime['analytics'] : [];

        $data->clickhouseBase = $this->stringValue($analytics['clickhouse_base'] ?? null, $data->clickhouseBase);
        $data->clickhouseUser = $this->stringValue($analytics['clickhouse_user'] ?? null, $data->clickhouseUser);
        $data->idempotencyEnabled = !empty($analytics['idempotency_enabled'] ?? false) ? '1' : '0';
        $data->idempotencyRequired = !empty($analytics['idempotency_required'] ?? false) ? '1' : '0';
        $data->authRequired = !empty($analytics['auth_required'] ?? false) ? '1' : '0';
        $data->authPublicRead = !empty($analytics['auth_public_read'] ?? false) ? '1' : '0';
        $data->rateLimitEnabled = !empty($analytics['rate_limit_enabled'] ?? false) ? '1' : '0';
        $data->rateLimitWindowSeconds = $this->stringValue($analytics['rate_limit_window_seconds'] ?? null, $data->rateLimitWindowSeconds);
        $data->rateLimitDefaultWriteLimit = $this->stringValue($analytics['rate_limit_default_write_limit'] ?? null, $data->rateLimitDefaultWriteLimit);

        return $data;
    }

    public function save(object $data, array $context = []): array
    {
        $payload = $this->assertData($data);
        $values = $this->stateRows($payload, 'pending');
        $masked = [
            'analytics_clickhouse_base' => $payload->clickhouseBase,
            'analytics_clickhouse_user' => $payload->clickhouseUser,
            'analytics_idempotency_enabled' => $payload->idempotencyEnabled,
            'analytics_idempotency_required' => $payload->idempotencyRequired,
            'analytics_auth_required' => $payload->authRequired,
            'analytics_auth_public_read' => $payload->authPublicRead,
            'analytics_rate_limit_enabled' => $payload->rateLimitEnabled,
            'analytics_rate_limit_window_seconds' => $payload->rateLimitWindowSeconds,
            'analytics_rate_limit_default_write_limit' => $payload->rateLimitDefaultWriteLimit,
        ];

        return $this->applyService->save($this->descriptor(), $this->actorIdentifier($context), $values, $masked, []);
    }

    public function apply(object $data, array $context = []): array
    {
        $payload = $this->assertData($data);
        $patch = $this->runtimePatch($payload);
        $write = $this->fileWriter->write(
            $this->projectDir.'/../Analysing',
            'config/component/analytics_runtime.yaml',
            $patch,
            $this->descriptor()->writableFiles,
        );

        $status = 'applied' === $write['status'] ? 'applied' : 'failed';
        $values = $this->stateRows($payload, $status);

        return $this->applyService->apply(
            $this->descriptor(),
            $this->actorIdentifier($context),
            $values,
            $patch,
            [],
            [[
                'path' => $write['path'],
                'backup_path' => $write['backup_path'],
                'status' => $write['status'],
                'message' => $write['message'],
            ]],
            [],
            'applied' === $write['status'] ? null : $write['message'],
            $status,
        );
    }

    private function assertData(object $data): AnalyticsEnvironmentConfigDataDTO
    {
        if (!$data instanceof AnalyticsEnvironmentConfigDataDTO) {
            throw new \InvalidArgumentException('Analysing environment config expects AnalyticsEnvironmentConfigDataDTO.');
        }

        return $data;
    }

    private function stringValue(mixed $value, string $default): string
    {
        return is_scalar($value) ? (string) $value : $default;
    }

    /** @param array<string,mixed> $context */
    private function actorIdentifier(array $context): string
    {
        $actor = $context['actor'] ?? null;

        return is_string($actor) && '' !== trim($actor) ? trim($actor) : 'system';
    }

    /** @return array<string, mixed> */
    private function runtimeManifest(): array
    {
        $path = $this->projectDir.'/../Analysing/config/component/analytics_runtime.yaml';
        $parsed = is_file($path) ? Yaml::parseFile($path) : [];
        if (!is_array($parsed)) {
            return [];
        }

        $runtime = [];
        foreach ($parsed as $key => $value) {
            if (is_string($key)) {
                $runtime[$key] = $value;
            }
        }

        return $runtime;
    }

    /**
     * @return array<string, mixed>
     */
    private function runtimePatch(AnalyticsEnvironmentConfigDataDTO $data): array
    {
        return [
            'analytics' => [
                'clickhouse_base' => $data->clickhouseBase,
                'clickhouse_user' => $data->clickhouseUser,
                'idempotency_enabled' => '1' === $data->idempotencyEnabled,
                'idempotency_required' => '1' === $data->idempotencyRequired,
                'auth_required' => '1' === $data->authRequired,
                'auth_public_read' => '1' === $data->authPublicRead,
                'rate_limit_enabled' => '1' === $data->rateLimitEnabled,
                'rate_limit_window_seconds' => (int) $data->rateLimitWindowSeconds,
                'rate_limit_default_write_limit' => (int) $data->rateLimitDefaultWriteLimit,
            ],
        ];
    }

    /**
     * @return array<string, array{fieldType:string, secret:bool, current:?string, pending:?string, masked:?string, status:string}>
     */
    private function stateRows(AnalyticsEnvironmentConfigDataDTO $data, string $status): array
    {
        return [
            'analytics_clickhouse_base' => ['fieldType' => 'string', 'secret' => false, 'current' => $data->clickhouseBase, 'pending' => $data->clickhouseBase, 'masked' => null, 'status' => $status],
            'analytics_clickhouse_user' => ['fieldType' => 'string', 'secret' => false, 'current' => $data->clickhouseUser, 'pending' => $data->clickhouseUser, 'masked' => null, 'status' => $status],
            'analytics_idempotency_enabled' => ['fieldType' => 'checkbox', 'secret' => false, 'current' => $data->idempotencyEnabled, 'pending' => $data->idempotencyEnabled, 'masked' => null, 'status' => $status],
            'analytics_idempotency_required' => ['fieldType' => 'checkbox', 'secret' => false, 'current' => $data->idempotencyRequired, 'pending' => $data->idempotencyRequired, 'masked' => null, 'status' => $status],
            'analytics_auth_required' => ['fieldType' => 'checkbox', 'secret' => false, 'current' => $data->authRequired, 'pending' => $data->authRequired, 'masked' => null, 'status' => $status],
            'analytics_auth_public_read' => ['fieldType' => 'checkbox', 'secret' => false, 'current' => $data->authPublicRead, 'pending' => $data->authPublicRead, 'masked' => null, 'status' => $status],
            'analytics_rate_limit_enabled' => ['fieldType' => 'checkbox', 'secret' => false, 'current' => $data->rateLimitEnabled, 'pending' => $data->rateLimitEnabled, 'masked' => null, 'status' => $status],
            'analytics_rate_limit_window_seconds' => ['fieldType' => 'integer', 'secret' => false, 'current' => $data->rateLimitWindowSeconds, 'pending' => $data->rateLimitWindowSeconds, 'masked' => null, 'status' => $status],
            'analytics_rate_limit_default_write_limit' => ['fieldType' => 'integer', 'secret' => false, 'current' => $data->rateLimitDefaultWriteLimit, 'pending' => $data->rateLimitDefaultWriteLimit, 'masked' => null, 'status' => $status],
        ];
    }
}
