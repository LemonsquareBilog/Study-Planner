<script setup lang="ts">
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import AuroraHero from '@/components/AuroraHero.vue'
</script>

<template>
  <DefaultLayout>
    <AuroraHero 
      title="Pricing"
      subtitle="Simple, transparent pricing for every learner"
      primaryAction={{ label: 'Start Free Trial', href: '/register' }}
      secondaryAction={{ label: 'Contact Sales', href: '/contact' }}
    />
    
    <section class="px-4 py-20 mx-auto max-w-7xl">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Free Plan -->
        <div class="glass-card-lg rounded-2xl p-8 flex flex-col">
          <div class="text-center mb-8">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Free</h3>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Perfect for getting started</p>
            <div class="mt-6">
              <span class="text-4xl font-bold text-gray-900 dark:text-white">$0</span>
              <span class="text-gray-500 dark:text-gray-400">/month</span>
            </div>
          </div>
          <ul class="space-y-4 mb-8">
            <li class="flex items-center gap-3"><svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z"/></svg> Up to 3 subjects</li>
            <li class="flex items-center gap-3"><svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z"/></svg> 10 tasks per subject</li>
            <li class="flex items-center gap-3"><svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z"/></svg> Basic study plan generation</li>
            <li class="flex items-center gap-3"><svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z"/></svg> Email notifications</li>
            <li class="flex items-center gap-3 text-gray-400 dark:text-gray-500"><svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L11.414 10l-4.293 4.293a1 1 0 01-1.414 1.414z"/></svg> AI recommendations</li>
            <li class="flex items-center gap-3 text-gray-400 dark:text-gray-500"><svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 011.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L11.414 10l-4.293 4.293a1 1 0 01-1.414 1.414z"/></svg> Advanced analytics</li>
          </ul>
          <RouterLink to="/register" class="btn-secondary w-full">Get Started</RouterLink>
        </div>
        
        <!-- Pro Plan -->
        <div class="glass-card-lg rounded-2xl p-8 flex flex-col relative">
          <div class="absolute -top-3 left-1/2 -translate-x-1/2">
            <span class="bg-pink-500 text-white text-xs font-semibold px-3 py-1 rounded-full">Most Popular</span>
          </div>
          <div class="text-center mb-8">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Pro</h3>
            <p class="text-gray-600 dark:text-gray-400 mt-2">For serious learners</p>
            <div class="mt-6">
              <span class="text-4xl font-bold text-gray-900 dark:text-white">$9</span>
              <span class="text-gray-500 dark:text-gray-400">/month</span>
            </div>
          </div>
          <ul class="space-y-4 mb-8">
            <li class="flex items-center gap-3"><svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z"/></svg> Unlimited subjects</li>
            <li class="flex items-center gap-3"><svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z"/></svg> Unlimited tasks</li>
            <li class="flex items-center gap-3"><svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z"/></svg> Advanced AI study plans</li>
            <li class="flex items-center gap-3"><svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z"/></svg> AI recommendations</li>
            <li class="flex items-center gap-3"><svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z"/></svg> Advanced analytics</li>
            <li class="flex items-center gap-3"><svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z"/></svg> Priority support</li>
          </ul>
          <RouterLink to="/register" class="btn-primary w-full">Start Free Trial</RouterLink>
        </div>
        
        <!-- Team Plan -->
        <div class="glass-card-lg rounded-2xl p-8 flex flex-col">
          <div class="text-center mb-8">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Team</h3>
            <p class="text-gray-600 dark:text-gray-400 mt-2">For study groups & classrooms</p>
            <div class="mt-6">
              <span class="text-4xl font-bold text-gray-900 dark:text-white">$29</span>
              <span class="text-gray-500 dark:text-gray-400">/month</span>
            </div>
          </div>
          <ul class="space-y-4 mb-8">
            <li class="flex items-center gap-3"><svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z"/></svg> Everything in Pro</li>
            <li class="flex items-center gap-3"><svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z"/></svg> Up to 10 members</li>
            <li class="flex items-center gap-3"><svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z"/></svg> Shared study plans</li>
            <li class="flex items-center gap-3"><svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z"/></svg> Group analytics</li>
            <li class="flex items-center gap-3"><svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z"/></svg> Admin dashboard</li>
            <li class="flex items-center gap-3"><svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z"/></svg> Dedicated support</li>
          </ul>
          <RouterLink to="/contact" class="btn-secondary w-full">Contact Sales</RouterLink>
        </div>
      </div>
      
      <!-- FAQ -->
      <section class="mt-20">
        <h2 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-12">Frequently Asked Questions</h2>
        <div class="max-w-3xl mx-auto space-y-4">
          <FAQItem question="Can I change plans later?" answer="Yes, you can upgrade or downgrade at any time. Changes take effect immediately, and we'll prorate the difference." />
          <FAQItem question="Is there a free trial?" answer="Yes! All paid plans come with a 14-day free trial. No credit card required to start." />
          <FAQItem question="What payment methods do you accept?" answer="We accept all major credit cards, PayPal, and bank transfers for annual Team plans." />
          <FAQItem question="Can I cancel anytime?" answer="Yes, you can cancel your subscription at any time. You'll continue to have access until the end of your billing period." />
          <FAQItem question="Is my data secure?" answer="Absolutely. We use bank-level encryption, regular security audits, and never sell your data." />
        </div>
      </section>
    </DefaultLayout>
  </template>