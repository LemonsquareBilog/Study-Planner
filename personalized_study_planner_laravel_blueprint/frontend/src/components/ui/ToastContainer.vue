<script setup lang="ts">
import { useToastStore } from '@/stores/toast'
import { 
  CheckCircleIcon, XCircleIcon, ExclamationTriangleIcon, InformationCircleIcon, XMarkIcon 
} from '@heroicons/vue/24/outline'

const toastStore = useToastStore()

const toastIcons = {
  success: CheckCircleIcon,
  error: XCircleIcon,
  warning: ExclamationTriangleIcon,
  info: InformationCircleIcon,
}

const toastColors = {
  success: 'bg-green-500',
  error: 'bg-red-500',
  warning: 'bg-yellow-500',
  info: 'bg-blue-500',
}

const toastBgColors = {
  success: 'bg-green-50 dark:bg-green-900/30 border-green-200/50 dark:border-green-800/50',
  error: 'bg-red-50 dark:bg-red-900/30 border-red-200/50 dark:border-red-800/50',
  warning: 'bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200/50 dark:border-yellow-800/50',
  info: 'bg-blue-50 dark:bg-blue-900/30 border-blue-200/50 dark:border-blue-800/50',
}

function removeToast(id: string) {
  toastStore.remove(id)
}

function getProgressStyle(duration: number) {
  return {
    transition: `width ${duration}ms linear`,
    width: '100%',
  }
}
</script>

<template>
  <div 
    class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"
    aria-live="polite"
    aria-label="Notifications"
  >
    <TransitionGroup name="toast" tag="div" class="flex flex-col gap-2">
      <div 
        v-for="toast in toastStore.visibleToasts" 
        :key="toast.id"
        :class="[
          'toast',
          'pointer-events-auto flex items-start gap-3 min-w-[300px] max-w-md animate-slide-in',
          toastBgColors[toast.type],
          'border-l-4',
          toastColors[toast.type],
          'rounded-xl p-4 shadow-lg',
        ]"
        role="alert"
        :aria-live="toast.type === 'error' ? 'assertive' : 'polite'"
      >
        <div class="flex-shrink-0 mt-0.5">
          <component :is="toastIcons[toast.type]" :class="['w-5 h-5', toastColors[toast.type]]" aria-hidden="true" />
        </div>
        
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ toast.title }}</p>
          <p v-if="toast.message" class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ toast.message }}</p>
          
          <button 
            v-if="toast.action"
            @click="toast.action.onClick()"
            class="mt-2 text-xs font-medium text-pink-600 dark:text-pink-400 hover:underline"
          >
            {{ toast.action.label }}
          </button>
        </div>
        
        <button 
          @click="removeToast(toast.id)"
          class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-1"
          :aria-label="`Dismiss ${toast.title}`"
        >
          <XMarkIcon class="w-4 h-4" aria-hidden="true" />
        </button>
        
        <!-- Progress bar for auto-dismiss -->
        <div 
          v-if="!toast.persistent && toast.duration && toast.duration > 0"
          class="absolute bottom-0 left-0 h-1 rounded-b-xl"
          :class="toastColors[toast.type]"
          :style="getProgressStyle(toast.duration)"
        />
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { useToastStore } from '@/stores/toast'
import { 
  CheckCircleIcon, XCircleIcon, ExclamationTriangleIcon, InformationCircleIcon, XMarkIcon 
} from '@heroicons/vue/24/outline'

const toastStore = useToastStore()

const toastIcons = {
  success: CheckCircleIcon,
  error: XCircleIcon,
  warning: ExclamationTriangleIcon,
  info: InformationCircleIcon,
}

const toastColors = {
  success: 'bg-green-500',
  error: 'bg-red-500',
  warning: 'bg-yellow-500',
  info: 'bg-blue-500',
}

const toastBgColors = {
  success: 'bg-green-50 dark:bg-green-900/30 border-green-200/50 dark:border-green-800/50',
  error: 'bg-red-50 dark:bg-red-900/30 border-red-200/50 dark:border-red-800/50',
  warning: 'bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200/50 dark:border-yellow-800/50',
  info: 'bg-blue-50 dark:bg-blue-900/30 border-blue-200/50 dark:border-blue-800/50',
}

function removeToast(id: string) {
  toastStore.remove(id)
}

function getProgressStyle(duration: number) {
  return {
    transition: `width ${duration}ms linear`,
    width: '100%',
  }
}
</script>