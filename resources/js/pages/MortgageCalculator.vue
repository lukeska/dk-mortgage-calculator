<script setup lang="ts">
import AmortizationTable from '@/components/AmortizationTable.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { useMortgageCalculator } from '@/composables/useMortgageCalculator';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mortgage Calculator',
        href: '/calculator',
    },
];

const {
    inputs,
    summary,
    yearlyBreakdown,
    formatCurrency,
    formatNumber,
} = useMortgageCalculator();

const loanPeriodOptions = [10, 20, 30] as const;
const flexibleLoanTypes = ['F3', 'F5'] as const;
</script>

<template>
    <Head title="Mortgage Calculator" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 lg:p-6">
            <div class="grid gap-6 lg:grid-cols-[400px_1fr]">
                <div class="flex flex-col gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Property Details</CardTitle>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="propertyValue">
                                    Property Value (DKK)
                                </Label>
                                <Input
                                    id="propertyValue"
                                    v-model="inputs.propertyValue"
                                    type="number"
                                    min="0"
                                    step="10000"
                                    placeholder="e.g. 3000000"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="downpayment">
                                    Your Downpayment (DKK)
                                </Label>
                                <Input
                                    id="downpayment"
                                    v-model="inputs.downpayment"
                                    type="number"
                                    min="0"
                                    step="10000"
                                    placeholder="e.g. 150000"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Monthly Expenses</CardTitle>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="ejerudgift">
                                    Ejerudgift (DKK/month)
                                </Label>
                                <Input
                                    id="ejerudgift"
                                    v-model="inputs.ejerudgift"
                                    type="number"
                                    min="0"
                                    step="100"
                                    placeholder="e.g. 2500"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="heating">Heating (DKK/month)</Label>
                                <Input
                                    id="heating"
                                    v-model="inputs.heating"
                                    type="number"
                                    min="0"
                                    step="100"
                                    placeholder="e.g. 500"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="water">Water (DKK/month)</Label>
                                <Input
                                    id="water"
                                    v-model="inputs.water"
                                    type="number"
                                    min="0"
                                    step="100"
                                    placeholder="e.g. 200"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="repairs">Repairs (DKK/month)</Label>
                                <Input
                                    id="repairs"
                                    v-model="inputs.repairs"
                                    type="number"
                                    min="0"
                                    step="100"
                                    placeholder="e.g. 500"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Loan Configuration</CardTitle>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="loanPeriodFixed">
                                    Loan Period - Fixed (F30)
                                </Label>
                                <select
                                    id="loanPeriodFixed"
                                    v-model="inputs.loanPeriodFixed"
                                    class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                                >
                                    <option
                                        v-for="period in loanPeriodOptions"
                                        :key="period"
                                        :value="period"
                                    >
                                        {{ period }} years
                                    </option>
                                </select>
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="loanPeriodVariable">
                                    Loan Period - Variable
                                </Label>
                                <select
                                    id="loanPeriodVariable"
                                    v-model="inputs.loanPeriodVariable"
                                    class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                                >
                                    <option
                                        v-for="period in loanPeriodOptions"
                                        :key="period"
                                        :value="period"
                                    >
                                        {{ period }} years
                                    </option>
                                </select>
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="fixedPercentage">
                                    Fixed Mortgage Percentage:
                                    {{ inputs.fixedMortgagePercentage }}%
                                </Label>
                                <input
                                    id="fixedPercentage"
                                    v-model="inputs.fixedMortgagePercentage"
                                    type="range"
                                    min="0"
                                    max="100"
                                    step="5"
                                    class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200 accent-primary dark:bg-gray-700"
                                />
                                <div
                                    class="text-muted-foreground flex justify-between text-xs"
                                >
                                    <span>0% Fixed</span>
                                    <span>100% Fixed</span>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="flexibleLoanType">
                                    Flexible Loan Type
                                </Label>
                                <select
                                    id="flexibleLoanType"
                                    v-model="inputs.flexibleLoanType"
                                    class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                                >
                                    <option
                                        v-for="loanType in flexibleLoanTypes"
                                        :key="loanType"
                                        :value="loanType"
                                    >
                                        {{ loanType }}
                                    </option>
                                </select>
                            </div>

                            <div class="flex items-center gap-3">
                                <Checkbox
                                    id="withRepayments"
                                    :checked="inputs.withRepayments"
                                    @update:checked="
                                        inputs.withRepayments = $event as boolean
                                    "
                                />
                                <Label for="withRepayments" class="cursor-pointer">
                                    With Repayments (Annuity)
                                </Label>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Interest Rates</CardTitle>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="interestRateF3">
                                    Interest Rate F3 (%)
                                </Label>
                                <Input
                                    id="interestRateF3"
                                    v-model="inputs.interestRateF3"
                                    type="number"
                                    min="0"
                                    max="20"
                                    step="0.01"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="interestRateF5">
                                    Interest Rate F5 (%)
                                </Label>
                                <Input
                                    id="interestRateF5"
                                    v-model="inputs.interestRateF5"
                                    type="number"
                                    min="0"
                                    max="20"
                                    step="0.01"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="interestRateF30">
                                    Interest Rate F30 (%)
                                </Label>
                                <Input
                                    id="interestRateF30"
                                    v-model="inputs.interestRateF30"
                                    type="number"
                                    min="0"
                                    max="20"
                                    step="0.01"
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="flex flex-col gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Loan Summary</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <div
                                    class="bg-muted/50 flex flex-col gap-1 rounded-lg p-4"
                                >
                                    <span
                                        class="text-muted-foreground text-sm"
                                    >
                                        Total Loan Amount
                                    </span>
                                    <span class="text-xl font-semibold">
                                        {{ formatCurrency(summary.totalLoanAmount) }}
                                    </span>
                                </div>
                                <div
                                    class="bg-muted/50 flex flex-col gap-1 rounded-lg p-4"
                                >
                                    <span
                                        class="text-muted-foreground text-sm"
                                    >
                                        Fixed Loan (F30)
                                    </span>
                                    <span class="text-xl font-semibold">
                                        {{ formatCurrency(summary.fixedLoanAmount) }}
                                    </span>
                                    <span class="text-muted-foreground text-xs">
                                        {{ inputs.fixedMortgagePercentage }}% at
                                        {{ inputs.interestRateF30 }}%
                                    </span>
                                </div>
                                <div
                                    class="bg-muted/50 flex flex-col gap-1 rounded-lg p-4"
                                >
                                    <span
                                        class="text-muted-foreground text-sm"
                                    >
                                        Variable Loan ({{ inputs.flexibleLoanType }})
                                    </span>
                                    <span class="text-xl font-semibold">
                                        {{ formatCurrency(summary.variableLoanAmount) }}
                                    </span>
                                    <span class="text-muted-foreground text-xs">
                                        {{ 100 - inputs.fixedMortgagePercentage }}% at
                                        {{
                                            inputs.flexibleLoanType === 'F3'
                                                ? inputs.interestRateF3
                                                : inputs.interestRateF5
                                        }}%
                                    </span>
                                </div>
                                <div
                                    class="bg-muted/50 flex flex-col gap-1 rounded-lg p-4"
                                >
                                    <span
                                        class="text-muted-foreground text-sm"
                                    >
                                        Total Interest Paid
                                    </span>
                                    <span class="text-xl font-semibold">
                                        {{ formatCurrency(summary.totalInterestPaid) }}
                                    </span>
                                </div>
                                <div
                                    class="bg-muted/50 flex flex-col gap-1 rounded-lg p-4"
                                >
                                    <span
                                        class="text-muted-foreground text-sm"
                                    >
                                        Total Amount Paid
                                    </span>
                                    <span class="text-xl font-semibold">
                                        {{ formatCurrency(summary.totalAmountPaid) }}
                                    </span>
                                </div>
                                <div
                                    class="bg-muted/50 flex flex-col gap-1 rounded-lg p-4"
                                >
                                    <span
                                        class="text-muted-foreground text-sm"
                                    >
                                        Monthly Utilities
                                    </span>
                                    <span class="text-xl font-semibold">
                                        {{ formatCurrency(summary.monthlyUtilities) }}
                                    </span>
                                </div>
                                <div
                                    class="bg-primary/10 flex flex-col gap-1 rounded-lg p-4 sm:col-span-2 lg:col-span-3"
                                >
                                    <span
                                        class="text-muted-foreground text-sm"
                                    >
                                        Average Monthly Housing Cost
                                    </span>
                                    <span class="text-primary text-2xl font-bold">
                                        {{ formatCurrency(summary.averageMonthlyHousingCost) }}
                                    </span>
                                    <span class="text-muted-foreground text-xs">
                                        Mortgage payment + utilities
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="yearlyBreakdown.length > 0">
                        <CardHeader>
                            <CardTitle>Amortization Schedule</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <AmortizationTable
                                :breakdown="yearlyBreakdown"
                                :format-currency="formatCurrency"
                                :format-number="formatNumber"
                            />
                        </CardContent>
                    </Card>

                    <Card v-else>
                        <CardContent class="py-12">
                            <p
                                class="text-muted-foreground text-center text-sm"
                            >
                                Enter property value and downpayment to see the
                                amortization schedule.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
