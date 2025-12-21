<script setup lang="ts">
import {
    store as storeMortgage,
    update as updateMortgage,
} from '@/actions/App/Http/Controllers/MortgageController';
import AmortizationTable from '@/components/AmortizationTable.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import CurrencyInput from '@/components/ui/input/CurrencyInput.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { useMortgageCalculator } from '@/composables/useMortgageCalculator';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

interface MortgageData {
    id: number;
    name: string;
    [key: string]: unknown;
}

const props = defineProps<{
    mortgage?: MortgageData;
}>();

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
    fixedLoanAmount,
    variableLoanAmount,
    bankLoanAmount,
    fixedLoanPlusBond,
    fixedBidragssats,
    variableBidragssats,
    fixedEffectiveRate,
    variableEffectiveRate,
    formatCurrency,
    formatNumber,
    isEditableYear,
    setVariableRateForYear,
    variableRateOverrides,
    exportData,
    loadFromData,
} = useMortgageCalculator();

const loanPeriodOptions = [10, 20, 30] as const;
const flexibleLoanTypes = ['F3', 'F5'] as const;

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const currentMortgageId = ref<number | null>(null);
const saveName = ref('');
const showSaveModal = ref(false);
const isSaving = ref(false);

async function saveConfiguration() {
    if (!saveName.value.trim()) return;
    isSaving.value = true;

    try {
        const data = {
            name: saveName.value.trim(),
            ...exportData(),
        };

        let response;
        if (currentMortgageId.value) {
            response = await fetch(
                updateMortgage.url(currentMortgageId.value),
                {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-XSRF-TOKEN': getCsrfToken(),
                    },
                    body: JSON.stringify(data),
                },
            );
        } else {
            response = await fetch(storeMortgage.url(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(data),
            });
        }

        if (response.ok) {
            const mortgage = await response.json();
            showSaveModal.value = false;

            // Navigate to the mortgage-simulation page for new configurations
            if (!currentMortgageId.value) {
                router.visit(`/mortgage-simulation/${mortgage.id}`);
            } else {
                currentMortgageId.value = mortgage.id;
            }
        }
    } catch (error) {
        console.error('Failed to save mortgage:', error);
    } finally {
        isSaving.value = false;
    }
}

function newConfiguration() {
    router.visit('/calculator');
}

function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

onMounted(() => {
    // Load mortgage from prop if provided (from /mortgage-simulation/{id} route)
    if (props.mortgage) {
        loadFromData(props.mortgage);
        currentMortgageId.value = props.mortgage.id;
        saveName.value = props.mortgage.name;
    }
});
</script>

