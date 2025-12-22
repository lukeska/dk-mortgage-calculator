<script setup lang="ts">
import type { YearlyBreakdown } from '@/composables/useMortgageCalculator';

const props = defineProps<{
    breakdown: YearlyBreakdown[];
    formatCurrency: (value: number) => string;
    formatNumber: (value: number) => string;
    ejerudgift: number;
    heating: number;
    water: number;
    repairs: number;
    rentExpenses: number;
    variableEffectiveRate: number;
    flexibleLoanType: 'F3' | 'F5';
    hasVariableLoan: boolean;
    initialFixedBalance: number;
    initialVariableBalance: number;
    initialBankLoanBalance: number;
    isEditableYear: (year: number) => boolean;
    setVariableRateForYear: (year: number, rate: number) => void;
    variableRateOverrides: Record<number, number>;
}>();

function handleRateChange(year: number, event: Event) {
    const target = event.target as HTMLInputElement;
    const value = parseFloat(target.value);
    if (!isNaN(value)) {
        props.setVariableRateForYear(year, value);
    }
}

function getBaseRateForYear(year: number): number {
    // Get the base rate (without bidragssats) for display in input
    // We need to subtract the bidragssats from the effective rate
    const row = props.breakdown.find((r) => r.year === year);
    if (row) {
        // The effective rate includes bidragssats, but for the input we want the base rate
        // We'll just use the overrides or the default base rate
        if (props.variableRateOverrides[year] !== undefined) {
            return props.variableRateOverrides[year];
        }
        // Find the most recent override before this year
        const overrideYears = Object.keys(props.variableRateOverrides)
            .map(Number)
            .filter((y) => y < year)
            .sort((a, b) => b - a);
        if (overrideYears.length > 0) {
            return props.variableRateOverrides[overrideYears[0]];
        }
    }
    // Default to the base rate from flexibleLoanType
    return props.flexibleLoanType === 'F3' ? 3.59 : 3.49;
}

const ownershipCostKeys = [
    { key: 'ejerudgift', label: 'Ejerudgift' },
    { key: 'heating', label: 'Heating' },
    { key: 'water', label: 'Water' },
    { key: 'repairs', label: 'Repairs' },
] as const;

const mortgageRows = [
    { key: 'fixedInterest', label: 'Interest - Fixed Loan' },
    { key: 'variableInterest', label: 'Interest - Variable Loan' },
    { key: 'bankLoanInterest', label: 'Interest - Bank Loan' },
    { key: 'fixedPrincipal', label: 'Repayment - Fixed Loan' },
    { key: 'variablePrincipal', label: 'Repayment - Variable Loan' },
    { key: 'bankLoanPrincipal', label: 'Repayment - Bank Loan' },
] as const;

const interestDeductionThreshold = 100000;
const tier1DeductionRate = 0.336;
const tier2DeductionRate = 0.206;

function calculateInterestDeduction(row: YearlyBreakdown): number {
    const totalInterest =
        row.fixedInterest + row.variableInterest + row.bankLoanInterest;
    const tier1Deduction =
        Math.min(totalInterest, interestDeductionThreshold) *
        tier1DeductionRate;
    const tier2Deduction =
        Math.max(0, totalInterest - interestDeductionThreshold) *
        tier2DeductionRate;
    return tier1Deduction + tier2Deduction;
}

function calculateTotalCost(row: YearlyBreakdown): number {
    const ownershipCosts =
        row.ejerudgift + row.heating + row.water + row.repairs;
    const mortgageCosts =
        row.fixedInterest +
        row.variableInterest +
        row.bankLoanInterest +
        row.fixedPrincipal +
        row.variablePrincipal +
        row.bankLoanPrincipal;
    const taxDeductions = calculateInterestDeduction(row);
    return ownershipCosts + mortgageCosts - taxDeductions;
}

function calculateYearlyRent(row: YearlyBreakdown): number {
    return row.rentExpenses;
}

function calculateBaseIncomeDelta(row: YearlyBreakdown): number {
    return calculateYearlyRent(row) - calculateTotalCost(row);
}

function calculateEffectiveInterestRate(
    row: YearlyBreakdown,
    index: number,
): number {
    const isLastYear = index === props.breakdown.length - 1;
    if (isLastYear) {
        return 0;
    }

    const totalBalance =
        row.fixedBalance + row.variableBalance + row.bankLoanBalance;
    if (totalBalance <= 0) {
        return 0;
    }
    const totalInterest =
        row.fixedInterest + row.variableInterest + row.bankLoanInterest;
    const deduction = calculateInterestDeduction(row);
    return ((totalInterest - deduction) / totalBalance) * 100;
}
</script>

