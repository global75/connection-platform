<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-50 to-white flex flex-col items-center justify-center px-4">
    <RouterLink to="/" class="mb-8 text-2xl font-bold text-primary-600">Remote Arena</RouterLink>
    <div class="w-full max-w-md card p-8">
      <RouterView />
    </div>
  </div>
</template>

<script setup>
import { RouterLink, RouterView } from 'vue-router'
</script>
