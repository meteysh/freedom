<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Interface CurrencyDataFetcherInterface
 */
interface CurrencyDataFetcherInterface
{
    public function fetch(
        string $currencyCode,
        string $internalCode,
        string $baseDay
    ): array;


    public function fetchByDate(string $date): array;

    public function getInternalCodeByISO(string $targetCode): ?string;
}
