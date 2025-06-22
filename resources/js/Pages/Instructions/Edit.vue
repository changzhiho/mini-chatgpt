<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    instructions_about: String,
    instructions_how: String,
    success: String,
    flash: Object,
    from_conversation: String,
})

const form = useForm({
    instructions_about: props.instructions_about || '',
    instructions_how: props.instructions_how || '',
    from_conversation: props.from_conversation,
})

const submit = () => {
    form.post(route('instructions.update'))
}
</script>

<template>
    <AppLayout title="Instructions personnalisées">
        <div class="max-w-2xl mx-auto py-10 px-4">
            <h1 class="text-2xl font-bold mb-6">Instructions personnalisées</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label class="block font-medium mb-2">À propos de vous</label>
                    <textarea
                        v-model="form.instructions_about"
                        rows="5"
                        class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Décrivez-vous, votre métier, vos objectifs..."
                    ></textarea>
                    <div v-if="form.errors.instructions_about" class="text-red-600 text-sm mt-1">
                        {{ form.errors.instructions_about }}
                    </div>
                </div>

                <div>
                    <label class="block font-medium mb-2">Comment dois-je répondre ?</label>
                    <textarea
                        v-model="form.instructions_how"
                        rows="5"
                        class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Exemple : Réponds toujours en français, tuto étape par étape, etc."
                    ></textarea>
                    <div v-if="form.errors.instructions_how" class="text-red-600 text-sm mt-1">
                        {{ form.errors.instructions_how }}
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                </button>

                <div v-if="flash?.success" class="text-green-600">
                    {{ flash.success }}
                </div>
            </form>
        </div>
    </AppLayout>
</template>
