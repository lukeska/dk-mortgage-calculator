<?php

namespace App\Console\Commands;

use App\Models\ExchangeRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchExchangeRates extends Command
{
    protected $signature = 'exchange-rates:fetch';

    protected $description = 'Fetch latest exchange rates from DKK to other currencies';

    /**
     * @var array<string, string>
     */
    private array $currencyNames = [
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'GBP' => 'British Pound',
        'JPY' => 'Japanese Yen',
        'AUD' => 'Australian Dollar',
        'CAD' => 'Canadian Dollar',
        'CHF' => 'Swiss Franc',
        'CNY' => 'Chinese Yuan',
        'SEK' => 'Swedish Krona',
        'NOK' => 'Norwegian Krone',
        'NZD' => 'New Zealand Dollar',
        'MXN' => 'Mexican Peso',
        'SGD' => 'Singapore Dollar',
        'HKD' => 'Hong Kong Dollar',
        'KRW' => 'South Korean Won',
        'TRY' => 'Turkish Lira',
        'RUB' => 'Russian Ruble',
        'INR' => 'Indian Rupee',
        'BRL' => 'Brazilian Real',
        'ZAR' => 'South African Rand',
        'PLN' => 'Polish Zloty',
        'THB' => 'Thai Baht',
        'IDR' => 'Indonesian Rupiah',
        'HUF' => 'Hungarian Forint',
        'CZK' => 'Czech Koruna',
        'ILS' => 'Israeli Shekel',
        'CLP' => 'Chilean Peso',
        'PHP' => 'Philippine Peso',
        'AED' => 'UAE Dirham',
        'COP' => 'Colombian Peso',
        'SAR' => 'Saudi Riyal',
        'MYR' => 'Malaysian Ringgit',
        'RON' => 'Romanian Leu',
        'DKK' => 'Danish Krone',
    ];

    public function handle(): int
    {
        $this->info('Fetching exchange rates from API...');

        $response = Http::get('https://v6.exchangerate-api.com/v6/6680e39093b150914b8c3d21/latest/dkk');

        if ($response->failed()) {
            $this->error('Failed to fetch exchange rates: '.$response->status());

            return Command::FAILURE;
        }

        $data = $response->json();

        if ($data['result'] !== 'success') {
            $this->error('API returned an error: '.($data['error-type'] ?? 'Unknown error'));

            return Command::FAILURE;
        }

        $rates = $data['conversion_rates'] ?? [];
        $fetchedAt = now();
        $count = 0;

        foreach ($rates as $currencyCode => $rate) {
            if ($currencyCode === 'DKK') {
                continue;
            }

            ExchangeRate::updateOrCreate(
                ['currency_code' => $currencyCode],
                [
                    'currency_name' => $this->currencyNames[$currencyCode] ?? $currencyCode,
                    'rate' => $rate,
                    'fetched_at' => $fetchedAt,
                ]
            );
            $count++;
        }

        $this->info("Successfully updated {$count} exchange rates.");

        return Command::SUCCESS;
    }
}

