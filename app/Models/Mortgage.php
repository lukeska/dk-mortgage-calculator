<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mortgage extends Model
{
    /** @use HasFactory<\Database\Factories\MortgageFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'property_value',
        'downpayment',
        'ejerudgift',
        'heating',
        'water',
        'repairs',
        'rent_expenses',
        'loan_period_fixed',
        'loan_period_variable',
        'fixed_mortgage_percentage',
        'flexible_loan_type',
        'with_repayments',
        'bank_loan_interest',
        'bank_loan_period',
        'interest_rate_f3',
        'interest_rate_f5',
        'interest_rate_f30',
        'bidragssats_adjustment',
        'f30_no_repay',
        'f30_with_repay',
        'inflation_ejerudgift',
        'inflation_heating',
        'inflation_water',
        'inflation_repairs',
        'inflation_rent',
        'variable_rate_overrides',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'with_repayments' => 'boolean',
            'variable_rate_overrides' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
