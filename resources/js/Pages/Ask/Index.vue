<script setup>
import { ref, computed, onMounted, onUnmounted, onBeforeUnmount, nextTick, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Sidebar from './Components/Sidebar.vue'
import ChatHeader from './Components/ChatHeader.vue'
import MessagesList from './Components/MessagesList.vue'
import MessageInput from './Components/MessageInput.vue'
import ErrorDisplay from '@/Pages/UI/Components/ErrorDisplay.vue'
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

// Instance MarkdownIt pour le rendu des messages avec coloration syntaxique
let md = null

// Gestion de la persistance du scroll de page
const savePageScrollPosition = () => {
    localStorage.setItem('ask-page-scroll', window.scrollY.toString())
}

const restorePageScrollPosition = () => {
    nextTick(() => {
        const saved = localStorage.getItem('ask-page-scroll')
        if (saved) {
            window.scrollTo(0, parseInt(saved, 10))
        }
    })
}

onMounted(() => {
    // Configuration MarkdownIt avec highlight.js pour la coloration syntaxique
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
    restorePageScrollPosition()
    window.addEventListener('scroll', savePageScrollPosition)
})

onBeforeUnmount(() => {
    savePageScrollPosition()
    window.removeEventListener('scroll', savePageScrollPosition)
})

onUnmounted(() => {
    document.removeEventListener('click', closeSettingsMenu)
    window.removeEventListener('scroll', savePageScrollPosition)
})

// État réactif de l'application
const selectedConversation = ref(null)
const currentModel = ref(props.selectedModel)
const messagesList = ref(null)
const messageInput = ref(null)
const isSidebarOpen = ref(false)
const showSettingsMenu = ref(false)
const isStreaming = ref(false)
const currentAIMessage = ref('')

// Formulaires Inertia
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

// Logique de sélection de conversation au chargement
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

const focusMessageInput = () => {
    if (messageInput.value) {
        messageInput.value.focusMessageInput()
    }
}

const selectConversation = (conversation) => {
    selectedConversation.value = conversation
    currentModel.value = conversation.model || props.selectedModel
    messageForm.conversation_id = conversation.id
    messageForm.model = conversation.model || props.selectedModel

    // Navigation Inertia avec préservation d'état
    router.visit(`/ask?conversation=${conversation.id}`, {
        preserveState: true,
        preserveScroll: true,
        only: []
    })

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

// Fonction principale de streaming avec gestion des titres automatiques
const sendMessageWithStreaming = async () => {
  if (!messageForm.message.trim() || isStreaming.value) return

  // Ajouter le message utilisateur immédiatement à l'interface
  if (selectedConversation.value) {
    selectedConversation.value.messages.push({
      id: Date.now(),
      role: 'user',
      content: messageForm.message,
      created_at: new Date().toISOString()
    })
  }

  isStreaming.value = true
  currentAIMessage.value = ''

  try {
    // Requête fetch avec token CSRF
    const response = await fetch(route('ask.post'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        message: messageForm.message,
        model: currentModel.value,
        conversation_id: selectedConversation.value?.id
      })
    })

    if (!response.ok) throw new Error(`Erreur: ${response.status}`)

    // Streaming avec ReadableStream API
    const reader = response.body.getReader()
    const decoder = new TextDecoder()
    let buffer = ''
    let messageComplete = false

    while (true) {
      const { done, value } = await reader.read()
      if (done) break

      buffer += decoder.decode(value, { stream: true })

      // Détection de la fin du message IA
      if (buffer.includes('__MESSAGE_END__')) {
        messageComplete = true
        buffer = buffer.replace('__MESSAGE_END__', '')
      }

      // Traitement du streaming de titre après fin du message
      if (messageComplete && buffer.includes('__TITLE_START__') && buffer.includes('__TITLE_END__')) {
        const titleJson = buffer.match(/__TITLE_START__\n(.*?)\n__TITLE_END__/s)
        if (titleJson) {
          const titleData = JSON.parse(titleJson[1])
          // Mise à jour réactive du titre dans la conversation et la sidebar
          if (selectedConversation.value?.id === titleData.conversation_id) {
            selectedConversation.value.title = titleData.title
          }
          const idx = props.conversations.findIndex(c => c.id === titleData.conversation_id)
          if (idx !== -1) props.conversations[idx].title = titleData.title
        }
        buffer = ''
      }

      // Streaming du contenu du message
      if (!messageComplete) {
        currentAIMessage.value += buffer
        buffer = ''
        await new Promise(r => setTimeout(r, 20))
      }
    }

    // Ajouter le message final à la conversation
    if (selectedConversation.value) {
      selectedConversation.value.messages.push({
        id: Date.now() + 1,
        role: 'assistant',
        content: currentAIMessage.value,
        created_at: new Date().toISOString()
      })
    }
  } catch (error) {
    console.error('Erreur:', error)
    alert(`Erreur: ${error.message}`)
    if (selectedConversation.value) selectedConversation.value.messages.pop()
  } finally {
    isStreaming.value = false
    messageForm.reset('message')
    focusMessageInput()
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

// Watchers pour la synchronisation d'état
watch(currentModel, (newModel) => {
    messageForm.model = newModel
    conversationForm.model = newModel
})

// Synchronisation des conversations mises à jour depuis le serveur
watch(() => props.conversations, (newConversations) => {
    if (selectedConversation.value) {
        const updatedConversation = newConversations.find(c => c.id === selectedConversation.value.id)
        if (updatedConversation) {
            selectedConversation.value = updatedConversation
        }
    }
}, { deep: true })

// Réinitialisation lors de changements de conversations
watch(() => props.conversations, (newConversations, oldConversations) => {
    if (newConversations.length !== oldConversations?.length) {
        initializeConversationSelection()
    }
}, { immediate: false })

// Gestion des redirections et focus depuis le serveur
watch(() => props.flash, (newFlash, oldFlash) => {
    if (newFlash?.selectedConversationId && newFlash.selectedConversationId !== oldFlash?.selectedConversationId) {
        const conversation = props.conversations.find(c => c.id === newFlash.selectedConversationId)
        if (conversation) {
            selectConversation(conversation)
        }
    }
    if (newFlash?.shouldFocusInput) {
        focusMessageInput()
        if (messagesList.value) {
            messagesList.value.restoreScrollPosition()
        }
    }
}, { deep: true })
</script>

<template>
    <AppLayout title="Ask AI">
        <div class="flex h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Overlay mobile pour fermer la sidebar -->
            <div
                v-if="isSidebarOpen"
                class="fixed inset-0 bg-black bg-opacity-50 z-10 md:hidden"
                @click="isSidebarOpen = false"
            ></div>

            <Sidebar
                :conversations="conversations"
                :selectedConversation="selectedConversation"
                :currentModel="currentModel"
                :models="models"
                :isSidebarOpen="isSidebarOpen"
                :conversationForm="conversationForm"
                @selectConversation="selectConversation"
                @createNewConversation="createNewConversation"
                @closeSidebar="isSidebarOpen = false"
                @updateModel="currentModel = $event"
                @shareConversation="shareConversation"
                @deleteConversation="deleteConversation"
            />

            <!-- Zone de chat principale -->
            <div class="flex-1 flex flex-col">
                <ChatHeader
                    :selectedConversation="selectedConversation"
                    :isSidebarOpen="isSidebarOpen"
                    :showSettingsMenu="showSettingsMenu"
                    @toggleSidebar="isSidebarOpen = !isSidebarOpen"
                    @toggleSettingsMenu="showSettingsMenu = !showSettingsMenu"
                />

                <MessagesList
                    ref="messagesList"
                    :currentMessages="currentMessages"
                    :selectedConversation="selectedConversation"
                    :isStreaming="isStreaming"
                    :currentAIMessage="formatMarkdown(currentAIMessage)"
                    :formatMarkdown="formatMarkdown"
                />

                <ErrorDisplay :error="errors?.message" />

                <MessageInput
                    ref="messageInput"
                    :messageForm="messageForm"
                    :isStreaming="isStreaming"
                    :selectedConversation="selectedConversation"
                    @sendMessage="sendMessageWithStreaming"
                    @keydown="handleKeydown"
                />
            </div>
        </div>
    </AppLayout>
</template>
