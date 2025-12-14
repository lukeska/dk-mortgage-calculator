import { computed, reactive } from 'vue';

export type LoanPeriod = 10 | 20 | 30;
export type FlexibleLoanType = 'F3' | 'F5';

export interface MortgageInputs {
    propertyValue: number;
    ejerudgift: number;
    heating: number;
    water: number;
    repairs: number;
    rentExpenses: number;
    downpayment: number;
    loanPeriodFixed: LoanPeriod;
    loanPeriodVariable: LoanPeriod;
    fixedMortgagePercentage: number;
    flexibleLoanType: FlexibleLoanType;
    withRepayments: boolean;
    interestRateF3: number;
    interestRateF5: number;
    interestRateF30: number;
    bidragssatsAdjustment: number;
    f30NoRepay: number;
    f30WithRepay: number;
    bankLoanInterest: number;
    bankLoanPeriod: number;
    inflationEjerudgift: number;
    inflationHeating: number;
    inflationWater: number;
    inflationRepairs: number;
    inflationRent: number;
}

export interface YearlyBreakdown {
    year: number;
    fixedBalance: number;
    variableBalance: number;
    bankLoanBalance: number;
    totalBalance: number;
    fixedPayment: number;
    variablePayment: number;
    bankLoanPayment: number;
    totalPayment: number;
    fixedInterest: number;
    variablePrincipal: number;
    fixedPrincipal: number;
    variableInterest: number;
    bankLoanInterest: number;
    bankLoanPrincipal: number;
    monthlyPayment: number;
    monthlyHousingCost: number;
    ejerudgift: number;
    heating: number;
    water: number;
    repairs: number;
    rentExpenses: number;
    monthlyUtilities: number;
    variableRateForYear: number;
}

export interface MortgageSummary {
    totalLoanAmount: number;
    fixedLoanAmount: number;
    variableLoanAmount: number;
    totalInterestPaid: number;
    totalAmountPaid: number;
    averageMonthlyPayment: number;
    averageMonthlyHousingCost: number;
    monthlyUtilities: number;
}

const defaultInputs: MortgageInputs = {
    propertyValue: 6000000,
    ejerudgift: 4000,
    heating: 1000,
    water: 0,
    repairs: 0,
    rentExpenses: 15000,
    downpayment: 1000000,
    loanPeriodFixed: 30,
    loanPeriodVariable: 30,
    fixedMortgagePercentage: 100,
    flexibleLoanType: 'F5',
    withRepayments: true,
    interestRateF3: 3.59,
    interestRateF5: 3.49,
    interestRateF30: 5,
    bidragssatsAdjustment: 0,
    f30NoRepay: 91,
    f30WithRepay: 96,
    bankLoanInterest: 6,
    bankLoanPeriod: 10,
    inflationEjerudgift: 2,
    inflationHeating: 2,
    inflationWater: 2,
    inflationRepairs: 2,
    inflationRent: 2,
};

const bidragssats = {
    F3: { withRepayments: 1.05, withoutRepayments: 1.38 },
    F5: { withRepayments: 0.85, withoutRepayments: 0.77 },
    F30: { withRepayments: 0.68, withoutRepayments: 1.57 },
};

