<template>
  <div class="min-h-screen flex flex-col">
    <!-- Navbar -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <RouterLink to="/" class="flex items-center gap-2">
          <span class="text-xl font-bold text-primary-600">Connextion</span>
        </RouterLink>

        <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-gray-600">
          <!-- Jobs, with the four ways to look one level down -->
          <div class="relative" @mouseenter="jobsOpen = true" @mouseleave="jobsOpen = false">
            <RouterLink to="/jobs" class="hover:text-primary-600 py-5">Jobs</RouterLink>
            <div v-if="jobsOpen" class="absolute left-0 top-full w-52 card p-2 shadow-lg">
              <RouterLink to="/jobs" class="block px-3 py-2 rounded-lg text-sm hover:bg-primary-50">All jobs</RouterLink>
              <RouterLink to="/jobs/near_me" class="block px-3 py-2 rounded-lg text-sm hover:bg-primary-50">📍 Local</RouterLink>
              <RouterLink to="/jobs/nationwide" class="block px-3 py-2 rounded-lg text-sm hover:bg-primary-50">🇺🇸 National</RouterLink>
              <RouterLink to="/jobs/remote" class="block px-3 py-2 rounded-lg text-sm hover:bg-primary-50">🌎 Remote</RouterLink>
              <RouterLink to="/jobs/international" class="block px-3 py-2 rounded-lg text-sm hover:bg-primary-50">✈️ International</RouterLink>
            </div>
          </div>
          <RouterLink to="/for-employers" class="hover:text-primary-600">For Employers</RouterLink>
          <RouterLink to="/faq" class="hover:text-primary-600">FAQ</RouterLink>
        </nav>

        <div class="flex items-center gap-3">
          <RouterLink to="/login"    class="btn-secondary text-sm">Log in</RouterLink>
          <RouterLink to="/register" class="btn-primary text-sm">Get started</RouterLink>
        </div>
      </div>

      <!-- Mobile nav -->
      <div class="md:hidden border-t border-gray-100 px-4 py-2 flex gap-4 text-sm text-gray-600 overflow-x-auto">
        <RouterLink to="/jobs" class="whitespace-nowrap">All jobs</RouterLink>
        <RouterLink to="/jobs/near_me" class="whitespace-nowrap">Local</RouterLink>
        <RouterLink to="/jobs/remote" class="whitespace-nowrap">Remote</RouterLink>
        <RouterLink to="/jobs/international" class="whitespace-nowrap">International</RouterLink>
        <RouterLink to="/for-employers" class="whitespace-nowrap">For employers</RouterLink>
        <RouterLink to="/faq" class="whitespace-nowrap">FAQ</RouterLink>
      </div>
    </header>

    <main class="flex-1">
      <RouterView />
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 text-sm py-10">
      <div class="max-w-7xl mx-auto px-4">
        <div class="grid gap-8 sm:grid-cols-3 text-center sm:text-left">
          <div>
            <p class="font-semibold text-white mb-1">Connextion</p>
            <p>Connecting talent and businesses locally, nationally and globally.</p>
          </div>
          <div>
            <p class="font-semibold text-white mb-2">For professionals</p>
            <ul class="space-y-1">
              <li><RouterLink to="/jobs" class="hover:text-white">Browse all jobs</RouterLink></li>
              <li><RouterLink to="/jobs/near_me" class="hover:text-white">Jobs near me</RouterLink></li>
              <li><RouterLink to="/jobs/remote" class="hover:text-white">Remote jobs</RouterLink></li>
              <li><RouterLink to="/jobs/international" class="hover:text-white">International jobs</RouterLink></li>
              <li><RouterLink to="/register" class="hover:text-white">Sign up</RouterLink></li>
            </ul>
          </div>
          <div>
            <p class="font-semibold text-white mb-2">For employers</p>
            <ul class="space-y-1">
              <li><RouterLink to="/for-employers" class="hover:text-white">How hiring works</RouterLink></li>
              <li><RouterLink to="/register?role=employer" class="hover:text-white">Post a job</RouterLink></li>
              <li><RouterLink to="/faq" class="hover:text-white">FAQ</RouterLink></li>
            </ul>
          </div>
        </div>
        <p class="mt-8 text-center">© {{ new Date().getFullYear() }} Connextion Inc. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, RouterView } from 'vue-router'

const jobsOpen = ref(false)
</script>
