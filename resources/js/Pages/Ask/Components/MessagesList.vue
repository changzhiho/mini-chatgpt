<script setup>
import { ref, nextTick, watch, onMounted, onBeforeUnmount } from 'vue'
import MessageBubble from '@/Pages/Chat/Components/MessageBubble.vue'

const props = defineProps({
    currentMessages: Array,
    selectedConversation: Object,
    isStreaming: Boolean,
    currentAIMessage: String
})

const messagesContainer = ref(null)
const savedScrollPosition = ref(0)

// ✅ Sauvegarder la position de scroll
const saveScrollPosition = () => {
    if (messagesContainer.value) {
        savedScrollPosition.value = messagesContainer.value.scrollTop
        // Sauvegarde aussi dans localStorage pour persistance
        localStorage.setItem(`chat-scroll-${props.selectedConversation?.id}`, savedScrollPosition.value.toString())
    }
}

// ✅ Restaurer la position de scroll
const restoreScrollPosition = () => {
    nextTick(() => {
        if (messagesContainer.value && props.selectedConversation?.id) {
            // Récupère depuis localStorage
            const saved = localStorage.getItem(`chat-scroll-${props.selectedConversation.id}`)
            if (saved) {
                messagesContainer.value.scrollTop = parseInt(saved, 10)
            }
        }
    })
}

// ✅ Écouter les changements de scroll
const handleScroll = () => {
    saveScrollPosition()
}

onMounted(() => {
    if (messagesContainer.value) {
        messagesContainer.value.addEventListener('scroll', handleScroll)
    }
})

onBeforeUnmount(() => {
    if (messagesContainer.value) {
        messagesContainer.value.removeEventListener('scroll', handleScroll)
        saveScrollPosition() // Sauvegarde finale
    }
})

// ✅ Watch pour restaurer quand on change de conversation
watch(() => props.selectedConversation?.id, () => {
    if (props.selectedConversation?.id) {
        restoreScrollPosition()
    }
}, { immediate: true })

// ✅ Scroll seulement en bas pendant le streaming (optionnel)
watch(() => props.currentAIMessage, () => {
    if (props.isStreaming && messagesContainer.value) {
        // Scroll en bas seulement pendant le streaming
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
})

defineExpose({
    saveScrollPosition,
    restoreScrollPosition
})
</script>

<template>
    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4" scroll-region>
        <div v-if="!selectedConversation" class="text-center text-gray-500 mt-20">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
            <p class="text-lg">Commencez une nouvelle conversation</p>
        </div>

        <MessageBubble
            v-for="message in currentMessages"
            :key="message.id"
            :message="message"
        />

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
                        <div class="prose dark:prose-invert max-w-none prose-pre:bg-gray-800 prose-pre:text-gray-100" v-html="currentAIMessage"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
