<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import MarkdownIt from 'markdown-it'
import hljs from 'highlight.js'

// Props reçues du contrôleur
const props = defineProps({
    models: Array,
    selectedModel: String,
    conversations: Array,
    selectedConversationId: Number,
    shouldFocusInput: Boolean,
    flash: Object,
    errors: Object,
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

    // Sélectionner la conversation appropriée
    if (props.selectedConversationId) {
        const conversation = props.conversations.find(c => c.id === props.selectedConversationId)
        if (conversation) {
            selectConversation(conversation)
        }
    } else if (props.conversations.length > 0) {
        selectConversation(props.conversations[0])
    }

    // Focus automatique si demandé
    if (props.shouldFocusInput) {
        focusMessageInput()
    }
})

// État réactif
const selectedConversation = ref(null)
const currentModel = ref(props.selectedModel)
const messagesContainer = ref(null)
const messageInput = ref(null)

// Formulaire Inertia pour envoyer des messages
const messageForm = useForm({
    message: '',
    conversation_id: null,
    model: props.selectedModel
})

// Formulaire pour créer une conversation
const conversationForm = useForm({
    model: props.selectedModel
})

// Messages de la conversation actuelle
const currentMessages = computed(() => {
    return selectedConversation.value?.messages || []
})

// Rendu du markdown
const formatMarkdown = (content) => {
    if (!md) return content
    return md.render(content)
}

// Formatage des dates
const formatDate = (dateString) => {
    const date = new Date(dateString)
    const now = new Date()
    const diffTime = Math.abs(now - date)
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

    if (diffDays === 1) return 'Aujourd\'hui'
    if (diffDays === 2) return 'Hier'
    if (diffDays <= 7) return `Il y a ${diffDays - 1} jours`

    return date.toLocaleDateString('fr-FR')
}

// Scroll vers le bas
const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
        }
    })
}

// Focus automatique sur l'input
const focusMessageInput = () => {
    nextTick(() => {
        if (messageInput.value) {
            messageInput.value.focus()
        }
    })
}

// Sélectionner une conversation
const selectConversation = (conversation) => {
    selectedConversation.value = conversation
    currentModel.value = conversation.model || props.selectedModel
    messageForm.conversation_id = conversation.id
    messageForm.model = conversation.model || props.selectedModel
    scrollToBottom()
    focusMessageInput()
}

// Créer une nouvelle conversation
const createNewConversation = () => {
    conversationForm.model = currentModel.value
    conversationForm.post(route('ask.conversations.create'), {
        preserveScroll: true, // Préserver la position de scroll
        onSuccess: (page) => {
            // La nouvelle conversation sera automatiquement sélectionnée
            // grâce au selectedConversationId retourné par le controller
            if (page.props.selectedConversationId) {
                const newConversation = page.props.conversations.find(c => c.id === page.props.selectedConversationId)
                if (newConversation) {
                    selectConversation(newConversation)
                }
            }
        }
    })
}

// Supprimer une conversation
const deleteConversation = (conversationId) => {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette conversation ?')) return

    router.delete(route('ask.conversations.delete', conversationId), {
        preserveScroll: true, // Préserver la position de scroll
        onSuccess: () => {
            if (selectedConversation.value?.id === conversationId) {
                selectedConversation.value = null
                if (props.conversations.length > 0) {
                    selectConversation(props.conversations[0])
                }
            }
        }
    })
}

// Envoyer un message
const sendMessage = () => {
    if (!messageForm.message.trim() || messageForm.processing) return

    messageForm.model = currentModel.value

    messageForm.post(route('ask.post'), {
        preserveScroll: true, // CRUCIAL : Préserver la position de scroll
        onSuccess: (page) => {
            // Mettre à jour la conversation sélectionnée avec les nouvelles données
            if (page.props.selectedConversationId) {
                const updatedConversation = page.props.conversations.find(c => c.id === page.props.selectedConversationId)
                if (updatedConversation) {
                    selectedConversation.value = updatedConversation
                    scrollToBottom()

                    // Focus automatique après réception de la réponse
                    if (page.props.shouldFocusInput) {
                        focusMessageInput()
                    }
                }
            }
            messageForm.reset('message')
        },
        onError: (errors) => {
            console.error('Erreurs:', errors)
            focusMessageInput() // Focus même en cas d'erreur
        }
    })
}

// Gérer les raccourcis clavier
const handleKeydown = (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault()
        sendMessage()
    }
}

// Watcher pour mettre à jour le modèle
watch(currentModel, (newModel) => {
    messageForm.model = newModel
    conversationForm.model = newModel
})

// Watcher pour détecter les changements de conversations
watch(() => props.conversations, (newConversations) => {
    // Si on a une conversation sélectionnée, la mettre à jour avec les nouvelles données
    if (selectedConversation.value) {
        const updatedConversation = newConversations.find(c => c.id === selectedConversation.value.id)
        if (updatedConversation) {
            selectedConversation.value = updatedConversation
            nextTick(() => {
                scrollToBottom()
                // Focus automatique après mise à jour
                if (props.shouldFocusInput) {
                    focusMessageInput()
                }
            })
        }
    }
}, { deep: true })

