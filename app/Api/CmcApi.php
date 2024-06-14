<?php

namespace App\Api;

use App\Cryptocurrency;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;

class CmcApi implements CryptoApi
{
    private Client $client;
    private string $apiKey = "";

    public function __construct()
    {
        $this->client = new Client(['base_uri' => 'https://pro-api.coinmarketcap.com/v1/']);
    }

    public function getTopCryptos(): array
    {
        try {
            $response = $this->client->request('GET', 'cryptocurrency/listings/latest', [
                'headers' => [
                    'X-CMC_PRO_API_KEY' => $this->apiKey,
                ],
                'query' => [
                    'start' => '1',
                    'limit' => '10',
                ]
            ]);

            $apiData = json_decode($response->getBody(), true);
            $currencies = [];

            foreach ($apiData['data'] as $currency) {
                $crypto = new Cryptocurrency(
                    $currency['name'],
                    $currency['symbol'],
                    $currency['quote']['USD']['price']
                );

                $currencies[] = $crypto;
            }

            return $currencies;
        } catch (ClientException $e) {
            echo "Client error: " . $e->getMessage();
            return [];
        } catch (ServerException $e) {
            echo "Server error: " . $e->getMessage();
            return [];
        } catch (RequestException $e) {
            echo "Request error: " . $e->getMessage();
            return [];
        } catch (Exception $e) {
            echo "General error: " . $e->getMessage();
            return [];
        }
    }

    public function getCryptoBySymbol(string $symbol): ?array
    {
        try {
            $response = $this->client->request('GET', 'cryptocurrency/quotes/latest', [
                'headers' => [
                    'X-CMC_PRO_API_KEY' => $this->apiKey,
                ],
                'query' => [
                    'symbol' => $symbol,
                ]
            ]);

            $apiData = json_decode($response->getBody(), true);

            if (!empty($apiData['data'][$symbol])) {
                $currency = $apiData['data'][$symbol];
                return [new Cryptocurrency(
                    $currency['name'],
                    $currency['symbol'],
                    $currency['quote']['USD']['price']
                )];
            }

            return null;
        } catch (ClientException $e) {
            echo "Client error: " . $e->getMessage();
            return null;
        } catch (ServerException $e) {
            echo "Server error: " . $e->getMessage();
            return null;
        } catch (RequestException $e) {
            echo "Request error: " . $e->getMessage();
            return null;
        } catch (Exception $e) {
            echo "General error: " . $e->getMessage();
            return null;
        }
    }
}