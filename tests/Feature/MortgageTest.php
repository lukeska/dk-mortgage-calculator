<?php

use App\Models\Mortgage;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();

    $this->validMortgageData = [
        'name' => 'Test House',
        'property_value' => 5000000,
        'downpayment' => 1000000,
        'ejerudgift' => 4000,
        'heating' => 1000,
        'water' => 200,
        'repairs' => 500,
        'rent_expenses' => 15000,
        'loan_period_fixed' => 30,
        'loan_period_variable' => 30,
        'fixed_mortgage_percentage' => 100,
        'flexible_loan_type' => 'F5',
        'with_repayments' => true,
        'bank_loan_interest' => 6.00,
        'bank_loan_period' => 10,
        'interest_rate_f3' => 3.59,
        'interest_rate_f5' => 3.49,
        'interest_rate_f30' => 5.00,
        'bidragssats_adjustment' => 0,
        'f30_no_repay' => 91,
        'f30_with_repay' => 96,
        'inflation_ejerudgift' => 2.00,
        'inflation_heating' => 2.00,
        'inflation_water' => 2.00,
        'inflation_repairs' => 2.00,
        'inflation_rent' => 2.00,
        'variable_rate_overrides' => null,
    ];
});

test('unauthenticated users cannot access mortgages', function () {
    $this->getJson('/mortgages')->assertUnauthorized();
    $this->postJson('/mortgages', $this->validMortgageData)->assertUnauthorized();
});

test('authenticated user can list their mortgages', function () {
    Mortgage::factory()->count(3)->for($this->user)->create();
    Mortgage::factory()->count(2)->for($this->otherUser)->create();

    $response = $this->actingAs($this->user)
        ->getJson('/mortgages');

    $response->assertOk()
        ->assertJsonCount(3);
});

test('authenticated user can create a mortgage', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/mortgages', $this->validMortgageData);

    $response->assertCreated()
        ->assertJsonFragment(['name' => 'Test House']);

    $this->assertDatabaseHas('mortgages', [
        'user_id' => $this->user->id,
        'name' => 'Test House',
        'property_value' => 5000000,
    ]);
});

test('mortgage creation validates required fields', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/mortgages', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'property_value', 'downpayment']);
});

test('mortgage creation validates loan period values', function () {
    $data = array_merge($this->validMortgageData, [
        'loan_period_fixed' => 15,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/mortgages', $data);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['loan_period_fixed']);
});

test('mortgage creation validates flexible loan type', function () {
    $data = array_merge($this->validMortgageData, [
        'flexible_loan_type' => 'F10',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/mortgages', $data);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['flexible_loan_type']);
});

test('authenticated user can view their own mortgage', function () {
    $mortgage = Mortgage::factory()->for($this->user)->create();

    $response = $this->actingAs($this->user)
        ->getJson("/mortgages/{$mortgage->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $mortgage->id]);
});

test('authenticated user cannot view other users mortgage', function () {
    $mortgage = Mortgage::factory()->for($this->otherUser)->create();

    $response = $this->actingAs($this->user)
        ->getJson("/mortgages/{$mortgage->id}");

    $response->assertForbidden();
});

test('authenticated user can update their own mortgage', function () {
    $mortgage = Mortgage::factory()->for($this->user)->create();

    $response = $this->actingAs($this->user)
        ->putJson("/mortgages/{$mortgage->id}", [
            'name' => 'Updated Name',
            'property_value' => 6000000,
        ]);

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Updated Name']);

    $this->assertDatabaseHas('mortgages', [
        'id' => $mortgage->id,
        'name' => 'Updated Name',
        'property_value' => 6000000,
    ]);
});

test('authenticated user cannot update other users mortgage', function () {
    $mortgage = Mortgage::factory()->for($this->otherUser)->create();

    $response = $this->actingAs($this->user)
        ->putJson("/mortgages/{$mortgage->id}", [
            'name' => 'Hacked Name',
        ]);

    $response->assertForbidden();
});

test('authenticated user can delete their own mortgage', function () {
    $mortgage = Mortgage::factory()->for($this->user)->create();

    $response = $this->actingAs($this->user)
        ->deleteJson("/mortgages/{$mortgage->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('mortgages', [
        'id' => $mortgage->id,
    ]);
});

test('authenticated user cannot delete other users mortgage', function () {
    $mortgage = Mortgage::factory()->for($this->otherUser)->create();

    $response = $this->actingAs($this->user)
        ->deleteJson("/mortgages/{$mortgage->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('mortgages', [
        'id' => $mortgage->id,
    ]);
});

test('mortgage can store variable rate overrides as json', function () {
    $data = array_merge($this->validMortgageData, [
        'variable_rate_overrides' => ['6' => 4.5, '11' => 5.0],
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/mortgages', $data);

    $response->assertCreated();

    $mortgage = Mortgage::first();
    expect($mortgage->variable_rate_overrides)->toEqual(['6' => 4.5, '11' => 5.0]);
});

test('user mortgages are deleted when user is deleted', function () {
    $mortgage = Mortgage::factory()->for($this->user)->create();

    $this->user->delete();

    $this->assertDatabaseMissing('mortgages', [
        'id' => $mortgage->id,
    ]);
});
