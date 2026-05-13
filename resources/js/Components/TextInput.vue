<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: {
        type: [String, Number],
        required: true,
    },
    label: String,
    type: {
        type: String,
        default: 'text',
    },
    placeholder: String,
    error: String,
    id: String,
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <div class="mb-3">
        <label v-if="label" :for="id" class="form-label font-weight-bold text-sm">{{ label }}</label>
        <input
            :id="id"
            :type="type"
            class="form-control form-control-sm"
            :class="{ 'is-invalid': error }"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            ref="input"
            :placeholder="placeholder"
        />
        <div v-if="error" class="text-danger text-xs mt-1">
            {{ error }}
        </div>
    </div>
</template>

<style scoped>
.text-sm {
    font-size: 0.875rem;
}
.text-xs {
    font-size: 0.75rem;
}
</style>
