<script setup lang="ts">
import { showMortgage } from '@/actions/App/Http/Controllers/CalculatorController';
import { destroy as deleteMortgage } from '@/actions/App/Http/Controllers/MortgageController';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { calculator, dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Mortgage {
    id: number;
    name: string;
    property_value: number;
    downpayment: number;
    loan_period_fixed: number;
    loan_period_variable: number;
    fixed_mortgage_percentage: number;
    flexible_loan_type: 'F3' | 'F5';
    with_repayments: boolean;
    interest_rate_f3: number;
    interest_rate_f5: number;
    interest_rate_f30: number;
    bidragssats_adjustment: number;
    f30_no_repay: number;
    f30_with_repay: number;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    mortgages: Mortgage[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const bidragssats = {
    F3: { withRepayments: 1.05, noRepayments: 1.38 },
    F5: { withRepayments: 0.85, noRepayments: 0.77 },
    F30: { withRepayments: 0.68, noRepayments: 1.57 },
};

function calculateMortgageSummary(mortgage: Mortgage) {
    const downpaymentPercentage =
        mortgage.downpayment / mortgage.property_value;
    const totalLoanAmount =
        downpaymentPercentage >= 0.2
            ? mortgage.property_value - mortgage.downpayment
            : mortgage.property_value * 0.8;

    const fixedLoanAmount =
        totalLoanAmount * (mortgage.fixed_mortgage_percentage / 100);
    const variableLoanAmount = totalLoanAmount - fixedLoanAmount;

    // Calculate bond adjustment for fixed loan
    const bondPercentage = mortgage.with_repayments
        ? mortgage.f30_with_repay
        : mortgage.f30_no_repay;
    const fixedLoanPlusBond = Math.round(
        fixedLoanAmount / (bondPercentage / 100),
    );

    // Calculate effective rates
    const fixedBaseBidragssats = mortgage.with_repayments
        ? bidragssats.F30.withRepayments
        : bidragssats.F30.noRepayments;
    const fixedBidragssats =
        fixedBaseBidragssats * (1 - mortgage.bidragssats_adjustment / 100);
    const fixedEffectiveRate = mortgage.interest_rate_f30 + fixedBidragssats;

    const variableBaseBidragssats = mortgage.with_repayments
        ? bidragssats[mortgage.flexible_loan_type].withRepayments
        : bidragssats[mortgage.flexible_loan_type].noRepayments;
    const variableBidragssats =
        variableBaseBidragssats * (1 - mortgage.bidragssats_adjustment / 100);
    const variableBaseRate =
        mortgage.flexible_loan_type === 'F3'
            ? mortgage.interest_rate_f3
            : mortgage.interest_rate_f5;
    const variableEffectiveRate = variableBaseRate + variableBidragssats;

    // Calculate totals (simplified - assumes linear repayment)
    const maxPeriod = Math.max(
        mortgage.loan_period_fixed,
        mortgage.loan_period_variable,
    );
    let totalInterestPaid = 0;
    let fixedBalance = fixedLoanPlusBond;
    let variableBalance = variableLoanAmount;

    const fixedYearlyPrincipal =
        mortgage.loan_period_fixed > 0
            ? fixedLoanPlusBond / mortgage.loan_period_fixed
            : 0;
    const variableYearlyPrincipal =
        mortgage.loan_period_variable > 0
            ? variableLoanAmount / mortgage.loan_period_variable
            : 0;

    for (let year = 1; year <= maxPeriod; year++) {
        // Fixed loan interest
        if (fixedBalance > 0 && year <= mortgage.loan_period_fixed) {
            totalInterestPaid += fixedBalance * (fixedEffectiveRate / 100);
            fixedBalance -= fixedYearlyPrincipal;
        }

        // Variable loan interest
        if (variableBalance > 0 && year <= mortgage.loan_period_variable) {
            totalInterestPaid +=
                variableBalance * (variableEffectiveRate / 100);
            variableBalance -= variableYearlyPrincipal;
        }
    }

    const totalAmountPaid =
        fixedLoanPlusBond + variableLoanAmount + totalInterestPaid;

    return {
        totalLoanAmount: fixedLoanPlusBond + variableLoanAmount,
        totalInterestPaid,
        totalAmountPaid,
        loanConfig: `${mortgage.fixed_mortgage_percentage}% Fixed / ${100 - mortgage.fixed_mortgage_percentage}% ${mortgage.flexible_loan_type}`,
    };
}

const mortgagesWithSummary = computed(() =>
    props.mortgages.map((mortgage) => ({
        ...mortgage,
        summary: calculateMortgageSummary(mortgage),
    })),
);

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('da-DK', {
        style: 'currency',
        currency: 'DKK',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
}

const showDeleteModal = ref(false);
const mortgageToDelete = ref<Mortgage | null>(null);
const isDeleting = ref(false);

function confirmDelete(mortgage: Mortgage) {
    mortgageToDelete.value = mortgage;
    showDeleteModal.value = true;
}

function cancelDelete() {
    showDeleteModal.value = false;
    mortgageToDelete.value = null;
}

function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function performDelete() {
    if (!mortgageToDelete.value) return;

    isDeleting.value = true;
    try {
        const response = await fetch(deleteMortgage.url(mortgageToDelete.value.id), {
            method: 'DELETE',
            headers: {
                'X-XSRF-TOKEN': getCsrfToken(),
            },
        });

        if (response.ok) {
            showDeleteModal.value = false;
            mortgageToDelete.value = null;
            router.reload();
        }
    } catch (error) {
        console.error('Failed to delete mortgage:', error);
    } finally {
        isDeleting.value = false;
    }
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 lg:p-6">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle>Saved Mortgage Calculations</CardTitle>
                    <Link
                        :href="calculator().url"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        New Calculation
                    </Link>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="mortgagesWithSummary.length > 0"
                        class="overflow-x-auto"
                    >
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b text-left text-muted-foreground"
                                >
                                    <th class="px-4 py-3 font-medium">Name</th>
                                    <th
                                        class="px-4 py-3 text-right font-medium"
                                    >
                                        Property Value
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right font-medium"
                                    >
                                        Downpayment
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Loan Configuration
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right font-medium"
                                    >
                                        Total Loan
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right font-medium"
                                    >
                                        Total Interest
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right font-medium"
                                    >
                                        Total Amount
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right font-medium"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr
                                    v-for="mortgage in mortgagesWithSummary"
                                    :key="mortgage.id"
                                    class="hover:bg-muted/50"
                                >
                                    <td class="px-4 py-3 font-medium">
                                        {{ mortgage.name }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right text-muted-foreground"
                                    >
                                        {{
                                            formatCurrency(
                                                mortgage.property_value,
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right text-muted-foreground"
                                    >
                                        {{
                                            formatCurrency(mortgage.downpayment)
                                        }}
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">
                                        {{ mortgage.summary.loanConfig }}
                                        <span class="text-xs">
                                            ({{
                                                mortgage.with_repayments
                                                    ? 'w/ repay'
                                                    : 'no repay'
                                            }})
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right text-muted-foreground"
                                    >
                                        {{
                                            formatCurrency(
                                                mortgage.summary.totalLoanAmount,
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right text-muted-foreground"
                                    >
                                        {{
                                            formatCurrency(
                                                mortgage.summary
                                                    .totalInterestPaid,
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-medium"
                                    >
                                        {{
                                            formatCurrency(
                                                mortgage.summary
                                                    .totalAmountPaid,
                                            )
                                        }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <Link
                                                :href="
                                                    showMortgage(mortgage.id).url
                                                "
                                                class="text-primary hover:underline"
                                            >
                                                View
                                            </Link>
                                            <button
                                                class="text-destructive hover:underline"
                                                @click="confirmDelete(mortgage)"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="py-12 text-center text-muted-foreground">
                        <p class="mb-4">
                            You haven't saved any mortgage calculations yet.
                        </p>
                        <Link
                            :href="calculator().url"
                            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Create Your First Calculation
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="cancelDelete"
        >
            <div class="w-full max-w-md rounded-lg bg-background p-6 shadow-lg">
                <h3 class="mb-2 text-lg font-semibold">Delete Calculation</h3>
                <p class="mb-6 text-muted-foreground">
                    Are you sure you want to delete
                    <span class="font-medium text-foreground">{{
                        mortgageToDelete?.name
                    }}</span
                    >? This action cannot be undone.
                </p>
                <div class="flex justify-end gap-2">
                    <button
                        class="rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent"
                        :disabled="isDeleting"
                        @click="cancelDelete"
                    >
                        Cancel
                    </button>
                    <button
                        class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50"
                        :disabled="isDeleting"
                        @click="performDelete"
                    >
                        {{ isDeleting ? 'Deleting...' : 'Delete' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
