<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

interface TransformerInterface
{
    /**
     * @template TRow of array<string,mixed>
     * @template TResult
     *
     * @param list<TRow>             $rows
     * @param callable(TRow):TResult $fn
     *
     * @return list<TResult>
     */
    public function map(array $rows, callable $fn): array;
}
