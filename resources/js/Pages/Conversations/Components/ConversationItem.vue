<script setup>
import { computed } from 'vue'
import ConversationActions from '@/Pages/Conversations/Components/ConversationActions.vue'

const props = defineProps({
    conversation: Object,
    isSelected: Boolean
})

const emit = defineEmits([
    'select',
    'share',
    'delete'
])

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
</script>

<template>
    <div
        :class="[
            'p-3 rounded-lg cursor-pointer mb-2 group relative',
            isSelected ? 'bg-gray-700' : 'hover:bg-gray-800'
        ]"
        @click="emit('select', conversation)"
    >
        <div class="text-sm font-medium truncate pr-16">
            {{ conversation.title }}
        </div>
        <div class="text-xs text-gray-400 mt-1">
            {{ formatDate(conversation.updated_at) }}
        </div>

        <ConversationActions
            :conversation="conversation"
            @share="emit('share')"
            @delete="emit('delete')"
        />
    </div>
</template>
