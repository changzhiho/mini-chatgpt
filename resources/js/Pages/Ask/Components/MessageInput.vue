<script setup>
import { ref, nextTick } from 'vue'
const props = defineProps({
    messageForm: Object,
    isStreaming: Boolean,
    selectedConversation: Object
})

const emit = defineEmits([
    'sendMessage',
    'keydown'
])

const messageInput = ref(null)

//Fonction de focus
const focusMessageInput = () => {
    nextTick(() => {
        if (messageInput.value) {
            messageInput.value.focus()
        }
    })
}

//Expose la fonction pour l'utiliser depuis le parent
defineExpose({
    focusMessageInput
})
</script>

<template>
    <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4">
        <form @submit.prevent="emit('sendMessage')" class="max-w-3xl mx-auto">
            <div class="flex space-x-4">
                <div class="flex-1">
                    <textarea
                        ref="messageInput"
                        v-model="messageForm.message"
                        :disabled="isStreaming || !selectedConversation"
                        @keydown="emit('keydown', $event)"
                        placeholder="Tapez votre message... (Entrée pour envoyer, Shift+Entrée pour nouvelle ligne)"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white resize-none"
                        rows="1"
                    ></textarea>
                </div>
                <button
                    type="submit"
                    :disabled="!messageForm.message.trim() || isStreaming || !selectedConversation"
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    <svg v-if="!isStreaming" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
</template>
