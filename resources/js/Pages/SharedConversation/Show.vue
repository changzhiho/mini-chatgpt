<script setup>
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import MarkdownIt from 'markdown-it'
import hljs from 'highlight.js'

const props = defineProps({
    conversation: Object,
    messages: Array,
    owner: Object,
})

const md = new MarkdownIt({
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

const formatMarkdown = (content) => {
    return md.render(content)
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>

<template>
    <AppLayout title="Conversation partagée">
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <div class="max-w-4xl mx-auto py-8 px-4">
                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ conversation.title }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Partagé par {{ owner.name }} • {{ formatDate(conversation.created_at) }}
                    </p>
                </div>

                <!-- Messages -->
                <div class="space-y-4">
                    <div v-for="message in messages" :key="message.id" class="flex">
                        <div :class="[
                            'max-w-3xl mx-auto w-full rounded-lg shadow-sm',
                            message.role === 'user' ? 'bg-gray-50 dark:bg-gray-800' : 'bg-white dark:bg-gray-900'
                        ]">
                            <div class="flex p-6">
                                <div class="flex-shrink-0 mr-4">
                                    <div :class="[
                                        'w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-medium',
                                        message.role === 'user' ? 'bg-blue-500' : 'bg-green-500'
                                    ]">
                                        {{ message.role === 'user' ? 'U' : 'AI' }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div v-if="message.role === 'user'" class="prose dark:prose-invert max-w-none">
                                        {{ message.content }}
                                    </div>
                                    <div v-else class="prose dark:prose-invert max-w-none prose-pre:bg-gray-800 prose-pre:text-gray-100" v-html="formatMarkdown(message.content)"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-8 text-center">
                    <a href="/ask" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Créer votre propre conversation
                    </a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@import 'highlight.js/styles/github-dark.css';

.prose pre {
    @apply bg-gray-800 text-gray-100 rounded-lg;
}

.prose code {
    @apply text-pink-500 bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-sm;
}

.prose pre code {
    @apply text-gray-100 bg-transparent p-0;
}

.hljs {
    @apply bg-gray-800 text-gray-100 p-4 rounded-lg overflow-x-auto;
}
</style>
