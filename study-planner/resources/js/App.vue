<template>
  <router-view v-slot="{ Component }">
    <component v-if="Component" :is="Component" />
    <div v-else class="min-h-screen flex items-center justify-center bg-brand-background text-text-primary" role="status">
        Loading Study Planner…
    </div>
  </router-view>
</template>
