<script setup>
const props = defineProps({
    models: Array,
    currentModel: String
})

const emit = defineEmits(['update:model'])
</script>

<template>
    <div :class="$attrs.class">
        <label class="block text-sm font-medium mb-2">Modèle IA</label>
        <select
            v-model="props.currentModel"
            @change="emit('update:model', $event.target.value)"
            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
            <option v-for="model in models" :key="model.id" :value="model.id">
                {{ model.name }}
            </option>
        </select>
    </div>
</template>
