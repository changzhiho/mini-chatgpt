<script setup>
import { ref, nextTick, watch, onMounted, onBeforeUnmount } from 'vue'
import MessageBubble from '@/Pages/Chat/Components/MessageBubble.vue'

const props = defineProps({
    currentMessages: Array,
    selectedConversation: Object,
    isStreaming: Boolean,
    currentAIMessage: String,
    formatMarkdown: Function
})

const messagesContainer = ref(null)

const saveScrollPosition = () => {
    if (messagesContainer.value && !props.isStreaming) {
        localStorage.setItem(`chat-scroll-${props.selectedConversation?.id}`, messagesContainer.value.scrollTop.toString())
    }
}

const restoreScrollPosition = () => {
    nextTick(() => {
        if (messagesContainer.value && props.selectedConversation?.id) {
            const saved = localStorage.getItem(`chat-scroll-${props.selectedConversation.id}`)
            if (saved) {
                messagesContainer.value.scrollTop = parseInt(saved, 10)
            }
        }
    })
}


const scrollToBottomForStreaming = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
        }
    })
}

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
        saveScrollPosition()
    }
})

watch(() => props.selectedConversation?.id, () => {
    if (props.selectedConversation?.id) {
        restoreScrollPosition()
    }
}, { immediate: true })

//Watch simplifié pour le streaming
watch(() => props.isStreaming, (isStreaming) => {
    if (isStreaming) {

        scrollToBottomForStreaming()
    }
})

//Watch pour scroll pendant le streaming
watch(() => props.currentAIMessage, () => {
    if (props.isStreaming) {
        scrollToBottomForStreaming()
    }
})

//Watch pour la fin du streaming
watch(() => props.isStreaming, (newIsStreaming, oldIsStreaming) => {
    if (oldIsStreaming && !newIsStreaming) {
        nextTick(() => {
            scrollToBottomForStreaming()
            setTimeout(() => {
                saveScrollPosition()
            }, 100)
        })
    }
})

defineExpose({
    saveScrollPosition,
    restoreScrollPosition,
    scrollToBottomForStreaming
})
</script>

<template>
    <div ref="messagesContainer" class="flex-1 overflow-y-auto">
        <div class="max-w-3xl mx-auto p-4 space-y-4">
            <div v-if="!selectedConversation" class="text-center text-gray-500 mt-20">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <p class="text-lg">Commencez une nouvelle conversation</p>
            </div>

            <!-- Messages normaux -->
            <MessageBubble
                v-for="message in currentMessages"
                :key="message.id"
                :message="message"
                :formatMarkdown="formatMarkdown"
            />

            <!-- Message en streaming -->
            <div v-if="isStreaming" class="flex justify-start mb-4">
                <div class="flex items-start gap-3 max-w-[80%] mr-8 md:mr-16">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white text-sm font-medium">
                            AI
                        </div>
                    </div>
                    <div class="rounded-xl px-4 py-3 shadow-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-600">
                        <div class="prose prose-sm dark:prose-invert max-w-none prose-pre:bg-gray-800 prose-pre:text-gray-100" v-html="currentAIMessage"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