<template>
    <div class="relative overflow-x-scroll">
        <table class="text-sm">
            <tbody class="divide-y divide-border">
                <!-- Year Header Row -->
                <tr class="bg-muted/50">
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-muted/50 px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
                    >
                        Year
                    </th>
                    <td
                        class="px-3 py-3 text-right font-medium whitespace-nowrap"
                    >
                        0
                    </td>
                    <td
                        v-for="row in breakdown"
                        :key="`year-${row.year}`"
                        class="px-3 py-3 text-right font-medium whitespace-nowrap"
                    >
                        {{ row.year }}
                    </td>
                </tr>

                <!-- Basic Ownership Costs Section -->
                <tr class="bg-amber-100 dark:bg-amber-900/30">
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-amber-100 px-3 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-amber-800 uppercase dark:bg-amber-900/30 dark:text-amber-200"
                    >
                        Basic ownership costs
                    </th>
                    <td class="px-3 py-3"></td>
                    <td
                        v-for="row in breakdown"
                        :key="`ownership-header-${row.year}`"
                        class="px-3 py-3"
                    ></td>
                </tr>

                <tr
                    v-for="costRow in ownershipCostKeys"
                    :key="costRow.key"
                    class="transition-colors hover:bg-muted/30"
                >
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-background px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
                    >
                        {{ costRow.label }}
                    </th>
                    <td
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    ></td>
                    <td
                        v-for="row in breakdown"
                        :key="`${costRow.key}-${row.year}`"
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    >
                        {{ formatCurrency(row[costRow.key]) }}
                    </td>
                </tr>

                <!-- Mortgage Section -->
                <tr class="bg-blue-100 dark:bg-blue-900/30">
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-blue-100 px-3 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-blue-800 uppercase dark:bg-blue-900/30 dark:text-blue-200"
                    >
                        Mortgage
                    </th>
                    <td class="px-3 py-3"></td>
                    <td
                        v-for="row in breakdown"
                        :key="`mortgage-header-${row.year}`"
                        class="px-3 py-3"
                    ></td>
                </tr>

                <tr
                    v-for="mortgageRow in mortgageRows"
                    :key="mortgageRow.key"
                    class="transition-colors hover:bg-muted/30"
                >
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-background px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
                    >
                        {{ mortgageRow.label }}
                    </th>
                    <td
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    ></td>
                    <td
                        v-for="row in breakdown"
                        :key="`${mortgageRow.key}-${row.year}`"
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    >
                        {{ formatCurrency(row[mortgageRow.key]) }}
                    </td>
                </tr>

                <!-- Tax Deductions Section -->
                <tr class="bg-green-100 dark:bg-green-900/30">
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-green-100 px-3 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-green-800 uppercase dark:bg-green-900/30 dark:text-green-200"
                    >
                        Tax Deductions
                    </th>
                    <td class="px-3 py-3"></td>
                    <td
                        v-for="row in breakdown"
                        :key="`tax-header-${row.year}`"
                        class="px-3 py-3"
                    ></td>
                </tr>

                <tr class="transition-colors hover:bg-muted/30">
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-background px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
                    >
                        Interest Deductions
                    </th>
                    <td
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    ></td>
                    <td
                        v-for="row in breakdown"
                        :key="`interest-deductions-${row.year}`"
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    >
                        {{ formatCurrency(calculateInterestDeduction(row)) }}
                    </td>
                </tr>

                <!-- Total Costs Section -->
                <tr class="bg-purple-100 dark:bg-purple-900/30">
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-purple-100 px-3 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-purple-800 uppercase dark:bg-purple-900/30 dark:text-purple-200"
                    >
                        Total Costs
                    </th>
                    <td class="px-3 py-3"></td>
                    <td
                        v-for="row in breakdown"
                        :key="`total-costs-header-${row.year}`"
                        class="px-3 py-3"
                    ></td>
                </tr>

                <tr class="transition-colors hover:bg-muted/30">
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-background px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
                    >
                        Total Cost
                    </th>
                    <td
                        class="px-3 py-3 text-right font-semibold whitespace-nowrap"
                    ></td>
                    <td
                        v-for="row in breakdown"
                        :key="`total-cost-${row.year}`"
                        class="px-3 py-3 text-right font-semibold whitespace-nowrap"
                    >
                        {{ formatCurrency(calculateTotalCost(row)) }}
                    </td>
                </tr>

                <tr class="transition-colors hover:bg-muted/30">
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-background px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
                    >
                        Reference Rent
                    </th>
                    <td
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    ></td>
                    <td
                        v-for="row in breakdown"
                        :key="`reference-rent-${row.year}`"
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    >
                        {{ formatCurrency(calculateYearlyRent(row)) }}
                    </td>
                </tr>

                <tr class="transition-colors hover:bg-muted/30">
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-background px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
                    >
                        Base Income Delta
                    </th>
                    <td
                        class="px-3 py-3 text-right font-semibold whitespace-nowrap"
                    ></td>
                    <td
                        v-for="row in breakdown"
                        :key="`base-income-delta-${row.year}`"
                        class="px-3 py-3 text-right font-semibold whitespace-nowrap"
                        :class="{
                            'text-red-600 dark:text-red-400':
                                calculateBaseIncomeDelta(row) < 0,
                            'text-green-600 dark:text-green-400':
                                calculateBaseIncomeDelta(row) > 0,
                        }"
                    >
                        {{ formatCurrency(calculateBaseIncomeDelta(row)) }}
                    </td>
                </tr>

                <!-- Other Stuff Section -->
                <tr class="bg-slate-100 dark:bg-slate-900/30">
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-slate-100 px-3 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-slate-800 uppercase dark:bg-slate-900/30 dark:text-slate-200"
                    >
                        Other stuff
                    </th>
                    <td
                        class="px-3 py-3 text-center text-xs font-semibold text-slate-800 dark:text-slate-200"
                    >
                        0
                    </td>
                    <td
                        v-for="row in breakdown"
                        :key="`other-stuff-header-${row.year}`"
                        class="px-3 py-3 text-center text-xs font-semibold text-slate-800 dark:text-slate-200"
                    >
                        {{ row.year }}
                    </td>
                </tr>

                <tr class="transition-colors hover:bg-muted/30">
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-background px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
                    >
                        Fixed Loan Outstanding
                    </th>
                    <td
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    >
                        {{ formatCurrency(initialFixedBalance) }}
                    </td>
                    <td
                        v-for="row in breakdown"
                        :key="`fixed-loan-outstanding-${row.year}`"
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    >
                        {{ formatCurrency(row.fixedBalance) }}
                    </td>
                </tr>

                <tr class="transition-colors hover:bg-muted/30">
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-background px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
                    >
                        Variable Loan Outstanding
                    </th>
                    <td
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    >
                        {{ formatCurrency(initialVariableBalance) }}
                    </td>
                    <td
                        v-for="row in breakdown"
                        :key="`variable-loan-outstanding-${row.year}`"
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    >
                        {{ formatCurrency(row.variableBalance) }}
                    </td>
                </tr>

                <tr class="transition-colors hover:bg-muted/30">
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-background px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
                    >
                        Bank Loan Outstanding
                    </th>
                    <td
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    >
                        {{ formatCurrency(initialBankLoanBalance) }}
                    </td>
                    <td
                        v-for="row in breakdown"
                        :key="`bank-loan-outstanding-${row.year}`"
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    >
                        {{ formatCurrency(row.bankLoanBalance) }}
                    </td>
                </tr>

                <tr
                    v-if="hasVariableLoan"
                    class="transition-colors hover:bg-muted/30"
                >
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-background px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
                    >
                        {{ flexibleLoanType }} Interest Rate
                    </th>
                    <td
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    ></td>
                    <td
                        v-for="row in breakdown"
                        :key="`variable-interest-rate-${row.year}`"
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    >
                        <template v-if="isEditableYear(row.year)">
                            <div class="flex flex-col items-end gap-1">
                                <div>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="20"
                                        :value="getBaseRateForYear(row.year)"
                                        class="w-16 rounded border border-input bg-background px-1 py-0.5 text-right text-sm"
                                        @change="handleRateChange(row.year, $event)"
                                    />%
                                </div>
                                <div class="text-xs text-muted-foreground/70">
                                    ({{ row.variableRateForYear.toFixed(2) }}%)
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            {{ row.variableRateForYear.toFixed(2) }}%
                        </template>
                    </td>
                </tr>

                <tr class="transition-colors hover:bg-muted/30">
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-background px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
                    >
                        Monthly Cost
                    </th>
                    <td
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    ></td>
                    <td
                        v-for="row in breakdown"
                        :key="`variable-monthly-cost-${row.year}`"
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    >
                        {{ formatCurrency(calculateTotalCost(row) / 12) }}
                    </td>
                </tr>

                <tr class="transition-colors hover:bg-muted/30">
                    <th
                        class="sticky left-0 z-10 border-r border-border bg-background px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
                    >
                        Effective Interest Rate
                    </th>
                    <td
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    ></td>
                    <td
                        v-for="(row, index) in breakdown"
                        :key="`effective-interest-rate-${row.year}`"
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    >
                        {{
                            calculateEffectiveInterestRate(row, index).toFixed(2)
                        }}%
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
