<?php

namespace App\Http\Controllers;

use App\Models\Mortgage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalculatorController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('MortgageCalculator');
    }

    public function showMortgage(Request $request, Mortgage $mortgage): Response
    {
        if ($mortgage->user_id !== $request->user()->id) {
            abort(403);
        }

        return Inertia::render('MortgageCalculator', [
            'mortgage' => $mortgage,
        ]);
    }
}
