<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import MarkdownIt from 'markdown-it'
import hljs from 'highlight.js'

const props = defineProps({
    models: Array,
    selectedModel: String,
    conversations: Array,
    selectedConversationId: [String, Number],
    flash: Object,
    errors: Object,
})

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
    initializeConversationSelection()
    document.addEventListener('click', closeSettingsMenu)
})

onUnmounted(() => {
    document.removeEventListener('click', closeSettingsMenu)
})

const selectedConversation = ref(null)
const currentModel = ref(props.selectedModel)
const messagesContainer = ref(null)
const messageInput = ref(null)
const isSidebarOpen = ref(false)
const showSettingsMenu = ref(false)
const isStreaming = ref(false)
const currentAIMessage = ref('')

const messageForm = useForm({
    message: '',
    conversation_id: null,
    model: props.selectedModel
})

const conversationForm = useForm({
    model: props.selectedModel
})

const currentMessages = computed(() => {
    return selectedConversation.value?.messages || []
})

const initializeConversationSelection = () => {
    if (props.flash?.selectedConversationId) {
        const conversation = props.conversations.find(c => c.id === props.flash.selectedConversationId)
        if (conversation) {
            selectConversation(conversation)
            if (props.flash?.shouldFocusInput) {
                focusMessageInput()
            }
            return
        }
    }

    if (props.selectedConversationId) {
        const conversation = props.conversations.find(c => c.id == props.selectedConversationId)
        if (conversation) {
            selectConversation(conversation)
            return
        }
    }

    if (props.conversations.length > 0) {
        selectConversation(props.conversations[0])
    }
}

const closeSettingsMenu = () => {
    showSettingsMenu.value = false
}

const formatMarkdown = (content) => {
    if (!md) return content
    return md.render(content)
}

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

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
        }
    })
}

const focusMessageInput = () => {
    nextTick(() => {
        if (messageInput.value) {
            messageInput.value.focus()
        }
    })
}

const selectConversation = (conversation) => {
    selectedConversation.value = conversation
    currentModel.value = conversation.model || props.selectedModel
    messageForm.conversation_id = conversation.id
    messageForm.model = conversation.model || props.selectedModel

    //Change l'URL pour inclure l'ID
    router.visit(`/ask?conversation=${conversation.id}`, {
    preserveState: true,
    preserveScroll: true,
    only: []
    })

    scrollToBottom()
    focusMessageInput()
    if (window.innerWidth < 768) {
        isSidebarOpen.value = false
    }
}

const createNewConversation = () => {
    conversationForm.model = currentModel.value
    conversationForm.post(route('ask.new'), {
        preserveScroll: true,
    })
}

const deleteConversation = (conversationId) => {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette conversation ?')) return
    router.delete(route('ask.delete', conversationId), {
        preserveScroll: true,
    })
}

const sendMessageWithStreaming = async () => {
    if (!messageForm.message.trim() || isStreaming.value) return

    const userMessage = {
        id: Date.now(),
        role: 'user',
        content: messageForm.message,
        created_at: new Date().toISOString()
    }

    if (selectedConversation.value) {
        selectedConversation.value.messages.push(userMessage)
        scrollToBottom()
    }

    isStreaming.value = true
    currentAIMessage.value = ''

    try {
        const response = await fetch(route('ask.post'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                message: messageForm.message,
                model: currentModel.value,
                conversation_id: selectedConversation.value?.id,
            }),
        })

        if (!response.ok) {
            throw new Error(`Erreur: ${response.status}`)
        }

        const reader = response.body.getReader()
        const decoder = new TextDecoder()

        while (true) {
            const { done, value } = await reader.read()
            if (done) break

            const chunk = decoder.decode(value, { stream: true })

            for (let char of chunk) {
                currentAIMessage.value += char
                scrollToBottom()
                await new Promise(resolve => setTimeout(resolve, 20))
            }
        }
    } catch (error) {
        console.error('Erreur:', error)
        alert(`Erreur: ${error.message}`)

        if (selectedConversation.value) {
            selectedConversation.value.messages.pop()
        }
    } finally {
        isStreaming.value = false
        messageForm.reset('message')
        focusMessageInput()

        router.reload({
            only: ['conversations'],
            preserveState: true,
            preserveScroll: true
        })
    }
}



