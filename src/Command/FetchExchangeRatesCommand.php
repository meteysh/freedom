<?php

namespace App\Command;

use App\Message\RatesDataMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;


#[AsCommand(
    name: 'app:fetch-exchange-rates',
    description: 'Batch receipt of currencies',
    aliases: ['app:fe-ex-ra'],
    hidden: false
)]
class FetchExchangeRatesCommand extends Command
{
    private MessageBusInterface $bus;

    private const int DAYS = 180;

    public function __construct(
        MessageBusInterface $bus
    ) {
        parent::__construct();
        $this->bus = $bus;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = new \DateTime();
        $startDate = clone $today;
        $this->bus->dispatch(new RatesDataMessage($today->format('d/m/Y')));

        for ($i = 2; $i <= self::DAYS; $i++) {
            $startDate->modify('-1 day');
            $date = $startDate->format('d/m/Y');
            $this->bus->dispatch(new RatesDataMessage($date));
        }

        $output->writeln('Requests to fetch exchange rates have been dispatched.');
        return Command::SUCCESS;
    }
}
