<?php

namespace App\Message;

readonly class RatesDataMessage
{
    public function __construct(
        private string $date
    ) {
    }

    public function getDate(): string
    {
        return $this->date;
    }
}
