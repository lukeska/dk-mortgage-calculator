<?php

test('calculator page is accessible', function () {
    $this->withoutVite();

    $response = $this->get(route('calculator'));

    $response->assertSuccessful();
});

test('calculator page renders the MortgageCalculator component', function () {
    $this->withoutVite();

    $response = $this->get(route('calculator'));

    $response->assertInertia(fn ($page) => $page->component('MortgageCalculator'));
});
