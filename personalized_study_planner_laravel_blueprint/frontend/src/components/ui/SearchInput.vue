<script setup lang="ts>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/24/outline'

interface Props {
  modelValue: string
  placeholder?: string
  suggestions?: string[]
  showSuggestions?: boolean
  loading?: boolean
  ariaLabel?: string
  id?: string
}

interface Emits {
  'update:modelValue': [value: string]
  selectSuggestion: [suggestion: string]
  clear: []
  search: [query: string]
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Search...',
  suggestions: () => [],
  showSuggestions: true,
  loading: false,
  ariaLabel: 'Search',
  id: 'search-input'
})

const emit = defineEmits<Emits>()

const isFocused = ref(false)
const selectedIndex = ref(-1)
const inputRef = ref<HTMLInputElement | null>(null)
const dropdownRef = ref<HTMLDivElement | null>(null)

const showDropdown = computed(() => 
  props.showSuggestions && 
  props.suggestions.length > 0 && 
  (isFocused.value || selectedIndex.value >= 0)
)

function handleInput(event: Event) {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
  selectedIndex.value = -1
}

function handleFocus() {
  isFocused.value = true
  selectedIndex.value = -1
}

function handleBlur() {
  setTimeout(() => {
    isFocused.value = false
    selectedIndex.value = -1
  }, 200)
}

function handleKeydown(event: KeyboardEvent) {
  if (!showDropdown.value) return
  
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      selectedIndex.value = Math.min(selectedIndex.value + 1, props.suggestions.length - 1)
      break
    case 'ArrowUp':
      event.preventDefault()
      selectedIndex.value = Math.max(selectedIndex.value - 1, -1)
      break
    case 'Enter':
      event.preventDefault()
      if (selectedIndex.value >= 0) {
        selectSuggestion(props.suggestions[selectedIndex.value])
      } else {
        emit('search', props.modelValue)
      }
      break
    case 'Escape':
      isFocused.value = false
      selectedIndex.value = -1
      if (inputRef.value) inputRef.value.blur()
      break
  }
}

function selectSuggestion(suggestion: string) {
  emit('update:modelValue', suggestion)
  emit('selectSuggestion', suggestion)
  selectedIndex.value = -1
  isFocused.value = false
  if (inputRef.value) inputRef.value.blur()
}

function handleClear() {
  emit('update:modelValue', '')
  emit('clear')
  selectedIndex.value = -1
  if (inputRef.value) inputRef.value.focus()
}

function handleClickOutside(event: MouseEvent) {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    isFocused.value = false
    selectedIndex.value = -1
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

watch(() => props.modelValue, () => {
  selectedIndex.value = -1
})
</script>

<template>
  <div class="relative" ref="dropdownRef">
    <label :for="id" class="sr-only">{{ ariaLabel }}</label>
    <div class="relative">
      <MagnifyingGlassIcon 
        v-if="!loading" 
        class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted dark:text-gray-500" 
        aria-hidden="true" 
      />
      <svg 
        v-else 
        class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted dark:text-gray-500 animate-spin" 
        aria-hidden="true" 
        viewBox="0 0 24 24" 
        fill="none" 
        xmlns="http://www.w3.org/2000/svg"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
      <input
        :id="id"
        :type="search"
        :value="modelValue"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown="handleKeydown"
        :placeholder="placeholder"
        :aria-label="ariaLabel"
        :aria-autocomplete="showSuggestions ? 'list' : 'none'"
        :aria-controls="showDropdown ? `${id}-suggestions` : undefined"
        :aria-expanded="showDropdown"
        :aria-activedescendant="selectedIndex >= 0 ? `${id}-suggestion-${selectedIndex}` : undefined"
        ref="inputRef"
        class="input-field pl-10 pr-10 w-full"
      />
      <button
        v-if="modelValue"
        @click="handleClear"
        :aria-label="Clear search"
        class="absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded-lg text-text-muted dark:text-gray-500 hover:text-text-primary dark:hover:text-white hover:bg-surface-border/30 dark:hover:bg-gray-700/50 transition-colors"
        type="button"
      >
        <XMarkIcon class="w-5 h-5" aria-hidden="true" />
      </button>
    </div>

    <Transition name="slide-down">
      <div 
        v-show="showDropdown" 
        :id="`${id}-suggestions`"
        class="absolute z-50 mt-1 w-full max-h-60 overflow-y-auto glass-card-lg rounded-xl shadow-lg ring-1 ring-surface-border/50 dark:ring-surface-border/30 py-1 animate-scale-in"
        role="listbox"
      >
        <div 
          v-for="(suggestion, index) in suggestions" 
          :key="suggestion"
          :id="`${id}-suggestion-${index}`"
          @click="selectSuggestion(suggestion)"
          @mousedown.prevent
          :class="[
            'px-4 py-2.5 text-sm text-text-primary dark:text-white cursor-pointer transition-colors',
            index === selectedIndex ? 'bg-primary-light/50 dark:bg-primary-dark/30 text-primary-dark dark:text-primary-light' : 'hover:bg-surface/50 dark:hover:bg-surface-alt/30'
          ]"
          role="option"
          :aria-selected="index === selectedIndex"
        >
          {{ suggestion }}
        </div>
      </div>
    </Transition>
  </div>
</template>