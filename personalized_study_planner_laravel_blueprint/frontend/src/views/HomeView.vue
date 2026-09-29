<script setup lang="ts">
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import AuroraHero from '@/components/AuroraHero.vue'
import FeatureCard from '@/components/FeatureCard.vue'

const features = [
  {
    icon: 'brain',
    title: 'AI Study Plans',
    description: 'Personalized schedules based on your goals, performance, and available time.',
  },
  {
    icon: 'chart',
    title: 'Progress Analytics',
    description: 'Detailed insights into your learning patterns, strengths, and growth areas.',
  },
  {
    icon: 'bell',
    title: 'Smart Reminders',
    description: 'Never miss a deadline with intelligent notifications via email, push, and in-app.',
  },
  {
    icon: 'book',
    title: 'Smart Recommendations',
    description: 'Get personalized resource recommendations based on your quiz performance.',
  },
  {
    icon: 'clock',
    title: 'Flexible Scheduling',
    description: 'Supports overnight study sessions and adapts to your preferred hours.',
  },
  {
    icon: 'users',
    title: 'Collaborative Learning',
    description: 'Join study rooms, share resources, and track progress with peers.',
  },
]
</script>

<template>
  <DefaultLayout>
    <AuroraHero />
    
    <!-- Features Section -->
    <section class="px-4 py-20 mx-auto max-w-7xl">
      <div class="text-center mb-16">
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">
          Everything you need to study smarter
        </h2>
        <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
          Our AI-powered platform adapts to your learning style and schedule.
        </p>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <FeatureCard 
          v-for="feature in features" 
          :key="feature.icon"
          :icon="feature.icon"
          :title="feature.title"
          :description="feature.description"
        />
      </div>
    </section>
    
    <!-- CTA Section -->
    <section class="px-4 py-20 mx-auto max-w-7xl">
      <div class="glass-card-lg rounded-2xl p-8 md:p-16 text-center">
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">
          Ready to study smarter?
        </h2>
        <p class="text-lg text-gray-600 dark:text-gray-300 mb-8 max-w-xl mx-auto">
          Join thousands of students who've improved their grades with personalized study plans.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
          <RouterLink to="/register" class="btn-primary">Start Free Trial</RouterLink>
          <RouterLink to="/demo" class="btn-secondary">Watch Demo</RouterLink>
        </div>
      </div>
    </section>
  </DefaultLayout>
</template>