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
const ejerudgiftStep = ref(200);

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

// Ejerudgift values (columns) - 5 below, current, 5 above
const ejerudgiftValues = computed(() => {
    const base = props.ejerudgift;
    const step = ejerudgiftStep.value;
    const variations: number[] = [];

    // 5 values below
    for (let i = 5; i >= 1; i--) {
        const value = base - step * i;
        if (value >= 0) {
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

function calculateMonthlyHousingCost(
    propertyValue: number,
    ejerudgift: number,
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

    // Year 1 interest
    const yearlyFixedInterest =
        fixedLoanPlusBond * (props.fixedEffectiveRate / 100);
    const yearlyVariableInterest =
        variableLoanAmount * (props.variableEffectiveRate / 100);
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

    // Monthly utilities (using variable ejerudgift)
    const monthlyUtilities =
        ejerudgift + props.heating + props.water + props.repairs;

    return monthlyPayment + monthlyUtilities - monthlyTaxDeduction;
}

// Base monthly cost for comparison
const baseMonthlyHousingCost = computed(() => {
    return calculateMonthlyHousingCost(props.propertyValue, props.ejerudgift);
});

function isCurrentCell(propertyValue: number, ejerudgift: number): boolean {
    return (
        propertyValue === props.propertyValue && ejerudgift === props.ejerudgift
    );
}

function getCellClass(propertyValue: number, ejerudgift: number): string {
    if (isCurrentCell(propertyValue, ejerudgift)) {
        return 'bg-blue-200 font-bold';
    }

    const cellCost = calculateMonthlyHousingCost(propertyValue, ejerudgift);
    const baseCost = baseMonthlyHousingCost.value;
    const percentageDiff = ((cellCost - baseCost) / baseCost) * 100;

    if (percentageDiff < -5) {
        // More than 10% cheaper - light green
        return 'bg-green-100 dark:bg-green-900/30';
    } else if (percentageDiff > 5) {
        // More than 10% more expensive - light red
        return 'bg-red-100 dark:bg-red-900/30';
    } else {
        // Within 10% - light yellow
        return 'bg-yellow-100 dark:bg-yellow-900/30';
    }
}
</script>

<template>
    <div class="overflow-x-auto">
        <div class="mb-4 flex flex-wrap items-center gap-6">
            <div class="flex items-center gap-2">
                <label
                    for="priceStepEjerudgift"
                    class="text-sm font-medium text-muted-foreground"
                >
                    Price step:
                </label>
                <CurrencyInput
                    id="priceStepEjerudgift"
                    v-model="priceStep"
                    class="h-8 w-32"
                />
            </div>
            <div class="flex items-center gap-2">
                <label
                    for="ejerudgiftStep"
                    class="text-sm font-medium text-muted-foreground"
                >
                    Ejerudgift step:
                </label>
                <CurrencyInput
                    id="ejerudgiftStep"
                    v-model="ejerudgiftStep"
                    class="h-8 w-32"
                />
            </div>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b bg-muted/50">
                    <th
                        class="sticky left-0 z-10 bg-muted/50 px-3 py-2 text-left font-medium"
                    >
                        Property Value ↓ / Ejerudgift →
                    </th>
                    <th
                        v-for="ej in ejerudgiftValues"
                        :key="ej"
                        class="px-3 py-2 text-right font-medium whitespace-nowrap"
                    >
                        {{ formatCurrency(ej) }}
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
                        v-for="ej in ejerudgiftValues"
                        :key="`${pv}-${ej}`"
                        class="px-3 py-2 text-right whitespace-nowrap"
                        :class="getCellClass(pv, ej)"
                    >
                        {{
                            formatCurrency(calculateMonthlyHousingCost(pv, ej))
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
