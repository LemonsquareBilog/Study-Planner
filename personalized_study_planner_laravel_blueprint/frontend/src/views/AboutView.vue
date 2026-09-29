<script setup lang="ts">
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import AuroraHero from '@/components/AuroraHero.vue'
</script>

<template>
  <DefaultLayout>
    <AuroraHero 
      title="About StudyPlanner"
      subtitle="Empowering students worldwide with AI-powered personalized learning"
      primaryAction={{ label: 'Get Started Free', href: '/register' }}
      secondaryAction={{ label: 'View Features', href: '/features' }}
    />
    
    <section class="px-4 py-20 mx-auto max-w-7xl">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Our Mission</h2>
          <p class="text-lg text-gray-600 dark:text-gray-300 mb-6">
            StudyPlanner was born from a simple observation: every student learns differently, 
            yet traditional study tools treat everyone the same.
          </p>
          <p class="text-lg text-gray-600 dark:text-gray-300 mb-6">
            We believe that by combining cognitive science with artificial intelligence, 
            we can create a study companion that truly understands each student's unique 
            learning patterns, preferences, and goals.
          </p>
          <p class="text-lg text-gray-600 dark:text-gray-300">
            Our platform adapts in real-time, creating personalized study schedules that 
            optimize retention, reduce stress, and help students achieve their academic goals.
          </p>
        </div>
        <div class="glass-card-lg rounded-2xl p-8">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 text-center">By the Numbers</h3>
          <div class="grid grid-cols-2 gap-8">
            <div class="text-center">
              <p class="text-3xl font-bold text-pink-600 dark:text-pink-400">10,000+</p>
              <p class="text-gray-600 dark:text-gray-400">Active Students</p>
            </div>
            <div class="text-center">
              <p class="text-3xl font-bold text-pink-600 dark:text-pink-400">500,000+</p>
              <p class="text-gray-600 dark:text-gray-400">Study Sessions Generated</p>
            </div>
            <div class="text-center">
              <p class="text-3xl font-bold text-pink-600 dark:text-pink-400">94%</p>
              <p class="text-gray-600 dark:text-gray-400">Retention Improvement</p>
            </div>
            <div class="text-center">
              <p class="text-3xl font-bold text-pink-600 dark:text-pink-400">50+</p>
              <p class="text-gray-600 dark:text-gray-400">Universities Partnered</p>
            </div>
          </div>
        </div>
      </div>
      
      <div class="mt-20">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white text-center mb-12">Our Values</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="glass-card-lg rounded-2xl p-6 text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-pink-100/80 dark:bg-pink-900/30 rounded-xl flex items-center justify-center text-pink-600 dark:text-pink-400">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.549c0-.266.01-.532.03-.798a2 2 0 01-.338-.706l-2.79-2.79a2 2 0 01-.338-.706.57.57 0 01.03-.797V16c0-.266.01-.532.03-.798a2 2 0 01-.338-.706l-2.79-2.79a2 2 0 01-.338-.706.57.57 0 01.03-.797V14.5c0-.266.01-.532.03-.798a2 2 0 01-.338-.706l-2.79-2.79a2 2 0 01-.338-.706.57.57 0 01.03-.797V11.5a6.883 6.883 0 01-.548-.547 6.772 6.772 0 010-9.652 6.883 6.772 0 019.652 0 6.883 6.772 0 010 9.652l-2.79 2.79a2 2 0 01-.338.706.57.57 0 01-.03.797v3a6.883 6.883 0 01-.548.547z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Student-First</h3>
            <p class="text-gray-600 dark:text-gray-400">Every feature is designed with student success as the primary goal</p>
          </div>
          <div class="glass-card-lg rounded-2xl p-6 text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-pink-100/80 dark:bg-pink-900/30 rounded-xl flex items-center justify-center text-pink-600 dark:text-pink-400">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-1.535z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Evidence-Based</h3>
            <p class="text-gray-600 dark:text-gray-400">Built on cognitive science and learning research</p>
          </div>
          <div class="glass-card-lg rounded-2xl p-6 text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-pink-100/80 dark:bg-pink-900/30 rounded-xl flex items-center justify-center text-pink-600 dark:text-pink-400">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m5.464 0a9 9 0 00-5.454.856M9 20H4a3 3 0 01-3-3v-5a3 3 0 013-3h10a3 3 0 013 3v5a3 3 0 01-3 3h-2m0 0l-3-3m0 0l3-3m0 0l-3 3"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Accessible</h3>
            <p class="text-gray-600 dark:text-gray-400">Designed for all learners, regardless of ability or background</p>
          </div>
        </div>
      </div>
    </DefaultLayout>
  </template>