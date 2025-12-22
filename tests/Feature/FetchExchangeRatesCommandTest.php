<?php

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Http;

it('fetches exchange rates from the API and stores them', function () {
    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([
            'result' => 'success',
            'conversion_rates' => [
                'DKK' => 1,
                'USD' => 0.1456,
                'EUR' => 0.1341,
                'GBP' => 0.1123,
            ],
        ]),
    ]);

    $this->artisan('exchange-rates:fetch')
        ->expectsOutput('Fetching exchange rates from API...')
        ->expectsOutput('Successfully updated 3 exchange rates.')
        ->assertSuccessful();

    expect(ExchangeRate::count())->toBe(3);
    expect(ExchangeRate::where('currency_code', 'USD')->first()->rate)->toBe('0.14560000');
    expect(ExchangeRate::where('currency_code', 'EUR')->first()->rate)->toBe('0.13410000');
});

it('updates existing exchange rates', function () {
    ExchangeRate::factory()->create([
        'currency_code' => 'USD',
        'rate' => 0.10,
    ]);

    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([
            'result' => 'success',
            'conversion_rates' => [
                'DKK' => 1,
                'USD' => 0.1456,
            ],
        ]),
    ]);

    $this->artisan('exchange-rates:fetch')->assertSuccessful();

    expect(ExchangeRate::count())->toBe(1);
    expect(ExchangeRate::where('currency_code', 'USD')->first()->rate)->toBe('0.14560000');
});

it('fails when API returns an error', function () {
    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([
            'result' => 'error',
            'error-type' => 'invalid-key',
        ]),
    ]);

    $this->artisan('exchange-rates:fetch')
        ->expectsOutput('API returned an error: invalid-key')
        ->assertFailed();
});

it('fails when API request fails', function () {
    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response(null, 500),
    ]);

    $this->artisan('exchange-rates:fetch')
        ->expectsOutput('Failed to fetch exchange rates: 500')
        ->assertFailed();
});
