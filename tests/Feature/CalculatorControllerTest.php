<?php

use App\Models\ExchangeRate;
use App\Models\Mortgage;
use App\Models\User;

it('shows calculator page with exchange rates', function () {
    ExchangeRate::factory()->create([
        'currency_code' => 'USD',
        'currency_name' => 'US Dollar',
        'rate' => 0.1456,
    ]);

    ExchangeRate::factory()->create([
        'currency_code' => 'EUR',
        'currency_name' => 'Euro',
        'rate' => 0.1341,
    ]);

    $response = $this->get('/calculator');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('MortgageCalculator')
        ->has('exchangeRates', 2)
        ->where('exchangeRates.0.currency_code', 'EUR')
        ->where('exchangeRates.1.currency_code', 'USD')
    );
});

it('shows calculator page with empty exchange rates when none exist', function () {
    $response = $this->get('/calculator');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('MortgageCalculator')
        ->has('exchangeRates', 0)
    );
});

it('shows mortgage simulation with exchange rates', function () {
    $user = User::factory()->create();

    $mortgage = Mortgage::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Mortgage',
    ]);

    ExchangeRate::factory()->create([
        'currency_code' => 'GBP',
        'currency_name' => 'British Pound',
        'rate' => 0.1123,
    ]);

    $response = $this->actingAs($user)->get("/mortgage-simulation/{$mortgage->id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('MortgageCalculator')
        ->has('mortgage')
        ->has('exchangeRates', 1)
        ->where('exchangeRates.0.currency_code', 'GBP')
    );
});
