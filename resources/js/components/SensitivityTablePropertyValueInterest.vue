<script setup lang="ts">
import CurrencyInput from '@/components/ui/input/CurrencyInput.vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    propertyValue: number;
    downpayment: number;
    ejerudgift: number;
    heating: number;
    water: number;
    repairs: number;
    fixedMortgagePercentage: number;
    withRepayments: boolean;
    loanPeriodFixed: number;
    loanPeriodVariable: number;
    bankLoanPeriod: number;
    bankLoanInterest: number;
    fixedEffectiveRate: number;
    variableEffectiveRate: number;
    f30NoRepay: number;
    f30WithRepay: number;
    formatCurrency: (value: number) => string;
}>();

const priceStep = ref(100000);
const interestStep = ref(0.5);

// Property value variations (rows) - 5 below, current, 5 above
const propertyValueVariations = computed(() => {
    const base = props.propertyValue;
    const step = priceStep.value;
    const variations: number[] = [];

    // 5 values below
    for (let i = 5; i >= 1; i--) {
        const value = base - step * i;
        if (value > 0) {
            variations.push(value);
        }
    }

    // Current value
    variations.push(base);

    // 5 values above
    for (let i = 1; i <= 5; i++) {
        variations.push(base + step * i);
    }

    return variations;
});

// Interest rate values (columns) - 5 below, current, 5 above
const interestValues = computed(() => {
    const base = props.variableEffectiveRate;
    const step = interestStep.value;
    const variations: number[] = [];

    // 5 values below
    for (let i = 5; i >= 1; i--) {
        const value = base - step * i;
        if (value >= 0) {
            variations.push(Math.round(value * 100) / 100);
        }
    }

    // Current value
    variations.push(Math.round(base * 100) / 100);

    // 5 values above
    for (let i = 1; i <= 5; i++) {
        variations.push(Math.round((base + step * i) * 100) / 100);
    }

    return variations;
});

function calculateMonthlyHousingCost(
    propertyValue: number,
    variableRate: number,
): number {
    // Calculate loan amounts
    const maxMortgage = propertyValue * 0.8;
    const downpaymentBasedLoan = propertyValue - props.downpayment;
    const totalLoanAmount = Math.max(
        0,
        Math.min(downpaymentBasedLoan, maxMortgage),
    );

    const fixedLoanAmount =
        (totalLoanAmount * props.fixedMortgagePercentage) / 100;
    const variableLoanAmount = totalLoanAmount - fixedLoanAmount;

    // Bank loan (amount needed beyond mortgage)
    const capitalNeeded = propertyValue * 0.2;
    const bankLoanAmount = Math.max(0, capitalNeeded - props.downpayment);

    // Fixed loan with bond adjustment
    const bondPercentage = props.withRepayments
        ? props.f30WithRepay
        : props.f30NoRepay;
    const fixedLoanPlusBond = Math.round(
        (100 * fixedLoanAmount) / bondPercentage,
    );

    // Year 1 calculations (similar to composable logic)
    const interestOnlyYears = 10;
    const isInterestOnlyYear = !props.withRepayments;

    // Principal amounts per year
    const fixedRepaymentYears = props.loanPeriodFixed - interestOnlyYears;
    const variableRepaymentYears = props.loanPeriodVariable - interestOnlyYears;

    const yearlyFixedPrincipalAmount = props.withRepayments
        ? fixedLoanPlusBond / props.loanPeriodFixed
        : fixedRepaymentYears > 0
          ? fixedLoanPlusBond / fixedRepaymentYears
          : 0;

    const yearlyVariablePrincipalAmount = props.withRepayments
        ? variableLoanAmount / props.loanPeriodVariable
        : variableRepaymentYears > 0
          ? variableLoanAmount / variableRepaymentYears
          : 0;

    const yearlyBankLoanPrincipalAmount =
        props.bankLoanPeriod > 0 ? bankLoanAmount / props.bankLoanPeriod : 0;

    // Year 1 interest (using variable rate parameter)
    const yearlyFixedInterest =
        fixedLoanPlusBond * (props.fixedEffectiveRate / 100);
    const yearlyVariableInterest = variableLoanAmount * (variableRate / 100);
    const yearlyBankLoanInterest =
        bankLoanAmount * (props.bankLoanInterest / 100);

    // Year 1 principal (0 if interest-only)
    const yearlyFixedPrincipal = isInterestOnlyYear
        ? 0
        : yearlyFixedPrincipalAmount;
    const yearlyVariablePrincipal = isInterestOnlyYear
        ? 0
        : yearlyVariablePrincipalAmount;
    const yearlyBankLoanPrincipal = yearlyBankLoanPrincipalAmount;

    // Total yearly payment
    const totalYearlyPayment =
        yearlyFixedInterest +
        yearlyFixedPrincipal +
        yearlyVariableInterest +
        yearlyVariablePrincipal +
        yearlyBankLoanInterest +
        yearlyBankLoanPrincipal;

    // Tax deduction calculation
    const totalYearlyInterest =
        yearlyFixedInterest + yearlyVariableInterest + yearlyBankLoanInterest;
    const interestDeductionThreshold = 100000;
    const tier1DeductionRate = 0.336;
    const tier2DeductionRate = 0.206;
    const tier1Deduction =
        Math.min(totalYearlyInterest, interestDeductionThreshold) *
        tier1DeductionRate;
    const tier2Deduction =
        Math.max(0, totalYearlyInterest - interestDeductionThreshold) *
        tier2DeductionRate;
    const yearlyTaxDeduction = tier1Deduction + tier2Deduction;

    const monthlyPayment = totalYearlyPayment / 12;
    const monthlyTaxDeduction = yearlyTaxDeduction / 12;

    // Monthly utilities
    const monthlyUtilities =
        props.ejerudgift + props.heating + props.water + props.repairs;

    return monthlyPayment + monthlyUtilities - monthlyTaxDeduction;
}

