<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class CalculatorController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('MortgageCalculator');
    }
}