const handleKeydown = (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault()
        sendMessageWithStreaming()
    }
}

const shareConversation = (conversation) => {
    router.post(`/ask/${conversation.id}/share`, {}, {
        preserveScroll: true,
        onSuccess: (page) => {
            const shareUrl = page.props.flash?.share_url
            if (shareUrl && page.props.flash?.share_success) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(shareUrl)
                        .then(() => alert('Lien copié !'))
                        .catch(() => prompt('Copiez ce lien:', shareUrl))
                } else {
                    prompt('Copiez ce lien:', shareUrl)
                }
            }
        }
    })
}

watch(currentModel, (newModel) => {
    messageForm.model = newModel
    conversationForm.model = newModel
})

watch(() => props.conversations, (newConversations) => {
    if (selectedConversation.value) {
        const updatedConversation = newConversations.find(c => c.id === selectedConversation.value.id)
        if (updatedConversation) {
            selectedConversation.value = updatedConversation
            nextTick(() => scrollToBottom())
        }
    }
}, { deep: true })

watch(() => props.conversations, (newConversations, oldConversations) => {
    if (newConversations.length !== oldConversations?.length) {
        initializeConversationSelection()
    }
}, { immediate: false })

watch(() => props.flash, (newFlash, oldFlash) => {
    if (newFlash?.selectedConversationId && newFlash.selectedConversationId !== oldFlash?.selectedConversationId) {
        const conversation = props.conversations.find(c => c.id === newFlash.selectedConversationId)
        if (conversation) {
            selectConversation(conversation)
        }
    }
    if (newFlash?.shouldFocusInput) {
        focusMessageInput()
    }
}, { deep: true })
</script>