export function useMortgageCalculator() {
    const inputs = reactive<MortgageInputs>({ ...defaultInputs });
    const variableRateOverrides = reactive<Record<number, number>>({});

    const totalLoanAmount = computed(() => {
        const maxMortgage = inputs.propertyValue * 0.8;
        const downpaymentBasedLoan = inputs.propertyValue - inputs.downpayment;
        // If downpayment covers more than 20%, use property - downpayment
        // Otherwise cap at 80% of property value
        return Math.max(0, Math.min(downpaymentBasedLoan, maxMortgage));
    });

    const fixedLoanAmount = computed(() => {
        return (totalLoanAmount.value * inputs.fixedMortgagePercentage) / 100;
    });

    const variableLoanAmount = computed(() => {
        return totalLoanAmount.value - fixedLoanAmount.value;
    });

    const variableBaseRate = computed(() => {
        return inputs.flexibleLoanType === 'F3'
            ? inputs.interestRateF3
            : inputs.interestRateF5;
    });

    const fixedBidragssats = computed(() => {
        const baseBidragssats = inputs.withRepayments
            ? bidragssats.F30.withRepayments
            : bidragssats.F30.withoutRepayments;
        const adjustment = baseBidragssats * (inputs.bidragssatsAdjustment / 100);
        return baseBidragssats - adjustment;
    });

    const variableBidragssats = computed(() => {
        const loanType = inputs.flexibleLoanType;
        const baseBidragssats = inputs.withRepayments
            ? bidragssats[loanType].withRepayments
            : bidragssats[loanType].withoutRepayments;
        const adjustment = baseBidragssats * (inputs.bidragssatsAdjustment / 100);
        return baseBidragssats - adjustment;
    });

    function isEditableYear(year: number): boolean {
        if (inputs.flexibleLoanType === 'F5') {
            // F5: editable at years 6, 11, 16, 21, 26 (every 5 years starting from 6)
            return year >= 6 && (year - 6) % 5 === 0;
        } else {
            // F3: editable at years 4, 7, 10, 13, ... (every 3 years starting from 4)
            return year >= 4 && (year - 4) % 3 === 0;
        }
    }

    function getVariableRateForYear(year: number): number {
        // Find the most recent override at or before this year
        let effectiveRate = variableBaseRate.value;
        const overrideYears = Object.keys(variableRateOverrides)
            .map(Number)
            .filter((y) => y <= year)
            .sort((a, b) => a - b);

        if (overrideYears.length > 0) {
            const lastOverrideYear = overrideYears[overrideYears.length - 1];
            effectiveRate = variableRateOverrides[lastOverrideYear];
        }

        return effectiveRate;
    }

    function setVariableRateForYear(year: number, rate: number) {
        variableRateOverrides[year] = rate;
    }

    function getEffectiveVariableRateForYear(year: number): number {
        const baseRate = getVariableRateForYear(year);
        return baseRate + variableBidragssats.value;
    }

    const fixedEffectiveRate = computed(() => {
        return inputs.interestRateF30 + fixedBidragssats.value;
    });

    const variableEffectiveRate = computed(() => {
        return variableBaseRate.value + variableBidragssats.value;
    });

    const monthlyUtilities = computed(() => {
        return (
            inputs.ejerudgift + inputs.heating + inputs.water + inputs.repairs
        );
    });

    const bankLoanAmount = computed(() => {
        const capitalNeeded = inputs.propertyValue * 0.2;
        return Math.max(0, capitalNeeded - inputs.downpayment);
    });

    const fixedLoanPlusBond = computed(() => {
        const bondPercentage = inputs.withRepayments ? inputs.f30WithRepay : inputs.f30NoRepay;
        return Math.round((100 * fixedLoanAmount.value) / bondPercentage);
    });

    const yearlyBreakdown = computed<YearlyBreakdown[]>(() => {
        const breakdown: YearlyBreakdown[] = [];

        if (totalLoanAmount.value <= 0) {
            return breakdown;
        }

        let fixedBalance = fixedLoanPlusBond.value;
        let variableBalance = variableLoanAmount.value;
        let bankBalance = bankLoanAmount.value;

        let currentEjerudgift = inputs.ejerudgift * 12;
        let currentHeating = inputs.heating * 12;
        let currentWater = inputs.water * 12;
        let currentRepairs = inputs.repairs * 12;
        let currentRent = inputs.rentExpenses * 12;

        const maxYears = Math.max(
            inputs.loanPeriodFixed,
            inputs.loanPeriodVariable,
            inputs.bankLoanPeriod,
        );

        const interestOnlyYears = 10;
        const fixedRepaymentYears = inputs.loanPeriodFixed - interestOnlyYears;
        const variableRepaymentYears =
            inputs.loanPeriodVariable - interestOnlyYears;

        const yearlyFixedPrincipalAmount = inputs.withRepayments
            ? fixedLoanPlusBond.value / inputs.loanPeriodFixed
            : fixedRepaymentYears > 0
              ? fixedLoanPlusBond.value / fixedRepaymentYears
              : 0;

        const yearlyVariablePrincipalAmount = inputs.withRepayments
            ? variableLoanAmount.value / inputs.loanPeriodVariable
            : variableRepaymentYears > 0
              ? variableLoanAmount.value / variableRepaymentYears
              : 0;

        const yearlyBankLoanPrincipalAmount =
            inputs.bankLoanPeriod > 0
                ? bankLoanAmount.value / inputs.bankLoanPeriod
                : 0;

        for (let year = 1; year <= maxYears; year++) {
            let yearlyFixedInterest = 0;
            let yearlyVariableInterest = 0;
            let yearlyFixedPrincipal = 0;
            let yearlyVariablePrincipal = 0;
            let yearlyBankLoanInterest = 0;
            let yearlyBankLoanPrincipal = 0;

            const isInterestOnlyYear =
                !inputs.withRepayments && year <= interestOnlyYears;

            if (fixedBalance > 0 && year <= inputs.loanPeriodFixed) {
                yearlyFixedInterest =
                    fixedBalance * (fixedEffectiveRate.value / 100);
                if (!isInterestOnlyYear) {
                    yearlyFixedPrincipal = Math.min(
                        yearlyFixedPrincipalAmount,
                        fixedBalance,
                    );
                }
                fixedBalance = Math.max(0, fixedBalance - yearlyFixedPrincipal);
            }

            const effectiveVariableRateForYear = getEffectiveVariableRateForYear(year);

            if (variableBalance > 0 && year <= inputs.loanPeriodVariable) {
                yearlyVariableInterest =
                    variableBalance * (effectiveVariableRateForYear / 100);
                if (!isInterestOnlyYear) {
                    yearlyVariablePrincipal = Math.min(
                        yearlyVariablePrincipalAmount,
                        variableBalance,
                    );
                }
                variableBalance = Math.max(
                    0,
                    variableBalance - yearlyVariablePrincipal,
                );
            }

            if (bankBalance > 0 && year <= inputs.bankLoanPeriod) {
                yearlyBankLoanInterest =
                    bankBalance * (inputs.bankLoanInterest / 100);
                yearlyBankLoanPrincipal = Math.min(
                    yearlyBankLoanPrincipalAmount,
                    bankBalance,
                );
                bankBalance = Math.max(
                    0,
                    bankBalance - yearlyBankLoanPrincipal,
                );
            }

            const yearlyFixedPayment =
                yearlyFixedInterest + yearlyFixedPrincipal;
            const yearlyVariablePayment =
                yearlyVariableInterest + yearlyVariablePrincipal;
            const yearlyBankLoanPayment =
                yearlyBankLoanInterest + yearlyBankLoanPrincipal;

            const totalYearlyPayment =
                yearlyFixedPayment +
                yearlyVariablePayment +
                yearlyBankLoanPayment;
            const monthlyPayment = totalYearlyPayment / 12;

            const yearlyUtilities =
                currentEjerudgift +
                currentHeating +
                currentWater +
                currentRepairs;

            breakdown.push({
                year,
                fixedBalance,
                variableBalance,
                bankLoanBalance: bankBalance,
                totalBalance: fixedBalance + variableBalance + bankBalance,
                fixedPayment: yearlyFixedPayment,
                variablePayment: yearlyVariablePayment,
                bankLoanPayment: yearlyBankLoanPayment,
                totalPayment: totalYearlyPayment,
                fixedInterest: yearlyFixedInterest,
                variableInterest: yearlyVariableInterest,
                bankLoanInterest: yearlyBankLoanInterest,
                fixedPrincipal: yearlyFixedPrincipal,
                variablePrincipal: yearlyVariablePrincipal,
                bankLoanPrincipal: yearlyBankLoanPrincipal,
                monthlyPayment,
                monthlyHousingCost: monthlyPayment + yearlyUtilities / 12,
                ejerudgift: currentEjerudgift,
                heating: currentHeating,
                water: currentWater,
                repairs: currentRepairs,
                rentExpenses: currentRent,
                monthlyUtilities: yearlyUtilities / 12,
                variableRateForYear: effectiveVariableRateForYear,
            });

            // Apply inflation for next year
            currentEjerudgift *= 1 + inputs.inflationEjerudgift / 100;
            currentHeating *= 1 + inputs.inflationHeating / 100;
            currentWater *= 1 + inputs.inflationWater / 100;
            currentRepairs *= 1 + inputs.inflationRepairs / 100;
            currentRent *= 1 + inputs.inflationRent / 100;

            if (fixedBalance <= 0 && variableBalance <= 0 && bankBalance <= 0) {
                break;
            }
        }

        return breakdown;
    });

    const summary = computed<MortgageSummary>(() => {
        const totalInterestPaid = yearlyBreakdown.value.reduce(
            (sum, year) => sum + year.fixedInterest + year.variableInterest,
            0,
        );

        const totalAmountPaid = yearlyBreakdown.value.reduce(
            (sum, year) => sum + year.totalPayment,
            0,
        );

        const averageMonthlyPayment =
            yearlyBreakdown.value.length > 0
                ? yearlyBreakdown.value.reduce(
                      (sum, year) => sum + year.monthlyPayment,
                      0,
                  ) / yearlyBreakdown.value.length
                : 0;

        const averageMonthlyHousingCost =
            averageMonthlyPayment + monthlyUtilities.value;

        return {
            totalLoanAmount: totalLoanAmount.value,
            fixedLoanAmount: fixedLoanAmount.value,
            variableLoanAmount: variableLoanAmount.value,
            totalInterestPaid,
            totalAmountPaid,
            averageMonthlyPayment,
            averageMonthlyHousingCost,
            monthlyUtilities: monthlyUtilities.value,
        };
    });

    function formatCurrency(value: number): string {
        return new Intl.NumberFormat('da-DK', {
            style: 'currency',
            currency: 'DKK',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(value);
    }

    function formatNumber(value: number): string {
        return new Intl.NumberFormat('da-DK', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(value);
    }

    function resetInputs() {
        Object.assign(inputs, defaultInputs);
    }

    return {
        inputs,
        totalLoanAmount,
        fixedLoanAmount,
        variableLoanAmount,
        bankLoanAmount,
        fixedLoanPlusBond,
        variableBaseRate,
        monthlyUtilities,
        yearlyBreakdown,
        summary,
        fixedBidragssats,
        variableBidragssats,
        fixedEffectiveRate,
        variableEffectiveRate,
        formatCurrency,
        formatNumber,
        resetInputs,
        isEditableYear,
        setVariableRateForYear,
        variableRateOverrides,
    };
}
