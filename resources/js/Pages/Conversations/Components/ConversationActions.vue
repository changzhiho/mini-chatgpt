<script setup>
const props = defineProps({
    conversation: Object
})

const emit = defineEmits([
    'share',
    'delete'
])
</script>

<template>
    <div class="absolute right-2 top-2 opacity-0 group-hover:opacity-100 flex space-x-1">
        <button
            @click.stop="emit('share')"
            class="text-gray-400 hover:text-blue-400 transition-all p-1 rounded"
            title="Partager cette conversation"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
            </svg>
        </button>
        <button
            @click.stop="emit('delete')"
            class="text-gray-400 hover:text-red-400 transition-all p-1 rounded"
            title="Supprimer cette conversation"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
        </button>
    </div>
</template>
