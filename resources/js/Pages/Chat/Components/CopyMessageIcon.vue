<script setup>
import { ref } from 'vue'

const props = defineProps({
  message: String,
  alignRight: { type: Boolean, default: false },
  alwaysVisible: { type: Boolean, default: false }
})

const isHovered = ref(false)

const fallbackCopyTextToClipboard = (text) => {
  const textArea = document.createElement('textarea')
  textArea.value = text
  textArea.style.position = 'fixed'
  textArea.style.left = '-999999px'
  textArea.style.top = '-999999px'
  document.body.appendChild(textArea)
  textArea.focus()
  textArea.select()
  try {
    document.execCommand('copy')
  } catch (err) {}
  document.body.removeChild(textArea)
}

const copyToClipboard = async () => {
  try {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(props.message)
    } else {
      fallbackCopyTextToClipboard(props.message)
    }
  } catch (e) {
    fallbackCopyTextToClipboard(props.message)
  }
}
</script>

<template>
  <div
    class="group"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
    @touchstart="isHovered = true"
    @touchend="setTimeout(() => isHovered = false, 1000)"
  >
    <slot />
    <!-- Conteneur de hauteur fixe pour éviter les décalages -->
    <div :class="[
      'flex items-center',
      alignRight ? 'justify-end' : 'justify-start'
    ]" style="min-height: 28px; margin-top: 0.25rem;">
      <button
        v-if="alwaysVisible || isHovered"
        @click.stop.prevent="copyToClipboard"
        class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors opacity-70 hover:opacity-100"
        aria-label="Copier le message"
        style="margin-left: 0.25rem; margin-right: 0.25rem;"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.375A2.25 2.25 0 014.125 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
        </svg>
      </button>
    </div>
  </div>
</template>
