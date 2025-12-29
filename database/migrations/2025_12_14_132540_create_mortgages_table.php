<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mortgages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');

            // Property Details
            $table->unsignedInteger('property_value');
            $table->unsignedInteger('downpayment');

            // Monthly Expenses
            $table->unsignedInteger('ejerudgift');
            $table->unsignedInteger('heating');
            $table->unsignedInteger('water');
            $table->unsignedInteger('repairs');
            $table->unsignedInteger('rent_expenses');

            // Loan Configuration
            $table->unsignedTinyInteger('loan_period_fixed');
            $table->unsignedTinyInteger('loan_period_variable');
            $table->unsignedTinyInteger('fixed_mortgage_percentage');
            $table->string('flexible_loan_type', 2);
            $table->boolean('with_repayments');

            // Bank Loan
            $table->decimal('bank_loan_interest', 5, 2);
            $table->unsignedTinyInteger('bank_loan_period');

            // Interest Rates
            $table->decimal('interest_rate_f3', 5, 2);
            $table->decimal('interest_rate_f5', 5, 2);
            $table->decimal('interest_rate_f30', 5, 2);
            $table->decimal('bidragssats_adjustment', 5, 2);
            $table->unsignedTinyInteger('f30_no_repay');
            $table->unsignedTinyInteger('f30_with_repay');

            // Inflation Rates
            $table->decimal('inflation_ejerudgift', 5, 2);
            $table->decimal('inflation_heating', 5, 2);
            $table->decimal('inflation_water', 5, 2);
            $table->decimal('inflation_repairs', 5, 2);
            $table->decimal('inflation_rent', 5, 2);

            // Year-specific overrides
            $table->json('variable_rate_overrides')->nullable();

            // Computed totals (stored for dashboard/reporting)
            $table->unsignedBigInteger('loan_total_amount')->default(0);
            $table->unsignedBigInteger('fixed_loan_total_amount')->default(0);
            $table->unsignedBigInteger('variable_loan_total_amount')->default(0);
            $table->unsignedBigInteger('bank_loan_total_amount')->default(0);
            $table->unsignedBigInteger('fixed_interest_total_amount')->default(0);
            $table->unsignedBigInteger('variable_interest_total_amount')->default(0);
            $table->unsignedBigInteger('bank_interest_total_amount')->default(0);
            $table->unsignedBigInteger('total_tax_deductions')->default(0);
            $table->unsignedInteger('first_year_tax_deduction')->default(0);
            $table->unsignedInteger('first_year_monthly_cost')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mortgages');
    }
};
