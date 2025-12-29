<script setup lang="ts">
import { showMortgage } from '@/actions/App/Http/Controllers/CalculatorController';
import { destroy as deleteMortgage } from '@/actions/App/Http/Controllers/MortgageController';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard, home } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Mortgage {
    id: number;
    name: string;
    property_value: number;
    downpayment: number;
    fixed_mortgage_percentage: number;
    flexible_loan_type: 'F3' | 'F5';
    with_repayments: boolean;
    loan_total_amount: number;
    fixed_loan_total_amount: number;
    variable_loan_total_amount: number;
    bank_loan_total_amount: number;
    fixed_interest_total_amount: number;
    variable_interest_total_amount: number;
    bank_interest_total_amount: number;
    first_year_tax_deduction: number;
    first_year_monthly_cost: number;
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

function getLoanConfig(mortgage: Mortgage): string {
    const fixedPct = mortgage.fixed_mortgage_percentage ?? 100;
    const loanType = mortgage.flexible_loan_type || 'F5';
    return `${fixedPct}% Fixed / ${100 - fixedPct}% ${loanType}`;
}

function getTotalInterest(mortgage: Mortgage): number {
    return (
        mortgage.fixed_interest_total_amount +
        mortgage.variable_interest_total_amount +
        mortgage.bank_interest_total_amount
    );
}

function getTotalAmountPaid(mortgage: Mortgage): number {
    return mortgage.loan_total_amount + getTotalInterest(mortgage);
}

function getFirstYearMonthlyCostNet(mortgage: Mortgage): number {
    return (
        mortgage.first_year_monthly_cost -
        mortgage.first_year_tax_deduction / 12
    );
}

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
        const response = await fetch(
            deleteMortgage.url(mortgageToDelete.value.id),
            {
                method: 'DELETE',
                headers: {
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
            },
        );

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
                        :href="home().url"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        New Calculation
                    </Link>
                </CardHeader>
                <CardContent>
                    <div v-if="mortgages.length > 0" class="overflow-x-auto">
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
                                        1st Year Monthly
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
                                    v-for="mortgage in mortgages"
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
                                        {{ getLoanConfig(mortgage) }}
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
                                                mortgage.loan_total_amount,
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right text-muted-foreground"
                                    >
                                        {{
                                            formatCurrency(
                                                getTotalInterest(mortgage),
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-medium"
                                    >
                                        {{
                                            formatCurrency(
                                                getTotalAmountPaid(mortgage),
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-medium text-primary"
                                    >
                                        {{
                                            formatCurrency(
                                                getFirstYearMonthlyCostNet(
                                                    mortgage,
                                                ),
                                            )
                                        }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div
                                            class="flex items-center justify-end gap-3"
                                        >
                                            <Link
                                                :href="
                                                    showMortgage(mortgage.id)
                                                        .url
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
                            :href="home().url"
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
