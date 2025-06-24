<script setup>
import CopyMessageIcon from '@/Pages/Chat/Components/CopyMessageIcon.vue'

const props = defineProps({
    message: Object,
    formatMarkdown: Function
})
</script>

<template>
    <div :class="[
        'flex mb-4',
        message.role === 'user' ? 'justify-end' : 'justify-start'
    ]">
        <CopyMessageIcon
            :message="message.content"
            :align-right="message.role === 'user'"
            :always-visible="message.role !== 'user'"
        >
            <div :class="[
                'flex items-start gap-3 max-w-[80%]',
                message.role === 'user' ? 'flex-row-reverse ml-8 md:ml-16' : 'flex-row mr-8 md:mr-16'
            ]">
                <!-- Avatar -->
                <div class="flex-shrink-0">
                    <div :class="[
                        'w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium',
                        message.role === 'user' ? 'bg-blue-500' : 'bg-green-500'
                    ]">
                        {{ message.role === 'user' ? 'U' : 'AI' }}
                    </div>
                </div>

                <!-- Message bubble -->
                <div :class="[
                    'rounded-xl px-4 py-3 shadow-sm',
                    message.role === 'user'
                        ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100'
                        : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-600'
                ]">
                    <div v-if="message.role === 'user'" class="text-sm leading-relaxed">
                        {{ message.content }}
                    </div>
                    <div v-else class="prose prose-sm dark:prose-invert max-w-none prose-pre:bg-gray-800 prose-pre:text-gray-100"
                         v-html="formatMarkdown ? formatMarkdown(message.content) : message.content">
                    </div>
                </div>
            </div>
        </CopyMessageIcon>
    </div>
</template>
