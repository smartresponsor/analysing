<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Domain\Analytics;

use App\DomainInterface\Analytics\ClickhouseClientInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ClickhouseClient implements ClickhouseClientInterface
{
    private HttpClientInterface $http;
    private string $base;
    private string $user;
    private string $pass;

    public function __construct(string $base, string $user, string $pass, ?HttpClientInterface $http = null)
    {
        $this->base = rtrim($base, '/');
        $this->user = $user;
        $this->pass = $pass;
        $this->http = $http ?? HttpClient::create(['timeout' => 10.0]);
    }

    public function query(string $sql, array $param = []): array
    {
        if ('' === trim($sql)) {
            throw new \InvalidArgumentException('ClickHouse query SQL must not be empty.');
        }

        $response = $this->request($this->bind($sql, $param).' FORMAT JSON');

        try {
            $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('Invalid JSON response from ClickHouse query.', 0, $exception);
        }

        if (!is_array($json)) {
            throw new \RuntimeException('Invalid JSON response from ClickHouse query.');
        }

        return isset($json['data']) && is_array($json['data']) ? $json['data'] : [];
    }

    public function insertJsonEachRow(string $table, array $rows): void
    {
        if ([] === $rows) {
            return;
        }

        $payloadLines = [];
        foreach ($rows as $index => $row) {
            if (!is_array($row) || [] === $row) {
                throw new \InvalidArgumentException(sprintf('ClickHouse insert row at index %d must be a non-empty array.', (int) $index));
            }

            $payloadLines[] = $this->encodeInsertRow($row, (int) $index);
        }

        $sql = sprintf('INSERT INTO %s FORMAT JSONEachRow', $this->assertIdentifier($table));
        $this->request($sql.'
'.implode('
', $payloadLines).'
');
    }

    /**
     * @param array<string,mixed> $row
     */
    private function encodeInsertRow(array $row, int $index): string
    {
        foreach ($row as $key => $_) {
            if (!is_string($key) || '' === trim($key)) {
                throw new \InvalidArgumentException(sprintf('ClickHouse insert row at index %d contains an invalid field name.', $index));
            }
        }

        try {
            return json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException(sprintf('Unable to encode row for ClickHouse insert at index %d.', $index), 0, $exception);
        }
    }

    private function request(string $body): string
    {
        try {
            $response = $this->http->request('POST', $this->base, [
                'auth_basic' => [$this->user, $this->pass],
                'body' => $body,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);
        } catch (TransportExceptionInterface $exception) {
            throw new \RuntimeException('ClickHouse transport request failed.', 0, $exception);
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $exception) {
            throw new \RuntimeException('ClickHouse HTTP request failed.', 0, $exception);
        }

        if ($statusCode >= 400) {
            $excerpt = trim(substr($content, 0, 300));

            throw new \RuntimeException(sprintf('ClickHouse request failed with status %d.%s', $statusCode, '' !== $excerpt ? ' Response excerpt: '.$excerpt : ''));
        }

        return $content;
    }

    private function assertIdentifier(string $identifier): string
    {
        if (1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new \RuntimeException('Invalid ClickHouse identifier.');
        }

        return $identifier;
    }

    /**
     * @param array<string,bool|float|int|string|null> $param
     */
    private function bind(string $sql, array $param): string
    {
        $query = $sql;
        foreach ($param as $key => $value) {
            if (!is_string($key) || 1 !== preg_match('/^[A-Za-z0-9_]+$/', $key)) {
                continue;
            }

            $replacement = $this->encodeValue($value, $key);

            $query = str_replace('{'.$key.'}', $replacement, $query);
            $query = str_replace('{ '.$key.' }', $replacement, $query);
        }

        if (false === preg_match_all('/\{\s*([A-Za-z0-9_]+)\s*}/', $query, $matches)) {
            throw new \RuntimeException('Unable to inspect ClickHouse query placeholders.');
        }

        $missing = array_values(array_unique($matches[1]));
        if ([] !== $missing) {
            throw new \InvalidArgumentException('ClickHouse query has unresolved parameters: '.implode(', ', $missing));
        }

        return $query;
    }

    private function encodeValue(mixed $value, string $key): string
    {
        if (null === $value) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return "'".str_replace("'", "''", $value)."'";
        }

        throw new \InvalidArgumentException(sprintf('ClickHouse query parameter "%s" must be scalar or null.', $key));
    }
}