<template>
    <AppLayout title="Ask AI">
        <div class="flex h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Overlay pour fermer le menu sur mobile -->
            <div
                v-if="isSidebarOpen"
                class="fixed inset-0 bg-black bg-opacity-50 z-10 md:hidden"
                @click="isSidebarOpen = false"
            ></div>

            <!-- Sidebar mobile-friendly -->
            <div
                :class="[
                    'w-64 bg-gray-900 text-white flex flex-col transition-all duration-300 fixed md:relative z-20 h-screen',
                    isSidebarOpen ? 'left-0' : '-left-64 md:left-0'
                ]"
            >
                <!-- Header sidebar -->
                <div class="p-4 border-b border-gray-700 flex justify-between items-center md:block">
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
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>                        </svg>
                        <span class="hidden sm:inline">Nouvelle conversation</span>
                        <span class="sm:hidden">Nouveau</span>
                    </button>
                    <button
                        class="md:hidden p-2 text-gray-300 hover:text-white ml-2"
                        @click="isSidebarOpen = false"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Model Selector pour mobile -->
                <div class="p-4 border-b border-gray-700 md:hidden">
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

                <div class="flex-1 overflow-y-auto p-2 min-h-0">
                    <!-- Lien Instructions mobile -->
                    <div class="md:hidden mb-4">
                        <a
                            :href="route('instructions.edit', { from_conversation: selectedConversation?.id })"
                            class="block p-3 rounded-lg text-gray-300 hover:bg-gray-800 transition-colors"
                            @click="isSidebarOpen = false"
                        >
                            <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Instructions
                        </a>
                        <a
                            :href="route('commands.edit', { from_conversation: selectedConversation?.id })"
                            class="block p-3 rounded-lg text-gray-300 hover:bg-gray-800 transition-colors"
                            @click="isSidebarOpen = false"
                        >
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Commandes
                        </a>
                    </div>

                    <!-- Conversations existantes -->
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
                        <div class="text-sm font-medium truncate pr-16">
                            {{ conversation.title }}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">
                            {{ formatDate(conversation.updated_at) }}
                        </div>

                        <!-- Container pour les boutons d'action -->
                        <div class="absolute right-2 top-2 opacity-0 group-hover:opacity-100 flex space-x-1">
                            <!-- Bouton de partage -->
                            <button
                                @click.stop="shareConversation(conversation)"
                                class="text-gray-400 hover:text-blue-400 transition-all p-1 rounded"
                                title="Partager cette conversation"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                                </svg>
                            </button>

                            <!-- Bouton de suppression -->
                            <button
                                @click.stop="deleteConversation(conversation.id)"
                                class="text-gray-400 hover:text-red-400 transition-all p-1 rounded"
                                title="Supprimer cette conversation"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Model Selector - EN BAS pour desktop -->
                <div class="p-4 border-t border-gray-700 hidden md:block">
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
                <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <button
                            class="md:hidden p-2 text-gray-700 dark:text-gray-300"
                            @click="isSidebarOpen = !isSidebarOpen"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white ml-2">
                            {{ selectedConversation?.title || 'Sélectionnez une conversation' }}
                        </h1>
                    </div>

                    <!-- Bouton Settings (desktop uniquement) -->
                    <div class="hidden md:block relative">
                        <button
                            @click.stop="showSettingsMenu = !showSettingsMenu"
                            class="p-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                            title="Paramètres"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </button>

                        <!-- Menu déroulant Settings -->
                        <div
                            v-if="showSettingsMenu"
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg z-50 border border-gray-200 dark:border-gray-700"
                        >
                            <a
                                :href="route('instructions.edit', { from_conversation: selectedConversation?.id })"
                                class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                @click="showSettingsMenu = false"
                            >
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Instructions
                            </a>
                            <a
                                :href="route('commands.edit', { from_conversation: selectedConversation?.id })"
                                class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                @click="showSettingsMenu = false"
                            >
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Commandes
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
                    <div v-if="!selectedConversation" class="text-center text-gray-500 mt-20">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <p class="text-lg">Commencez une nouvelle conversation</p>
                    </div>
                    <div v-for="message in currentMessages" :key="message.id" :class="[
                        'flex',
                        message.role === 'user' ? 'justify-end' : 'justify-start'
                    ]">
                        <div :class="[
                            'flex p-4',
                            message.role === 'user' ? 'bg-gray-50 dark:bg-gray-800 flex-row-reverse' : 'bg-white dark:bg-gray-900 flex-row',
                            message.role === 'user' ? 'max-w-2xl' : 'max-w-3xl'
                        ]">
                            <div :class="[
                                'flex-shrink-0',
                                message.role === 'user' ? 'ml-3' : 'mr-3'
                            ]">
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
                    <!-- Message en cours de streaming -->
                    <div v-if="isStreaming" class="flex">
                        <div class="max-w-3xl mx-auto w-full bg-white dark:bg-gray-900">
                            <div class="flex p-4">
                                <div class="flex-shrink-0 mr-4">
                                    <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white text-sm font-medium">
                                        AI
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="prose dark:prose-invert max-w-none prose-pre:bg-gray-800 prose-pre:text-gray-100" v-html="formatMarkdown(currentAIMessage)"></div>
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
                    <form @submit.prevent="sendMessageWithStreaming" class="max-w-3xl mx-auto">
                        <div class="flex space-x-4">
                            <div class="flex-1">
                                <textarea
                                    ref="messageInput"
                                    v-model="messageForm.message"
                                    :disabled="isStreaming"
                                    @keydown="handleKeydown"
                                    placeholder="Tapez votre message... (Entrée pour envoyer, Shift+Entrée pour nouvelle ligne)"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white resize-none"
                                    rows="1"
                                ></textarea>
                            </div>
                            <button
                                type="submit"
                                :disabled="!messageForm.message.trim() || isStreaming"
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <svg v-if="!isStreaming" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


<style>
@import 'highlight.js/styles/github-dark.css';

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