// Base monthly cost for comparison
const baseMonthlyHousingCost = computed(() => {
    return calculateMonthlyHousingCost(
        props.propertyValue,
        props.variableEffectiveRate,
    );
});

function isCurrentCell(propertyValue: number, interest: number): boolean {
    return (
        propertyValue === props.propertyValue &&
        Math.abs(interest - props.variableEffectiveRate) < 0.01
    );
}

function getCellClass(propertyValue: number, interest: number): string {
    if (isCurrentCell(propertyValue, interest)) {
        return 'bg-blue-200 font-bold';
    }

    const cellCost = calculateMonthlyHousingCost(propertyValue, interest);
    const baseCost = baseMonthlyHousingCost.value;
    const percentageDiff = ((cellCost - baseCost) / baseCost) * 100;

    if (percentageDiff < -5) {
        // More than 5% cheaper - light green
        return 'bg-green-100 dark:bg-green-900/30';
    } else if (percentageDiff > 5) {
        // More than 5% more expensive - light red
        return 'bg-red-100 dark:bg-red-900/30';
    } else {
        // Within 5% - light yellow
        return 'bg-yellow-100 dark:bg-yellow-900/30';
    }
}
</script>

<template>
    <div class="overflow-x-auto">
        <div class="mb-4 flex flex-wrap items-center gap-6">
            <div class="flex items-center gap-2">
                <label
                    for="priceStepInterest"
                    class="text-sm font-medium text-muted-foreground"
                >
                    Price step:
                </label>
                <CurrencyInput
                    id="priceStepInterest"
                    v-model="priceStep"
                    class="h-8 w-32"
                />
            </div>
            <div class="flex items-center gap-2">
                <label
                    for="interestStep"
                    class="text-sm font-medium text-muted-foreground"
                >
                    Interest step:
                </label>
                <input
                    id="interestStep"
                    v-model.number="interestStep"
                    type="number"
                    min="0.1"
                    max="5"
                    step="0.1"
                    class="h-8 w-20 rounded-md border border-input bg-background px-2 py-1 text-sm shadow-sm ring-offset-background focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none dark:bg-input/30"
                />
                <span class="text-sm text-muted-foreground">%</span>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b bg-muted/50">
                    <th
                        class="sticky left-0 z-10 bg-muted/50 px-3 py-2 text-left font-medium"
                    >
                        Property Value ↓ / Variable Interest →
                    </th>
                    <th
                        v-for="rate in interestValues"
                        :key="rate"
                        class="px-3 py-2 text-right font-medium whitespace-nowrap"
                    >
                        {{ rate.toFixed(2) }}%
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="pv in propertyValueVariations"
                    :key="pv"
                    class="border-b hover:bg-muted/30"
                >
                    <td
                        class="sticky left-0 z-10 bg-background px-3 py-2 font-medium whitespace-nowrap"
                    >
                        {{ formatCurrency(pv) }}
                    </td>
                    <td
                        v-for="rate in interestValues"
                        :key="`${pv}-${rate}`"
                        class="px-3 py-2 text-right whitespace-nowrap"
                        :class="getCellClass(pv, rate)"
                    >
                        {{
                            formatCurrency(
                                calculateMonthlyHousingCost(pv, rate),
                            )
                        }}
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="mt-3 text-xs text-muted-foreground">
            Monthly net cost (Year 1) = Mortgage payments + Utilities - Tax
            deductions. Current selection highlighted.
        </p>
    </div>
</template>
