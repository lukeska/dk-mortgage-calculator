<script setup lang="ts">
import type { YearlyBreakdown } from '@/composables/useMortgageCalculator';

defineProps<{
    breakdown: YearlyBreakdown[];
    formatCurrency: (value: number) => string;
    formatNumber: (value: number) => string;
}>();
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr
                    class="border-border bg-muted/50 border-b text-left text-xs font-medium uppercase tracking-wider"
                >
                    <th class="px-3 py-3">Year</th>
                    <th class="px-3 py-3 text-right">Remaining Balance</th>
                    <th class="px-3 py-3 text-right">Yearly Payment</th>
                    <th class="px-3 py-3 text-right">Interest</th>
                    <th class="px-3 py-3 text-right">Principal</th>
                    <th class="px-3 py-3 text-right">Monthly Payment</th>
                    <th class="px-3 py-3 text-right">Monthly Housing Cost</th>
                </tr>
            </thead>
            <tbody class="divide-border divide-y">
                <tr
                    v-for="row in breakdown"
                    :key="row.year"
                    class="hover:bg-muted/30 transition-colors"
                >
                    <td class="px-3 py-3 font-medium">
                        {{ row.year }}
                    </td>
                    <td class="text-muted-foreground px-3 py-3 text-right">
                        {{ formatCurrency(row.totalBalance) }}
                    </td>
                    <td class="px-3 py-3 text-right">
                        {{ formatCurrency(row.totalPayment) }}
                    </td>
                    <td class="text-muted-foreground px-3 py-3 text-right">
                        {{ formatCurrency(row.fixedInterest + row.variableInterest) }}
                    </td>
                    <td class="text-muted-foreground px-3 py-3 text-right">
                        {{ formatCurrency(row.fixedPrincipal + row.variablePrincipal) }}
                    </td>
                    <td class="px-3 py-3 text-right font-medium">
                        {{ formatCurrency(row.monthlyPayment) }}
                    </td>
                    <td class="text-primary px-3 py-3 text-right font-semibold">
                        {{ formatCurrency(row.monthlyHousingCost) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
