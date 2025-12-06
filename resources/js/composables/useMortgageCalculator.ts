import { computed, reactive } from 'vue';

export type LoanPeriod = 10 | 20 | 30;
export type FlexibleLoanType = 'F3' | 'F5';

export interface MortgageInputs {
    propertyValue: number;
    ejerudgift: number;
    heating: number;
    water: number;
    repairs: number;
    downpayment: number;
    loanPeriodFixed: LoanPeriod;
    loanPeriodVariable: LoanPeriod;
    fixedMortgagePercentage: number;
    flexibleLoanType: FlexibleLoanType;
    withRepayments: boolean;
    interestRateF3: number;
    interestRateF5: number;
    interestRateF30: number;
}

export interface YearlyBreakdown {
    year: number;
    fixedBalance: number;
    variableBalance: number;
    totalBalance: number;
    fixedPayment: number;
    variablePayment: number;
    totalPayment: number;
    fixedInterest: number;
    variablePrincipal: number;
    fixedPrincipal: number;
    variableInterest: number;
    monthlyPayment: number;
    monthlyHousingCost: number;
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
    propertyValue: 0,
    ejerudgift: 0,
    heating: 0,
    water: 0,
    repairs: 0,
    downpayment: 0,
    loanPeriodFixed: 30,
    loanPeriodVariable: 30,
    fixedMortgagePercentage: 80,
    flexibleLoanType: 'F5',
    withRepayments: true,
    interestRateF3: 3.59,
    interestRateF5: 3.49,
    interestRateF30: 5,
};

function calculateAnnuityPayment(
    principal: number,
    annualRate: number,
    years: number,
): number {
    if (principal <= 0 || years <= 0) {
        return 0;
    }

    const monthlyRate = annualRate / 100 / 12;
    const numberOfPayments = years * 12;

    if (monthlyRate === 0) {
        return principal / numberOfPayments;
    }

    const annuityFactor =
        (monthlyRate * Math.pow(1 + monthlyRate, numberOfPayments)) /
        (Math.pow(1 + monthlyRate, numberOfPayments) - 1);

    return principal * annuityFactor;
}

function calculateInterestOnlyPayment(
    principal: number,
    annualRate: number,
): number {
    if (principal <= 0) {
        return 0;
    }

    return (principal * (annualRate / 100)) / 12;
}

export function useMortgageCalculator() {
    const inputs = reactive<MortgageInputs>({ ...defaultInputs });

    const totalLoanAmount = computed(() => {
        const loan = inputs.propertyValue - inputs.downpayment;
        return Math.max(0, loan);
    });

    const fixedLoanAmount = computed(() => {
        return (totalLoanAmount.value * inputs.fixedMortgagePercentage) / 100;
    });

    const variableLoanAmount = computed(() => {
        return totalLoanAmount.value - fixedLoanAmount.value;
    });

    const variableInterestRate = computed(() => {
        return inputs.flexibleLoanType === 'F3'
            ? inputs.interestRateF3
            : inputs.interestRateF5;
    });

    const monthlyUtilities = computed(() => {
        return (
            inputs.ejerudgift + inputs.heating + inputs.water + inputs.repairs
        );
    });

    const yearlyBreakdown = computed<YearlyBreakdown[]>(() => {
        const breakdown: YearlyBreakdown[] = [];

        if (totalLoanAmount.value <= 0) {
            return breakdown;
        }

        let fixedBalance = fixedLoanAmount.value;
        let variableBalance = variableLoanAmount.value;

        const maxYears = Math.max(
            inputs.loanPeriodFixed,
            inputs.loanPeriodVariable,
        );

        const fixedMonthlyPayment = inputs.withRepayments
            ? calculateAnnuityPayment(
                  fixedLoanAmount.value,
                  inputs.interestRateF30,
                  inputs.loanPeriodFixed,
              )
            : calculateInterestOnlyPayment(
                  fixedLoanAmount.value,
                  inputs.interestRateF30,
              );

        const variableMonthlyPayment = inputs.withRepayments
            ? calculateAnnuityPayment(
                  variableLoanAmount.value,
                  variableInterestRate.value,
                  inputs.loanPeriodVariable,
              )
            : calculateInterestOnlyPayment(
                  variableLoanAmount.value,
                  variableInterestRate.value,
              );

        for (let year = 1; year <= maxYears; year++) {
            let yearlyFixedPayment = 0;
            let yearlyVariablePayment = 0;
            let yearlyFixedInterest = 0;
            let yearlyVariableInterest = 0;
            let yearlyFixedPrincipal = 0;
            let yearlyVariablePrincipal = 0;

            for (let month = 1; month <= 12; month++) {
                if (fixedBalance > 0 && year <= inputs.loanPeriodFixed) {
                    const monthlyFixedInterest =
                        (fixedBalance * (inputs.interestRateF30 / 100)) / 12;
                    const monthlyFixedPrincipal = inputs.withRepayments
                        ? Math.min(
                              fixedMonthlyPayment - monthlyFixedInterest,
                              fixedBalance,
                          )
                        : 0;

                    yearlyFixedInterest += monthlyFixedInterest;
                    yearlyFixedPrincipal += monthlyFixedPrincipal;
                    yearlyFixedPayment +=
                        monthlyFixedInterest + monthlyFixedPrincipal;
                    fixedBalance = Math.max(
                        0,
                        fixedBalance - monthlyFixedPrincipal,
                    );
                }

                if (variableBalance > 0 && year <= inputs.loanPeriodVariable) {
                    const monthlyVariableInterest =
                        (variableBalance * (variableInterestRate.value / 100)) /
                        12;
                    const monthlyVariablePrincipal = inputs.withRepayments
                        ? Math.min(
                              variableMonthlyPayment - monthlyVariableInterest,
                              variableBalance,
                          )
                        : 0;

                    yearlyVariableInterest += monthlyVariableInterest;
                    yearlyVariablePrincipal += monthlyVariablePrincipal;
                    yearlyVariablePayment +=
                        monthlyVariableInterest + monthlyVariablePrincipal;
                    variableBalance = Math.max(
                        0,
                        variableBalance - monthlyVariablePrincipal,
                    );
                }
            }

            const totalYearlyPayment =
                yearlyFixedPayment + yearlyVariablePayment;
            const monthlyPayment = totalYearlyPayment / 12;

            breakdown.push({
                year,
                fixedBalance,
                variableBalance,
                totalBalance: fixedBalance + variableBalance,
                fixedPayment: yearlyFixedPayment,
                variablePayment: yearlyVariablePayment,
                totalPayment: totalYearlyPayment,
                fixedInterest: yearlyFixedInterest,
                variableInterest: yearlyVariableInterest,
                fixedPrincipal: yearlyFixedPrincipal,
                variablePrincipal: yearlyVariablePrincipal,
                monthlyPayment,
                monthlyHousingCost: monthlyPayment + monthlyUtilities.value,
            });

            if (fixedBalance <= 0 && variableBalance <= 0) {
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
        variableInterestRate,
        monthlyUtilities,
        yearlyBreakdown,
        summary,
        formatCurrency,
        formatNumber,
        resetInputs,
    };
}
