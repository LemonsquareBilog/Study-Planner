<template>
  <router-view v-slot="{ Component }">
    <transition name="fade" mode="out-in">
      <component v-if="Component" :is="Component" />
      <div v-else class="min-h-screen flex items-center justify-center bg-brand-background text-text-primary" role="status">
        Loading Study Planner…
      </div>
    </transition>
  </router-view>
</template>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