<template>
    <Head title="Mortgage Calculator" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 lg:p-6">
            <div class="grid gap-6 lg:grid-cols-[400px_1fr]">
                <div class="flex flex-col gap-6">
                    <!-- Save/Load Configuration -->
                    <Card v-if="isAuthenticated">
                        <CardHeader>
                            <CardTitle>{{
                                saveName || 'New Calculation'
                            }}</CardTitle>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-4">
                            <div class="flex gap-2">
                                <button
                                    class="flex-1 rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                                    @click="showSaveModal = true"
                                >
                                    {{
                                        currentMortgageId
                                            ? 'Save Changes'
                                            : 'Save Configuration'
                                    }}
                                </button>
                                <button
                                    class="rounded-md border border-input bg-background px-3 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground"
                                    @click="newConfiguration"
                                >
                                    New
                                </button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Save Modal -->
                    <div
                        v-if="showSaveModal"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                        @click.self="showSaveModal = false"
                    >
                        <div
                            class="w-full max-w-md rounded-lg bg-background p-6 shadow-lg"
                        >
                            <h3 class="mb-4 text-lg font-semibold">
                                Save Configuration
                            </h3>
                            <div class="mb-4 flex flex-col gap-2">
                                <Label for="configName"
                                    >Configuration Name</Label
                                >
                                <Input
                                    id="configName"
                                    v-model="saveName"
                                    type="text"
                                    placeholder="e.g., House in Copenhagen"
                                    @keyup.enter="saveConfiguration"
                                />
                            </div>
                            <div class="flex justify-end gap-2">
                                <button
                                    class="rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent"
                                    @click="showSaveModal = false"
                                >
                                    Cancel
                                </button>
                                <button
                                    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                                    :disabled="!saveName.trim() || isSaving"
                                    @click="saveConfiguration"
                                >
                                    {{ isSaving ? 'Saving...' : 'Save' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Property Details</CardTitle>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="propertyValue">
                                    Property Value (DKK)
                                </Label>
                                <CurrencyInput
                                    id="propertyValue"
                                    v-model="inputs.propertyValue"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="downpayment">
                                    Your Downpayment (DKK)
                                </Label>
                                <CurrencyInput
                                    id="downpayment"
                                    v-model="inputs.downpayment"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="capitalNeeded">
                                    Capital Needed (DKK)
                                </Label>
                                <CurrencyInput
                                    id="capitalNeeded"
                                    :model-value="inputs.propertyValue * 0.2"
                                    readonly
                                    class="cursor-not-allowed bg-muted"
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
                                <CurrencyInput
                                    id="ejerudgift"
                                    v-model="inputs.ejerudgift"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="heating">Heating (DKK/month)</Label>
                                <CurrencyInput
                                    id="heating"
                                    v-model="inputs.heating"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="water">Water (DKK/month)</Label>
                                <CurrencyInput
                                    id="water"
                                    v-model="inputs.water"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="repairs">Repairs (DKK/month)</Label>
                                <CurrencyInput
                                    id="repairs"
                                    v-model="inputs.repairs"
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
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
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
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
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
                                    class="flex justify-between text-xs text-muted-foreground"
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
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
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
                                <input
                                    id="withRepayments"
                                    v-model="inputs.withRepayments"
                                    type="checkbox"
                                    class="size-4 rounded border border-input"
                                />
                                <Label
                                    for="withRepayments"
                                    class="cursor-pointer"
                                >
                                    With Repayments (Annuity)
                                </Label>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Bank Loan</CardTitle>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="bankLoanNeeded">
                                    Bank Loan Needed (DKK)
                                </Label>
                                <CurrencyInput
                                    id="bankLoanNeeded"
                                    :model-value="
                                        Math.max(
                                            0,
                                            inputs.propertyValue * 0.2 -
                                                inputs.downpayment,
                                        )
                                    "
                                    readonly
                                    class="cursor-not-allowed bg-muted"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="bankLoanInterest">
                                    Bank Loan Interest (%)
                                </Label>
                                <Input
                                    id="bankLoanInterest"
                                    v-model="inputs.bankLoanInterest"
                                    type="number"
                                    min="0"
                                    max="30"
                                    step="0.1"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="bankLoanPeriod">
                                    Bank Loan Period (years)
                                </Label>
                                <Input
                                    id="bankLoanPeriod"
                                    v-model="inputs.bankLoanPeriod"
                                    type="number"
                                    min="1"
                                    max="30"
                                    step="1"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Rent Cost</CardTitle>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="rentExpenses">
                                    Rent + expenses (DKK/month)
                                </Label>
                                <CurrencyInput
                                    id="rentExpenses"
                                    v-model="inputs.rentExpenses"
                                />
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

                            <div class="flex flex-col gap-2">
                                <Label for="bidragssatsAdjustment">
                                    Bidragssats Adjustment (%)
                                </Label>
                                <Input
                                    id="bidragssatsAdjustment"
                                    v-model="inputs.bidragssatsAdjustment"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                />
                            </div>

                            <div class="mt-2 border-t border-border pt-4">
                                <p
                                    class="mb-3 text-sm font-medium text-muted-foreground"
                                >
                                    Effective Rates (with Bidragssats)
                                </p>
                                <div class="flex flex-col gap-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground"
                                            >F30 (Fixed)</span
                                        >
                                        <span class="font-medium">
                                            {{ inputs.interestRateF30 }}% +
                                            {{ fixedBidragssats }}% =
                                            {{ fixedEffectiveRate.toFixed(2) }}%
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground"
                                            >{{
                                                inputs.flexibleLoanType
                                            }}
                                            (Variable)</span
                                        >
                                        <span class="font-medium">
                                            {{
                                                inputs.flexibleLoanType === 'F3'
                                                    ? inputs.interestRateF3
                                                    : inputs.interestRateF5
                                            }}% + {{ variableBidragssats }}% =
                                            {{
                                                variableEffectiveRate.toFixed(
                                                    2,
                                                )
                                            }}%
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-2 border-t border-border pt-4">
                                <p
                                    class="mb-3 text-sm font-medium text-muted-foreground"
                                >
                                    Bidragssats Reference
                                </p>
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr
                                            class="border-b text-muted-foreground"
                                        >
                                            <th
                                                class="pb-2 text-left font-medium"
                                            >
                                                Type
                                            </th>
                                            <th
                                                class="pb-2 text-right font-medium"
                                            >
                                                w/ Repay
                                            </th>
                                            <th
                                                class="pb-2 text-right font-medium"
                                            >
                                                No Repay
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-muted-foreground">
                                        <tr>
                                            <td class="py-1">F3</td>
                                            <td class="py-1 text-right">
                                                +1.05%
                                            </td>
                                            <td class="py-1 text-right">
                                                +1.38%
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="py-1">F5</td>
                                            <td class="py-1 text-right">
                                                +0.85%
                                            </td>
                                            <td class="py-1 text-right">
                                                +0.77%
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="py-1">F30</td>
                                            <td class="py-1 text-right">
                                                +0.68%
                                            </td>
                                            <td class="py-1 text-right">
                                                +1.57%
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Mortgage and Bonds</CardTitle>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="mortgageRequired">
                                    Mortgage Required (DKK)
                                </Label>
                                <CurrencyInput
                                    id="mortgageRequired"
                                    :model-value="summary.totalLoanAmount"
                                    readonly
                                    class="cursor-not-allowed bg-muted"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="f30NoRepay">
                                    F30 No Repay (%)
                                </Label>
                                <Input
                                    id="f30NoRepay"
                                    v-model="inputs.f30NoRepay"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="f30WithRepay">
                                    F30 w/ Repay (%)
                                </Label>
                                <Input
                                    id="f30WithRepay"
                                    v-model="inputs.f30WithRepay"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                />
                            </div>

                            <div class="mt-2 border-t border-border pt-4">
                                <p
                                    class="mb-3 text-sm font-medium text-muted-foreground"
                                >
                                    Interest Rate Split
                                </p>
                                <div class="flex flex-col gap-3">
                                    <div class="flex flex-col gap-2">
                                        <Label for="mortgageF30">
                                            Mortgage - F30 (DKK)
                                        </Label>
                                        <CurrencyInput
                                            id="mortgageF30"
                                            :model-value="fixedLoanAmount"
                                            readonly
                                            class="cursor-not-allowed bg-muted"
                                        />
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <Label for="mortgageF3F5">
                                            Mortgage - F3/F5 (DKK)
                                        </Label>
                                        <CurrencyInput
                                            id="mortgageF3F5"
                                            :model-value="variableLoanAmount"
                                            readonly
                                            class="cursor-not-allowed bg-muted"
                                        />
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <Label for="mortgageF30PlusBond">
                                            Mortgage - F30 plus bond (DKK)
                                        </Label>
                                        <CurrencyInput
                                            id="mortgageF30PlusBond"
                                            :model-value="fixedLoanPlusBond"
                                            readonly
                                            class="cursor-not-allowed bg-muted"
                                        />
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <Label for="totalMortgage">
                                            Total Mortgage (DKK)
                                        </Label>
                                        <CurrencyInput
                                            id="totalMortgage"
                                            :model-value="
                                                fixedLoanPlusBond +
                                                variableLoanAmount
                                            "
                                            readonly
                                            class="cursor-not-allowed bg-muted"
                                        />
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <Label for="extraBondPayment">
                                            Extra Bond Payment (DKK)
                                        </Label>
                                        <CurrencyInput
                                            id="extraBondPayment"
                                            :model-value="
                                                fixedLoanPlusBond -
                                                fixedLoanAmount
                                            "
                                            readonly
                                            class="cursor-not-allowed bg-muted"
                                        />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle
                                >Inflation - Yearly Adjustments</CardTitle
                            >
                        </CardHeader>
                        <CardContent class="flex flex-col gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="inflationEjerudgift">
                                    Ejerudgift (%)
                                </Label>
                                <Input
                                    id="inflationEjerudgift"
                                    v-model="inputs.inflationEjerudgift"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="inflationHeating">
                                    Heating (%)
                                </Label>
                                <Input
                                    id="inflationHeating"
                                    v-model="inputs.inflationHeating"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="inflationWater"> Water (%) </Label>
                                <Input
                                    id="inflationWater"
                                    v-model="inputs.inflationWater"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="inflationRepairs">
                                    Repairs (%)
                                </Label>
                                <Input
                                    id="inflationRepairs"
                                    v-model="inputs.inflationRepairs"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="inflationRent"> Rent (%) </Label>
                                <Input
                                    id="inflationRent"
                                    v-model="inputs.inflationRent"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="flex flex-col gap-6">
                    <Card class="max-w-7xl">
                        <CardHeader>
                            <CardTitle>Loan Summary</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                <div
                                    class="flex flex-col gap-1 rounded-lg bg-muted/50 p-4"
                                >
                                    <span class="text-sm text-muted-foreground">
                                        Total Loan Amount
                                    </span>
                                    <span class="text-xl font-semibold">
                                        {{
                                            formatCurrency(
                                                fixedLoanPlusBond +
                                                    variableLoanAmount,
                                            )
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="flex flex-col gap-1 rounded-lg bg-muted/50 p-4"
                                >
                                    <span class="text-sm text-muted-foreground">
                                        Fixed Loan (F30)
                                    </span>
                                    <span class="text-xl font-semibold">
                                        {{ formatCurrency(fixedLoanPlusBond) }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ inputs.interestRateF30 }}% +
                                        {{ fixedBidragssats }}% bidrag =
                                        {{ fixedEffectiveRate.toFixed(2) }}%
                                    </span>
                                </div>
                                <div
                                    class="flex flex-col gap-1 rounded-lg bg-muted/50 p-4"
                                >
                                    <span class="text-sm text-muted-foreground">
                                        Variable Loan ({{
                                            inputs.flexibleLoanType
                                        }})
                                    </span>
                                    <span class="text-xl font-semibold">
                                        {{
                                            formatCurrency(
                                                summary.variableLoanAmount,
                                            )
                                        }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        {{
                                            inputs.flexibleLoanType === 'F3'
                                                ? inputs.interestRateF3
                                                : inputs.interestRateF5
                                        }}% + {{ variableBidragssats }}% bidrag
                                        =
                                        {{ variableEffectiveRate.toFixed(2) }}%
                                    </span>
                                </div>
                                <div
                                    class="flex flex-col gap-1 rounded-lg bg-muted/50 p-4"
                                >
                                    <span class="text-sm text-muted-foreground">
                                        Total Interest Paid
                                    </span>
                                    <span class="text-xl font-semibold">
                                        {{
                                            formatCurrency(
                                                summary.totalInterestPaid,
                                            )
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="flex flex-col gap-1 rounded-lg bg-muted/50 p-4"
                                >
                                    <span class="text-sm text-muted-foreground">
                                        Total Amount Paid
                                    </span>
                                    <span class="text-xl font-semibold">
                                        {{
                                            formatCurrency(
                                                summary.totalAmountPaid,
                                            )
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="flex flex-col gap-1 rounded-lg bg-muted/50 p-4"
                                >
                                    <span class="text-sm text-muted-foreground">
                                        Monthly Utilities
                                    </span>
                                    <span class="text-xl font-semibold">
                                        {{
                                            formatCurrency(
                                                summary.monthlyUtilities,
                                            )
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="flex flex-col gap-1 rounded-lg bg-primary/10 p-4 sm:col-span-2 lg:col-span-3"
                                >
                                    <span class="text-sm text-muted-foreground">
                                        Average Monthly Housing Cost
                                    </span>
                                    <span
                                        class="text-2xl font-bold text-primary"
                                    >
                                        {{
                                            formatCurrency(
                                                summary.averageMonthlyHousingCost,
                                            )
                                        }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
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
                        <CardContent class="overflow-x-hidden p-0">
                            <AmortizationTable
                                :breakdown="yearlyBreakdown"
                                :format-currency="formatCurrency"
                                :format-number="formatNumber"
                                :ejerudgift="inputs.ejerudgift"
                                :heating="inputs.heating"
                                :water="inputs.water"
                                :repairs="inputs.repairs"
                                :rent-expenses="inputs.rentExpenses"
                                :variable-effective-rate="variableEffectiveRate"
                                :flexible-loan-type="inputs.flexibleLoanType"
                                :has-variable-loan="
                                    inputs.fixedMortgagePercentage < 100
                                "
                                :initial-fixed-balance="fixedLoanPlusBond"
                                :initial-variable-balance="variableLoanAmount"
                                :initial-bank-loan-balance="bankLoanAmount"
                                :is-editable-year="isEditableYear"
                                :set-variable-rate-for-year="
                                    setVariableRateForYear
                                "
                                :variable-rate-overrides="variableRateOverrides"
                            />
                        </CardContent>
                    </Card>

                    <Card v-else>
                        <CardContent class="py-12">
                            <p
                                class="text-center text-sm text-muted-foreground"
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
