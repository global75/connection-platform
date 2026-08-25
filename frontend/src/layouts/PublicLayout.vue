<template>
  <div class="min-h-screen flex flex-col">
    <!-- Navbar -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <RouterLink to="/" class="flex items-center gap-2">
          <span class="text-xl font-bold text-primary-600">Remote Arena</span>
        </RouterLink>

        <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-gray-600">
          <RouterLink to="/jobs" class="hover:text-primary-600">Browse Jobs</RouterLink>
          <a href="#employers" class="hover:text-primary-600">For Employers</a>
        </nav>

        <div class="flex items-center gap-3">
          <RouterLink to="/login"    class="btn-secondary text-sm">Log in</RouterLink>
          <RouterLink to="/register" class="btn-primary text-sm">Get started</RouterLink>
        </div>
      </div>
    </header>

    <main class="flex-1">
      <RouterView />
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 text-sm py-10">
      <div class="max-w-7xl mx-auto px-4 text-center">
        <p class="font-semibold text-white mb-1">Remote Arena</p>
        <p>Connecting global talent with US opportunities.</p>
        <p class="mt-4">© {{ new Date().getFullYear() }} RemoteArena LLC. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { RouterLink, RouterView } from 'vue-router'
</script>
