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
}>();

interface OwnershipCostRow {
    key: string;
    label: string;
    getValue: () => number;
}

const ownershipCostRows: OwnershipCostRow[] = [
    {
        key: 'ejerudgift',
        label: 'Ejerudgift',
        getValue: () => props.ejerudgift * 12,
    },
    { key: 'heating', label: 'Heating', getValue: () => props.heating * 12 },
    { key: 'water', label: 'Water', getValue: () => props.water * 12 },
    { key: 'repairs', label: 'Repairs', getValue: () => props.repairs * 12 },
];

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
    const totalInterest = row.fixedInterest + row.variableInterest + row.bankLoanInterest;
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
        props.ejerudgift * 12 +
        props.heating * 12 +
        props.water * 12 +
        props.repairs * 12;
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

function calculateYearlyRent(): number {
    return props.rentExpenses * 12;
}

function calculateBaseIncomeDelta(row: YearlyBreakdown): number {
    return calculateYearlyRent() - calculateTotalCost(row);
}

function calculateEffectiveInterestRate(row: YearlyBreakdown): number {
    const totalBalance = row.fixedBalance + row.variableBalance + row.bankLoanBalance;
    if (totalBalance <= 0) {
        return 0;
    }
    const totalInterest = row.fixedInterest + row.variableInterest + row.bankLoanInterest;
    const deduction = calculateInterestDeduction(row);
    return ((totalInterest - deduction) / totalBalance) * 100;
}
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <tbody class="divide-y divide-border">
                <!-- Year Header Row -->
                <tr class="bg-muted/50">
                    <th
                        class="border-r border-border px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
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
                        class="border-r border-border px-3 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-amber-800 uppercase dark:text-amber-200"
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
                    v-for="costRow in ownershipCostRows"
                    :key="costRow.key"
                    class="transition-colors hover:bg-muted/30"
                >
                    <th
                        class="border-r border-border px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
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
                        {{ formatCurrency(costRow.getValue()) }}
                    </td>
                </tr>

                <!-- Mortgage Section -->
                <tr class="bg-blue-100 dark:bg-blue-900/30">
                    <th
                        class="border-r border-border px-3 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-blue-800 uppercase dark:text-blue-200"
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
                        class="border-r border-border px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
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
                        class="border-r border-border px-3 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-green-800 uppercase dark:text-green-200"
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
                        class="border-r border-border px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
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
                        class="border-r border-border px-3 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-purple-800 uppercase dark:text-purple-200"
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
                        class="border-r border-border px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
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
                        class="border-r border-border px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
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
                        {{ formatCurrency(calculateYearlyRent()) }}
                    </td>
                </tr>

                <tr class="transition-colors hover:bg-muted/30">
                    <th
                        class="border-r border-border px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
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
                        class="border-r border-border px-3 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-slate-800 uppercase dark:text-slate-200"
                    >
                        Other stuff
                    </th>
                    <td class="px-3 py-3"></td>
                    <td
                        v-for="row in breakdown"
                        :key="`other-stuff-header-${row.year}`"
                        class="px-3 py-3"
                    ></td>
                </tr>

                <tr class="transition-colors hover:bg-muted/30">
                    <th
                        class="border-r border-border px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
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
                        class="border-r border-border px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
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
                        class="border-r border-border px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
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
                        class="border-r border-border px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
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
                        {{ variableEffectiveRate.toFixed(2) }}%
                    </td>
                </tr>

                <tr class="transition-colors hover:bg-muted/30">
                    <th
                        class="border-r border-border px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
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
                        class="border-r border-border px-3 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap uppercase"
                    >
                        Effective Interest Rate
                    </th>
                    <td
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    ></td>
                    <td
                        v-for="row in breakdown"
                        :key="`effective-interest-rate-${row.year}`"
                        class="px-3 py-3 text-right whitespace-nowrap text-muted-foreground"
                    >
                        {{ calculateEffectiveInterestRate(row).toFixed(2) }}%
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
