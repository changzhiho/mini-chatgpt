<script setup>
const props = defineProps({
    currentAIMessage: String
})
</script>

<template>
    <div class="flex">
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
</template>
