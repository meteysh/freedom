<?php

namespace App\Command;

use App\Service\CurrencyDataFetcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:currency-rate',
    description: 'Getting exchange rates.',
    aliases: ['app:cu-ra'],
    hidden: false
)]
class GetExchangeRatesCommand extends Command
{
    private CurrencyDataFetcher $currencyDataFetcher;

    public function __construct(CurrencyDataFetcher $currencyDataFetcher)
    {
        parent::__construct();
        $this->currencyDataFetcher = $currencyDataFetcher;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('date', InputArgument::REQUIRED, 'Date in the format DD/MM/YYYY')
            ->addArgument('currencyCode', InputArgument::REQUIRED, 'Currency code, e.g. USD')
            ->addArgument('baseCurrencyCode', InputArgument::OPTIONAL, 'Base currency code, e.g. RUB', 'RUR')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateString = $input->getArgument('date');
        $currencyCode = strtoupper($input->getArgument('currencyCode'));
        $baseCurrencyCode = $input->getArgument('baseCurrencyCode');

        if (!$this->isValidDate($dateString)) {
            $output->writeln('<error>Invalid date format. Please use DD/MM/YYYY.</error>');
            return Command::FAILURE;
        }
        $output->writeln([
            'Getting exchange rates for ' . $currencyCode,
            '==========================',
            '',
        ]);

        $internalCode = $this->currencyDataFetcher->getInternalCodeByISO($currencyCode);

        if (!$internalCode) {
            $output->writeln("<error>Invalid currency format. Please use ISO code like USD.</error>");
            return Command::INVALID;
        }

        try {
            $rateDTO = $this->currencyDataFetcher->fetch($currencyCode, $internalCode, $dateString);
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::INVALID;
        }
        if (!$rateDTO) {
            $output->writeln("<error>Failed to retrieve course data.</error>");
            return Command::INVALID;
        }

        $output->writeln('<info>Currency Code:</info> ' . $rateDTO->getCode());
        $output->writeln('==================');
        $output->writeln('<info>Rate:</info> ' . $rateDTO->getRate());
        $output->writeln('<info>Delta:</info> ' . $rateDTO->getDelta());

        return Command::SUCCESS;
    }

    private function isValidDate(string $date): bool
    {
        $dateTime = \DateTime::createFromFormat('d/m/Y', $date);
        return $dateTime && $dateTime->format('d/m/Y') === $date;
    }
}
