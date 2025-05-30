<template>
    <AppLayout title="Ask AI">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Ask AI
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8">
                        <!-- Formulaire -->
                        <form @submit.prevent="submitQuestion" class="space-y-6">
                            <!-- Sélecteur de modèle -->
                            <div>
                                <label for="model"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Modèle IA
                                </label>
                                <select id="model" v-model="form.model"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                    required>
                                    <option value="">Choisir un modèle</option>
                                    <option v-for="model in models" :key="model.id" :value="model.id">
                                        {{ model.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Zone de texte pour la question -->
                            <div>
                                <label for="message"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Votre question
                                </label>
                                <textarea id="message" v-model="form.message" rows="4"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                    placeholder="Posez votre question ici..." required></textarea>
                            </div>

                            <!-- Bouton d'envoi -->
                            <div>
                                <button type="submit" :disabled="form.processing"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 disabled:opacity-50">
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    {{ form.processing ? 'Envoi en cours...' : 'Envoyer' }}
                                </button>
                            </div>
                        </form>

                        <!-- Affichage des erreurs -->
                        <div v-if="$page.props.flash.error"
                            class="mt-6 p-4 bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-800 dark:text-red-200">
                                        {{ $page.props.flash.error }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Affichage de la réponse -->
                        <div v-if="$page.props.flash.message" class="mt-8">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Réponse de l'IA
                            </h3>
                            <div
                                class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                                <div v-html="renderedMarkdown"
                                    class="prose dark:prose-invert prose-slate max-w-none prose-pre:bg-gray-800 prose-pre:text-gray-100">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import MarkdownIt from 'markdown-it'
import hljs from 'highlight.js'

// Props reçues du contrôleur
const props = defineProps({
    models: Array,
    selectedModel: String,
})

// Configuration de markdown-it avec highlight.js
let md = null

onMounted(() => {
    md = new MarkdownIt({
        html: true,
        linkify: true,
        typographer: true,
        highlight: function (str, lang) {
            if (lang && hljs.getLanguage(lang)) {
                try {
                    return '<pre class="hljs"><code>' +
                        hljs.highlight(str, { language: lang, ignoreIllegals: true }).value +
                        '</code></pre>'
                } catch (__) { }
            }

            return '<pre class="hljs"><code>' + md.utils.escapeHtml(str) + '</code></pre>'
        }
    })
})

// Formulaire Inertia
const form = useForm({
    message: '',
    model: props.selectedModel || '',
})

// Rendu du markdown
const renderedMarkdown = computed(() => {
    const message = props.$page?.props?.flash?.message || ''
    if (!message || !md) return ''
    return md.render(message)
})

// Soumission du formulaire
const submitQuestion = () => {
    form.post(route('ask.post'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('message')
        },
    })
}
</script>

<style>
/* Import des styles highlight.js */
@import 'highlight.js/styles/github-dark.css';

/* Styles personnalisés pour le code */
.prose pre {
    @apply bg-gray-800 text-gray-100 rounded-lg;
}

.prose code {
    @apply text-pink-500 bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-sm;
}

.prose pre code {
    @apply text-gray-100 bg-transparent p-0;
}

.hljs {
    @apply bg-gray-800 text-gray-100 p-4 rounded-lg overflow-x-auto;
}
</style>