<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMortgageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'property_value' => ['required', 'integer', 'min:0'],
            'downpayment' => ['required', 'integer', 'min:0'],
            'ejerudgift' => ['required', 'integer', 'min:0'],
            'heating' => ['required', 'integer', 'min:0'],
            'water' => ['required', 'integer', 'min:0'],
            'repairs' => ['required', 'integer', 'min:0'],
            'rent_expenses' => ['required', 'integer', 'min:0'],
            'loan_period_fixed' => ['required', 'integer', 'in:10,20,30'],
            'loan_period_variable' => ['required', 'integer', 'in:10,20,30'],
            'fixed_mortgage_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'flexible_loan_type' => ['required', 'string', 'in:F3,F5'],
            'with_repayments' => ['required', 'boolean'],
            'bank_loan_interest' => ['required', 'numeric', 'min:0', 'max:100'],
            'bank_loan_period' => ['required', 'integer', 'min:1', 'max:30'],
            'interest_rate_f3' => ['required', 'numeric', 'min:0', 'max:100'],
            'interest_rate_f5' => ['required', 'numeric', 'min:0', 'max:100'],
            'interest_rate_f30' => ['required', 'numeric', 'min:0', 'max:100'],
            'bidragssats_adjustment' => ['required', 'numeric', 'min:0', 'max:100'],
            'f30_no_repay' => ['required', 'integer', 'min:0', 'max:100'],
            'f30_with_repay' => ['required', 'integer', 'min:0', 'max:100'],
            'inflation_ejerudgift' => ['required', 'numeric', 'min:0', 'max:100'],
            'inflation_heating' => ['required', 'numeric', 'min:0', 'max:100'],
            'inflation_water' => ['required', 'numeric', 'min:0', 'max:100'],
            'inflation_repairs' => ['required', 'numeric', 'min:0', 'max:100'],
            'inflation_rent' => ['required', 'numeric', 'min:0', 'max:100'],
            'variable_rate_overrides' => ['nullable', 'array'],
            'variable_rate_overrides.*' => ['numeric', 'min:0', 'max:100'],
            'loan_total_amount' => ['required', 'integer', 'min:0'],
            'fixed_loan_total_amount' => ['required', 'integer', 'min:0'],
            'variable_loan_total_amount' => ['required', 'integer', 'min:0'],
            'bank_loan_total_amount' => ['required', 'integer', 'min:0'],
            'fixed_interest_total_amount' => ['required', 'integer', 'min:0'],
            'variable_interest_total_amount' => ['required', 'integer', 'min:0'],
            'bank_interest_total_amount' => ['required', 'integer', 'min:0'],
            'total_tax_deductions' => ['required', 'integer', 'min:0'],
            'first_year_tax_deduction' => ['required', 'integer', 'min:0'],
            'first_year_monthly_cost' => ['required', 'integer', 'min:0'],
        ];
    }
}
