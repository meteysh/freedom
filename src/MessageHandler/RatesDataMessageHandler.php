<?php

namespace App\MessageHandler;

use App\Message\RatesDataMessage;
use App\Service\CurrencyDataFetcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use SymfonyBundles\RedisBundle\Redis\ClientInterface;

#[AsMessageHandler]
readonly class RatesDataMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private CurrencyDataFetcher $currencyDataFetcher,
        public ClientInterface $redis
    ) {
    }

    public function __invoke(RatesDataMessage $message): void
    {
        $date = $message->getDate();
        $this->logger->info('Processing RatesDataMessage with date: ' . $date);
        $rates = $this->currencyDataFetcher->fetchByDate($date);

        foreach ($rates as $code => $rate) {
            $this->redis->set($date . ':' . $code, $rate);
        }

        $this->logger->info('Finished processing RatesDataMessage.');
    }
}
