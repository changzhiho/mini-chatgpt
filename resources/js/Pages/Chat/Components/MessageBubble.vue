<script setup>
import CopyMessageIcon from '@/Pages/Chat/Components/CopyMessageIcon.vue'


const props = defineProps({
    message: Object
})
</script>

<template>
    <div :class="[
        'flex',
        message.role === 'user' ? 'justify-end' : 'justify-start'
    ]">
        <CopyMessageIcon
            :message="message.content"
            :align-right="message.role === 'user'"
            :always-visible="message.role !== 'user'"
        >
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
                    <div v-else class="prose dark:prose-invert max-w-none prose-pre:bg-gray-800 prose-pre:text-gray-100" v-html="message.content"></div>
                </div>
            </div>
        </CopyMessageIcon>
    </div>
</template>
