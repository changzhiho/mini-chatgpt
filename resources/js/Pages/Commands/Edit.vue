<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    custom_commands: String,
    success: String,
    from_conversation: String,
})

const form = useForm({
    custom_commands: props.custom_commands || '',
    from_conversation: props.from_conversation,
})

const submit = () => {
    form.post(route('commands.update'))
}
</script>

<template>
    <AppLayout title="Commandes personnalisées">
        <div class="max-w-2xl mx-auto py-10 px-4">
            <h1 class="text-2xl font-bold mb-6">Commandes personnalisées</h1>
            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label class="block font-medium mb-2">Vos commandes personnalisées</label>
                    <textarea
                        v-model="form.custom_commands"
                        rows="10"
                        class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Définissez vos commandes personnalisées ici, une par ligne.&#10;Exemple :&#10;/meteo : Affiche la météo actuelle pour votre localisation.&#10;/citation : Donne une citation inspirante."
                    ></textarea>
                    <div v-if="form.errors.custom_commands" class="text-red-600 text-sm mt-1">
                        {{ form.errors.custom_commands }}
                    </div>
                </div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                </button>
                <div v-if="success" class="text-green-600">
                    {{ success }}
                </div>
            </form>
        </div>
    </AppLayout>
</template>
