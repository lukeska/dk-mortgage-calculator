<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { useCurrencyInput } from 'vue-currency-input';
import { watch } from 'vue';

const props = defineProps<{
    modelValue?: number | null;
    class?: HTMLAttributes['class'];
    readonly?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', payload: number | null): void;
}>();

const { inputRef, numberValue, setValue } = useCurrencyInput(
    {
        currency: 'DKK',
        locale: 'da-DK',
        hideCurrencySymbolOnFocus: true,
        hideGroupingSeparatorOnFocus: true,
        precision: 0,
    },
    false,
);

// Emit changes to parent
watch(numberValue, (value) => {
    if (value !== props.modelValue) {
        emit('update:modelValue', value);
    }
});

// Sync incoming modelValue changes
watch(
    () => props.modelValue,
    (value) => {
        if (value !== numberValue.value) {
            setValue(value);
        }
    },
    { immediate: true },
);
</script>

<template>
    <input
        ref="inputRef"
        type="text"
        :readonly="readonly"
        data-slot="input"
        :class="
            cn(
                'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                props.class,
            )
        "
    />
</template>
