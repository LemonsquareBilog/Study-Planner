<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowRightIcon } from '@heroicons/vue/24/outline'

interface Props {
  title?: string
  subtitle?: string
  primaryAction?: { label: string; href: string }
  secondaryAction?: { label: string; href: string }
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Personalized Study Planner',
  subtitle: 'AI-powered learning that adapts to you. Study smarter, not harder.',
  primaryAction: { label: 'Get Started Free', href: '/register' },
  secondaryAction: { label: 'Watch Demo', href: '#demo' },
})

const blobs = ref([
  { x: 25, y: 25, size: 64, color: 'pink', delay: 0 },
  { x: 75, y: 75, size: 64, color: 'purple', delay: 2000 },
  { x: 50, y: 50, size: 48, color: 'indigo', delay: 4000 },
])

let mouseX = 50, mouseY = 50
const handleMouseMove = (e: MouseEvent) => {
  mouseX = (e.clientX / window.innerWidth) * 100
  mouseY = (e.clientY / window.innerHeight) * 100
}

onMounted(() => window.addEventListener('mousemove', handleMouseMove))
onUnmounted(() => window.removeEventListener('mousemove', handleMouseMove))
</script>

<template>
  <section class="relative min-h-screen flex items-center justify-center overflow-hidden">
    <!-- Animated Aurora Background -->
    <div class="absolute inset-0 -z-10 overflow-hidden">
      <div 
        v-for="(blob, i) in blobs" 
        :key="i"
        class="aurora-blob"
        :style="{
          width: blob.size + 'rem',
          height: blob.size + 'rem',
          top: 'calc(' + blob.y + '% - ' + (blob.size/2) + 'rem)',
          left: 'calc(' + blob.x + '% - ' + (blob.size/2) + 'rem)',
          backgroundColor: 'var(--color-' + blob.color + ')',
          animationDelay: blob.delay + 'ms',
        }"
      />
      
      <!-- Mouse-following glow -->
      <div 
        class="absolute rounded-full blur-3xl opacity-20 pointer-events-none transition-all duration-300"
        :style="{
          width: '30rem',
          height: '30rem',
          top: 'calc(' + mouseY + '% - 15rem)',
          left: 'calc(' + mouseX + '% - 15rem)',
          backgroundColor: '#FFC0CB',
        }"
      />
    </div>

    <!-- Floating Particles -->
    <div class="absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
      <div v-for="i in 20" :key="i" 
        class="absolute w-1 h-1 bg-pink-300/50 rounded-full animate-float"
        :style="{
          left: (i * 5) + '%',
          top: (i * 7) % 100 + '%',
          animationDelay: (i * 0.5) + 's',
          animationDuration: (15 + i * 2) + 's',
        }"
      />
    </div>

    <!-- Content -->
    <div class="relative z-10 px-4 py-20 mx-auto max-w-7xl text-center">
      <div class="max-w-3xl mx-auto">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/80 dark:bg-gray-800/80 backdrop-blur border border-pink-200/50 dark:border-pink-800/50 mb-8">
          <span class="w-2 h-2 rounded-full bg-pink-400 animate-pulse" aria-hidden="true"></span>
          <span class="text-sm font-medium text-pink-700 dark:text-pink-300">New: AI Study Plans v2.0</span>
        </div>

        <!-- Title -->
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-gray-900 dark:text-white mb-6">
          <span class="block">{{ props.title }}</span>
          <span class="block bg-gradient-to-r from-pink-600 via-purple-600 to-indigo-600 bg-clip-text text-transparent">
            {{ props.title.split(' ').slice(1).join(' ') }}
          </span>
        </h1>

        <!-- Subtitle -->
        <p class="text-lg sm:text-xl text-gray-600 dark:text-gray-300 mb-10 max-w-2xl mx-auto leading-relaxed">
          {{ props.subtitle }}
        </p>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
          <RouterLink 
            :to="props.primaryAction?.href" 
            class="btn-primary group"
          >
            {{ props.primaryAction?.label }}
            <ArrowRightIcon class="ml-2 w-5 h-5 transition-transform group-hover:translate-x-1" aria-hidden="true" />
          </RouterLink>
          <RouterLink 
            :to="props.secondaryAction?.href" 
            class="btn-secondary"
          >
            {{ props.secondaryAction?.label }}
          </RouterLink>
        </div>

        <!-- Trust Indicators -->
        <div class="flex flex-wrap items-center justify-center gap-6 text-sm text-gray-500 dark:text-gray-400">
          <div class="flex items-center gap-1">
            <svg class="w-5 h-5 text-pink-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span>No credit card required</span>
          </div>
          <div class="flex items-center gap-1">
            <svg class="w-5 h-5 text-pink-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span>14-day free trial</span>
          </div>
          <div class="flex items-center gap-1">
            <svg class="w-5 h-5 text-pink-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span>Cancel anytime</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Scroll Indicator -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce" aria-hidden="true">
      <svg class="w-6 h-6 text-pink-400/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
      </svg>
    </div>
  </section>
</template>

<style scoped>
@keyframes float {
  0%, 100% { transform: translateY(0) translateX(0); opacity: 0.3; }
  25% { transform: translateY(-20px) translateX(10px); opacity: 0.6; }
  50% { transform: translateY(-40px) translateX(-5px); opacity: 0.4; }
  75% { transform: translateY(-20px) translateX(-10px); opacity: 0.6; }
}

.animate-float {
  animation: float infinite ease-in-out;
}
</style>