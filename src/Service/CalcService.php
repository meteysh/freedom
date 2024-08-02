<?php

declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;

class CalcService implements CalcServiceInterface
{
    /**
     * @param $currencyToRub //Курс валюты относительно рубля в строковом формате.
     * @param $referenceCurrencyToRub //Курс эталонной валюты относительно рубля в строковом формате.
     * @return string
     *
     * Пример:
     * Курс USD относительно рубля '80.0000'
     * Курс EUR относительно рубля '90.0000'
     * Рассчитываем курс USD в EUR
     * делим курс доллара(в rub) на курс евро(в rub)
     * получаем курс USD в EUR
     */
    public function calcRateToBase(string $currencyToRub, string $referenceCurrencyToRub): string
    {
        if (bccomp($referenceCurrencyToRub, '0') === 0) {
            throw new InvalidArgumentException(
                "The exchange rate of the reference currency cannot be zero."
            );
        }

        return bcdiv($currencyToRub, $referenceCurrencyToRub, 4);
    }

    public function getDelta(string $baseRate, string $previousRate): string
    {
        return bcsub($baseRate, $previousRate, 4);
    }
}
