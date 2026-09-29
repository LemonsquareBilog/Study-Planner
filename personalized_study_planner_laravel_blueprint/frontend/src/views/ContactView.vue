<script setup lang="ts">
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import AuroraHero from '@/components/AuroraHero.vue'
</script>

<template>
  <DefaultLayout>
    <AuroraHero 
      title="Contact Us"
      subtitle="Have questions? We'd love to hear from you"
      primaryAction={{ label: 'Start Free Trial', href: '/register' }}
      secondaryAction={{ label: 'View Features', href: '/features' }}
    />
    
    <section class="px-4 py-20 mx-auto max-w-7xl">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Get in Touch</h2>
          <p class="text-lg text-gray-600 dark:text-gray-300 mb-8">
            Whether you have questions about our platform, need help with your account, 
            or want to partner with us, our team is here to help.
          </p>
          
          <div class="space-y-6">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl bg-pink-100/80 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">Email</h3>
                <p class="text-gray-600 dark:text-gray-400">support@studyplanner.example.com</p>
              </div>
            </div>
            
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl bg-pink-100/80 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0l4.244 4.243a1.998 1.998 0 010 2.827L11 21.657l6 6 6-6"/></svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">Office</h3>
                <p class="text-gray-600 dark:text-gray-400">123 Learning Lane, Education City, EC 12345</p>
              </div>
            </div>
            
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl bg-pink-100/80 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">Hours</h3>
                <p class="text-gray-600 dark:text-gray-400">Mon-Fri 9am-6pm EST</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="glass-card-lg rounded-2xl p-8">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Send us a Message</h3>
          <form class="space-y-6" @submit.prevent>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="name" class="input-label">Name</label>
                <input id="name" type="text" class="input-field" placeholder="Your name" required />
              </div>
              <div>
                <label for="email" class="input-label">Email</label>
                <input id="email" type="email" class="input-field" placeholder="you@example.com" required />
              </div>
            </div>
            <div>
              <label for="subject" class="input-label">Subject</label>
              <select id="subject" class="select-field input-field">
                <option value="">Select a topic</option>
                <option value="general">General Inquiry</option>
                <option value="support">Technical Support</option>
                <option value="billing">Billing & Payments</option>
                <option value="partnership">Partnership</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div>
              <label for="message" class="input-label">Message</label>
              <textarea id="message" rows="5" class="input-field resize-y" placeholder="How can we help you?" required></textarea>
            </div>
            <button type="submit" class="btn-primary w-full">Send Message</button>
          </form>
        </div>
      </div>
    </DefaultLayout>
  </template>