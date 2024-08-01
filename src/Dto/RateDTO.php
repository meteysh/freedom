<?php

namespace App\Dto;

readonly class RateDTO
{
    public function __construct(
        private string $code,
        private string $rate,
        private string $delta
    ) {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getRate(): string
    {
        return $this->rate;
    }

    public function getDelta(): string
    {
        return $this->delta;
    }
}
