<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMortgageRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'property_value' => ['sometimes', 'integer', 'min:0'],
            'downpayment' => ['sometimes', 'integer', 'min:0'],
            'ejerudgift' => ['sometimes', 'integer', 'min:0'],
            'heating' => ['sometimes', 'integer', 'min:0'],
            'water' => ['sometimes', 'integer', 'min:0'],
            'repairs' => ['sometimes', 'integer', 'min:0'],
            'rent_expenses' => ['sometimes', 'integer', 'min:0'],
            'loan_period_fixed' => ['sometimes', 'integer', 'in:10,20,30'],
            'loan_period_variable' => ['sometimes', 'integer', 'in:10,20,30'],
            'fixed_mortgage_percentage' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'flexible_loan_type' => ['sometimes', 'string', 'in:F3,F5'],
            'with_repayments' => ['sometimes', 'boolean'],
            'bank_loan_interest' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'bank_loan_period' => ['sometimes', 'integer', 'min:1', 'max:30'],
            'interest_rate_f3' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'interest_rate_f5' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'interest_rate_f30' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'bidragssats_adjustment' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'f30_no_repay' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'f30_with_repay' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'inflation_ejerudgift' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'inflation_heating' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'inflation_water' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'inflation_repairs' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'inflation_rent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'variable_rate_overrides' => ['nullable', 'array'],
            'variable_rate_overrides.*' => ['numeric', 'min:0', 'max:100'],
        ];
    }
}
