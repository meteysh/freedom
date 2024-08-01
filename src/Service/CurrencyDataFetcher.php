<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\RateDTO;
use DateTime;
use Exception;
use SimpleXMLElement;
use SymfonyBundles\RedisBundle\Redis\ClientInterface;

class CurrencyDataFetcher
{
    private const string CURRENCY_LIST_URL = 'https://cbr.ru/scripts/XML_valFull.asp';
    private const string CURRENCY_DELTA_URL = 'https://cbr.ru/scripts/XML_dynamic.asp?date_req1=%s&date_req2=%s&VAL_NM_RQ=%s';
    private const string CURRENCY_DATE_URL = 'https://cbr.ru/scripts/XML_daily.asp?date_req=%s';
    private const string CURRENCY_LIST = 'currency_list';
    private const string DATE_FORMAT = 'd/m/Y';
    private const int TTL_DAYS = 10;
    private const int SECONDS_IN_DAY = 86400;

    public function __construct(
        public readonly ClientInterface $redis
    ) {
    }

    /**
     * @throws  Exception
     */
    public function fetch(string $currencyCode, string $internalCode, string $baseDay): ?RateDTO
    {
        $baseRate = $this->redis->get($baseDay . ':' . $currencyCode);
        $previousRate = $this->redis->get($this->getPreviousDay($baseDay) . ':' . $currencyCode);

        if (empty($baseRate) || empty($previousRate)) {
            $rates = $this->getRateByTwoDaysXml($internalCode, $baseDay);
            if (empty($rates)) {
                throw Exception('Error of fetching rates');
            }
            $previousRate = $rates[0];
            $baseRate = $rates[1];
        }

        return $this->createRateDto($currencyCode, $previousRate, $baseRate);
    }

    public function fetchByDate(string $date): array
    {
        $url = sprintf(self::CURRENCY_DATE_URL, $date);
        $xmlContent = file_get_contents($url);
        $xml = simplexml_load_string($xmlContent);

        $result = [];
        foreach ($xml->Valute as $valute) {
            $charCode = (string)$valute->CharCode;
            $value = (string)$valute->Value;

            if ($charCode && $value) {
                $result[$charCode] = str_replace(",", ".", (string)$value);
            }
        }
        return $result;
    }

    public function getInternalCodeByISO(string $targetCode): ?string
    {
        $currencyList = $this->redis->hgetall(self::CURRENCY_LIST);
        if (!$currencyList) {
            $currencyList = $this->getCurrencyList();
            $this->redis->hmset(self::CURRENCY_LIST, $currencyList);
            $this->redis->expire(self::CURRENCY_LIST, self::TTL_DAYS * self::SECONDS_IN_DAY);
        }

        return key_exists($targetCode, $currencyList) ? $currencyList[$targetCode] : null;
    }

    private function getPreviousDay(string $dateString): string
    {
        $date = DateTime::createFromFormat(self::DATE_FORMAT, $dateString);
        $previousDateTime = (clone $date)->modify('-1 day');

        return $previousDateTime->format(self::DATE_FORMAT);
    }

    private function createRateDto(string $code, string $previousRate, string $baseRate): RateDTO
    {
        $delta = $this->getDelta($baseRate, $previousRate);
        return new RateDTO($code, $baseRate, $delta);
    }

    private function getDelta(string $baseRate, string $previousRate): string
    {
        return bcsub($baseRate, $previousRate, 4);
    }

    private function getRateByTwoDaysXml(string $internalCode, string $date): array
    {
        $url = sprintf(self::CURRENCY_DELTA_URL, $this->getPreviousDay($date), $date, $internalCode);

        $xmlString = file_get_contents($url);
        $xml = new SimpleXMLElement($xmlString);

        $result = [];
        foreach ($xml->Record as $record) {
            $result[] = str_replace(",", ".", (string)$record->Value);
        }

        if (count($result) === 2) {
            return $result;
        }

        if (count($result) === 1) {
            return [$result[0], $result[0]];
        }

        return [];
    }

    private function getCurrencyList(): array
    {
        $xmlString = file_get_contents(self::CURRENCY_LIST_URL);
        $xml = new SimpleXMLElement($xmlString);
        $data = [];

        foreach ($xml->Item as $item) {
            $data[(string)$item->ISO_Char_Code] = (string)$item['ID'];
        }

        return $data;
    }
}
