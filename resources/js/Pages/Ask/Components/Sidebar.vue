<script setup>
import ConversationItem from '@/Pages/Conversations/Components/ConversationItem.vue'
import ModelSelector from '@/Pages/UI/Components/ModelSelector.vue'

const props = defineProps({
    conversations: Array,
    selectedConversation: Object,
    currentModel: String,
    models: Array,
    isSidebarOpen: Boolean,
    conversationForm: Object
})

const emit = defineEmits([
    'selectConversation',
    'createNewConversation',
    'closeSidebar',
    'updateModel'
])
</script>

<template>
    <div :class="[
        'w-64 bg-gray-900 text-white flex flex-col transition-all duration-300 fixed md:relative z-20 h-screen',
        isSidebarOpen ? 'left-0' : '-left-64 md:left-0'
    ]">
        <!-- Header sidebar -->
        <div class="p-4 border-b border-gray-700 flex justify-between items-center md:block">
            <button
                @click="emit('createNewConversation')"
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
                <span class="hidden sm:inline">Nouvelle conversation</span>
                <span class="sm:hidden">Nouveau</span>
            </button>
            <button
                class="md:hidden p-2 text-gray-300 hover:text-white ml-2"
                @click="emit('closeSidebar')"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Model Selector mobile -->
        <ModelSelector
            :models="models"
            :currentModel="currentModel"
            @update:model="emit('updateModel', $event)"
            class="p-4 border-b border-gray-700 md:hidden"
        />

        <div class="flex-1 overflow-y-auto p-2 min-h-0">
            <!-- Liens mobile (Instructions/Commandes) -->
            <div class="md:hidden mb-4">
                <a
                    :href="route('instructions.edit', { from_conversation: selectedConversation?.id })"
                    class="block p-3 rounded-lg text-gray-300 hover:bg-gray-800 transition-colors"
                    @click="emit('closeSidebar')"
                >
                    <svg class="w-5 h-5 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Instructions
                </a>
                <a
                    :href="route('commands.edit', { from_conversation: selectedConversation?.id })"
                    class="block p-3 rounded-lg text-gray-300 hover:bg-gray-800 transition-colors"
                    @click="emit('closeSidebar')"
                >
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Commandes
                </a>
            </div>

            <!-- Conversations -->
            <ConversationItem
                v-for="conversation in conversations"
                :key="conversation.id"
                :conversation="conversation"
                :isSelected="selectedConversation?.id === conversation.id"
                @select="emit('selectConversation', conversation)"
                @share="$emit('shareConversation', conversation)"
                @delete="$emit('deleteConversation', conversation.id)"
            />
        </div>

        <!-- Model Selector desktop -->
        <ModelSelector
            :models="models"
            :currentModel="currentModel"
            @update:model="emit('updateModel', $event)"
            class="p-4 border-t border-gray-700 hidden md:block"
        />
    </div>
</template>