// Watcher pour la conversation sélectionnée depuis le controller
watch(() => props.selectedConversationId, (newId) => {
    if (newId) {
        const conversation = props.conversations.find(c => c.id === newId)
        if (conversation) {
            selectConversation(conversation)
        }
    }
})

// Watcher pour le focus automatique
watch(() => props.shouldFocusInput, (shouldFocus) => {
    if (shouldFocus) {
        focusMessageInput()
    }
})
</script>

<template>
    <AppLayout title="Ask AI">
        <div class="flex h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Sidebar avec scroll-region pour préserver la position -->
            <div class="w-64 bg-gray-900 text-white flex flex-col">
                <!-- Header -->
                <div class="p-4 border-b border-gray-700">
                    <button
                        @click="createNewConversation"
                        :disabled="conversationForm.processing"
                        class="w-full flex items-center justify-center px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors disabled:opacity-50"
                    >
                        <svg v-if="!conversationForm.processing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <svg v-else class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Nouvelle conversation
                    </button>
                </div>

                <!-- Conversations List avec scroll-region -->
                <div class="flex-1 overflow-y-auto p-2" scroll-region>
                    <div
                        v-for="conversation in conversations"
                        :key="conversation.id"
                        @click="selectConversation(conversation)"
                        :class="[
                            'p-3 rounded-lg cursor-pointer mb-2 group relative',
                            selectedConversation?.id === conversation.id
                                ? 'bg-gray-700'
                                : 'hover:bg-gray-800'
                        ]"
                    >
                        <div class="text-sm font-medium truncate">
                            {{ conversation.title }}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">
                            {{ formatDate(conversation.updated_at) }}
                        </div>
                        <button
                            @click.stop="deleteConversation(conversation.id)"
                            class="absolute right-2 top-2 opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-400 transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Model Selector -->
                <div class="p-4 border-t border-gray-700">
                    <label class="block text-sm font-medium mb-2">Modèle IA</label>
                    <select
                        v-model="currentModel"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option v-for="model in models" :key="model.id" :value="model.id">
                            {{ model.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Main Chat Area -->
            <div class="flex-1 flex flex-col">
                <!-- Chat Header -->
                <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4">
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ selectedConversation?.title || 'Sélectionnez une conversation' }}
                    </h1>
                </div>

                <!-- Messages avec scroll-region -->
                <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4" scroll-region>
                    <div v-if="!selectedConversation" class="text-center text-gray-500 mt-20">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <p class="text-lg">Commencez une nouvelle conversation</p>
                    </div>

                    <div v-for="message in currentMessages" :key="message.id" class="flex">
                        <div :class="[
                            'max-w-3xl mx-auto w-full',
                            message.role === 'user' ? 'bg-gray-50 dark:bg-gray-800' : 'bg-white dark:bg-gray-900'
                        ]">
                            <div class="flex p-4">
                                <div class="flex-shrink-0 mr-4">
                                    <div :class="[
                                        'w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium',
                                        message.role === 'user' ? 'bg-blue-500' : 'bg-green-500'
                                    ]">
                                        {{ message.role === 'user' ? 'U' : 'AI' }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div v-if="message.role === 'user'" class="prose dark:prose-invert max-w-none">
                                        {{ message.content }}
                                    </div>
                                    <div v-else class="prose dark:prose-invert max-w-none prose-pre:bg-gray-800 prose-pre:text-gray-100" v-html="formatMarkdown(message.content)"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loading indicator -->
                    <div v-if="messageForm.processing" class="flex">
                        <div class="max-w-3xl mx-auto w-full bg-white dark:bg-gray-900">
                            <div class="flex p-4">
                                <div class="flex-shrink-0 mr-4">
                                    <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white text-sm font-medium">
                                        AI
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2">
                                        <div class="animate-pulse flex space-x-1">
                                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                        </div>
                                        <span class="text-gray-500 text-sm">L'IA réfléchit...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Affichage des erreurs -->
                <div v-if="errors?.message" class="mx-4 mb-4 p-4 bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-800 dark:text-red-200">
                                {{ errors.message }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4">
                    <form @submit.prevent="sendMessage" class="max-w-3xl mx-auto">
                        <div class="flex space-x-4">
                            <div class="flex-1">
                                <textarea
                                    ref="messageInput"
                                    v-model="messageForm.message"
                                    :disabled="messageForm.processing"
                                    @keydown="handleKeydown"
                                    placeholder="Tapez votre message... (Entrée pour envoyer, Shift+Entrée pour nouvelle ligne)"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white resize-none"
                                    rows="1"
                                ></textarea>
                            </div>
                            <button
                                type="submit"
                                :disabled="!messageForm.message.trim() || messageForm.processing"
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <svg v-if="!messageForm.processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

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
