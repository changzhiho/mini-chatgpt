<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    instructions_about: String,
    instructions_how: String,
    success: String
})

const form = useForm({
    instructions_about: props.instructions_about || '',
    instructions_how: props.instructions_how || ''
})
</script>

<template>
    <div class="max-w-2xl mx-auto py-10">
        <h1 class="text-2xl font-bold mb-6">Instructions personnalisées</h1>
        <form @submit.prevent="form.post(route('instructions.update'))" class="space-y-6">
            <div>
                <label class="block font-medium mb-2">À propos de vous</label>
                <textarea v-model="form.instructions_about" rows="5" class="w-full border rounded p-2" placeholder="Décrivez-vous, votre métier, vos objectifs..."></textarea>
            </div>
            <div>
                <label class="block font-medium mb-2">Comment dois-je répondre ?</label>
                <textarea v-model="form.instructions_how" rows="5" class="w-full border rounded p-2" placeholder="Exemple : Réponds toujours en français, tuto étape par étape, etc."></textarea>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Enregistrer</button>
            <div v-if="form.errors.instructions_about" class="text-red-600">{{ form.errors.instructions_about }}</div>
            <div v-if="form.errors.instructions_how" class="text-red-600">{{ form.errors.instructions_how }}</div>
            <div v-if="success" class="text-green-600">{{ success }}</div>
        </form>
    </div>
</template>
