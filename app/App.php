<?php

namespace App;

use App\Api\CmcApi;
use App\Api\CryptoApi;
use App\Api\CryptoCompareApi;
use InitPHP\CLITable\Table;

class App
{
    private CryptoApi $api;
    private Wallet $wallet;

    public function __construct()
    {
        $this->api = new CmcApi();
        $this->wallet = new Wallet();
    }

    private function fillTable(array $cryptocurrencies): ?Table
    {
        if (count($cryptocurrencies) > 0) {
            $table = new Table();
            foreach ($cryptocurrencies as $cryptocurrency) {
                $table->row([
                    'name' => $cryptocurrency->getName(),
                    'symbol' => $cryptocurrency->getSymbol(),
                    'price' => number_format($cryptocurrency->getPrice(), 2) . " $",
                ]);
            }

            return $table;
        }
        return null;
    }

    public function listTopCryptos(): ?Table
    {
        $apiData = $this->api->getTopCryptos();

        return $this->fillTable($apiData);
    }

    public function listSingleCrypto(string $userInput): ?Table
    {
        $apiData = $this->api->getCryptoBySymbol($userInput);

        return $this->fillTable($apiData);
    }

    public function buyCrypto(string $symbol, float $amount): void
    {
        $apiData = $this->api->getCryptoBySymbol($symbol);

        if ($apiData === null) {
            echo "No such crypto symbol $symbol found" . PHP_EOL;
            return;
        }

        $name = $apiData[0]->getName();
        $symbol = $apiData[0]->getSymbol();
        $price = $apiData[0]->getPrice();

        $cryptocurrency = new Cryptocurrency($name, $symbol, $price);

        $this->wallet->buyCrypto($cryptocurrency, $amount);
        $this->wallet->saveWallet();
    }

    public function sellCrypto(string $symbol, float $amount): void
    {
        $apiData = $this->api->getCryptoBySymbol($symbol);

        $name = $apiData[0]->getName();
        $symbol = $apiData[0]->getSymbol();
        $price = $apiData[0]->getPrice();

        $cryptocurrency = new Cryptocurrency($name, $symbol, $price);

        $this->wallet->sellCrypto($cryptocurrency, $amount);
        $this->wallet->saveWallet();
    }

    public function displayWalletState(): void
    {
        $cash = $this->wallet->getBalanceUsd();
        $cashFormatted = number_format($cash, 2);

        echo "Cash balance - " . $cashFormatted . "$" . PHP_EOL;

        $holdings = $this->wallet->getHoldings();

        if (empty($holdings)) {
            echo "No holdings to display." . PHP_EOL;
            return;
        }

        $currentValues = [];
        $table = new Table();

        foreach ($holdings as $symbol => $amount) {
            $apiData = $this->api->getCryptoBySymbol($symbol);

            $price = $apiData[0]->getPrice();

            $value = $price * $amount;

            $currentValues[$symbol] = $value;

            $table->row([
                'name' => $symbol,
                'amount' => $amount,
                'value' => number_format($value, 2) . " $",
            ]);
        }

        $holdingsSum = array_sum($currentValues);

        $totalBalance = $cash + $holdingsSum;
        $totalBalanceFormatted = number_format($totalBalance, 2);

        echo "Total balance - " . $totalBalanceFormatted . "$" . PHP_EOL;

        echo $table;
    }

    public function displayTransactions(): void
    {
        $transactions = $this->wallet->getTransactions();

        $table = new Table();

        foreach ($transactions as $transaction) {
            $formattedDate = $transaction->getDate()->setTimezone('Europe/Riga')->format('d-m-Y H:i:s');

            $table->row([
                'date' => $formattedDate,
                'type' => $transaction->getType(),
                'amount' => $transaction->getAmount(),
                'cryptocurrency' => $transaction->getCryptocurrency(),
                'price' => number_format($transaction->getPurchasePrice(), 2) . " $",
            ]);
        }

        echo $table;
    }
}