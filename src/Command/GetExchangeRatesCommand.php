<?php

namespace App\Command;

use App\Service\CalcServiceInterface;
use App\Service\CurrencyDataFetcherInterface;
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
    private const string BASE_CURRENCY = 'RUR';
    private CurrencyDataFetcherInterface $currencyDataFetcher;
    private CalcServiceInterface $calcService;

    public function __construct(CurrencyDataFetcherInterface $currencyDataFetcher, CalcServiceInterface $calcService)
    {
        parent::__construct();
        $this->currencyDataFetcher = $currencyDataFetcher;
        $this->calcService = $calcService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('date', InputArgument::REQUIRED, 'Date in the format DD/MM/YYYY')
            ->addArgument('currencyCode', InputArgument::REQUIRED, 'Currency code, e.g. USD')
            ->addArgument('baseCurrencyCode', InputArgument::OPTIONAL, 'Base currency code, e.g. RUB', self::BASE_CURRENCY)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateString = $input->getArgument('date');
        $currencyCode = strtoupper($input->getArgument('currencyCode'));
        $baseCurrencyCode = strtoupper($input->getArgument('baseCurrencyCode'));

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
        $internalCodeBase = $baseCurrencyCode === self::BASE_CURRENCY ? null : $this->currencyDataFetcher->getInternalCodeByISO(
            $baseCurrencyCode
        );

        if (!$internalCode || ($baseCurrencyCode !== self::BASE_CURRENCY && !$internalCodeBase)) {
            $output->writeln("<error>Invalid currency format. Please use ISO code like USD.</error>");
            return Command::INVALID;
        }

        $rates = $this->currencyDataFetcher->fetch($currencyCode, $internalCode, $dateString);

        if (!$this->checkRatesBase($rates, $output)) {
            return Command::INVALID;
        }

        $previousRate = $rates[0];
        $baseRate = $rates[1];
        if ($baseCurrencyCode !== self::BASE_CURRENCY) {
            $ratesBase = $this->currencyDataFetcher->fetch($baseCurrencyCode, $internalCodeBase, $dateString);
            if (!$this->checkRatesBase($ratesBase, $output)) {
                return Command::INVALID;
            }
            $previousRate = $this->calcService->calcRateToBase($rates[0], $ratesBase[0]);
            $baseRate = $this->calcService->calcRateToBase($rates[1], $ratesBase[1]);
        }

        $delta = $this->calcService->getDelta($baseRate, $previousRate);

        $output->writeln("<info>$currencyCode rate in $baseCurrencyCode</info>");
        $output->writeln('==================');
        $output->writeln('<info>Rate:</info> ' . $baseRate);
        $output->writeln('<info>Delta:</info> ' . $delta);

        return Command::SUCCESS;
    }

    private function isValidDate(string $date): bool
    {
        $dateTime = \DateTime::createFromFormat('d/m/Y', $date);
        return $dateTime && $dateTime->format('d/m/Y') === $date;
    }

    private function checkRatesBase(array $ratesBase, OutputInterface $output): bool
    {
        if (empty($ratesBase)) {
            $output->writeln("<error>Failed to retrieve course data.</error>");
            return false;
        }
        return true;
    }
}
