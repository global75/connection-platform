<template>
  <!-- Hero -->
  <section class="bg-gradient-to-br from-primary-700 to-primary-900 text-white py-24 px-4">
    <div class="max-w-4xl mx-auto text-center">
      <h1 class="text-5xl font-bold leading-tight mb-6">
        Your Skills, <span class="text-primary-300">US Opportunities</span>
      </h1>
      <p class="text-xl text-primary-100 mb-10 max-w-2xl mx-auto">
        Remote Arena bridges the gap between world-class international talent and top US companies hiring remotely.
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <RouterLink to="/jobs" class="btn bg-white text-primary-700 hover:bg-primary-50 text-base px-8 py-3">
          Browse Jobs
        </RouterLink>
        <RouterLink to="/register?role=job_seeker" class="btn border-2 border-white text-white hover:bg-white/10 text-base px-8 py-3">
          Create Profile
        </RouterLink>
      </div>
    </div>
  </section>

  <!-- Quick search -->
  <section class="max-w-4xl mx-auto px-4 -mt-8">
    <div class="card p-4 flex flex-col sm:flex-row gap-3">
      <input v-model="query" type="text" placeholder="Job title, skill, or keyword…" class="input flex-1" @keydown.enter="search" />
      <select v-model="locationType" class="input sm:w-44">
        <option value="">All locations</option>
        <option value="remote">Remote</option>
        <option value="hybrid">Hybrid</option>
        <option value="on_site">On-site</option>
      </select>
      <button @click="search" class="btn-primary px-8">Search</button>
    </div>
  </section>

  <!-- Stats -->
  <section class="max-w-7xl mx-auto px-4 py-16 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
    <div v-for="stat in stats" :key="stat.label" class="card p-6">
      <p class="text-3xl font-bold text-primary-600">{{ stat.value }}</p>
      <p class="text-sm text-gray-500 mt-1">{{ stat.label }}</p>
    </div>
  </section>

  <!-- Categories -->
  <section class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4">
      <h2 class="text-2xl font-bold mb-8 text-center">Browse by Category</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <button
          v-for="cat in categories" :key="cat.name"
          @click="searchCategory(cat.name)"
          class="card p-5 text-left hover:border-primary-300 hover:shadow-md transition-all cursor-pointer group"
        >
          <span class="text-2xl">{{ cat.emoji }}</span>
          <p class="font-semibold mt-2 group-hover:text-primary-600">{{ cat.name }}</p>
          <p class="text-sm text-gray-400">{{ cat.count }}+ jobs</p>
        </button>
      </div>
    </div>
  </section>

  <!-- For employers CTA -->
  <section id="employers" class="bg-primary-50 py-16 px-4">
    <div class="max-w-3xl mx-auto text-center">
      <h2 class="text-3xl font-bold mb-4">Hire globally, grow faster</h2>
      <p class="text-gray-600 mb-8">
        Access a pre-vetted pool of skilled professionals from 50+ countries. Post a job in minutes.
      </p>
      <RouterLink to="/register?role=employer" class="btn-primary text-base px-8 py-3">
        Post a Job Free
      </RouterLink>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { RouterLink } from 'vue-router'

const router      = useRouter()
const query       = ref('')
const locationType = ref('')

const stats = [
  { value: '2,400+', label: 'Active Jobs' },
  { value: '18,000+', label: 'Registered Talent' },
  { value: '340+', label: 'Hiring Companies' },
  { value: '50+', label: 'Countries Represented' },
]

const categories = [
  { name: 'Engineering',   emoji: '💻', count: 820 },
  { name: 'Design',        emoji: '🎨', count: 210 },
  { name: 'Marketing',     emoji: '📣', count: 180 },
  { name: 'Data Science',  emoji: '📊', count: 150 },
  { name: 'Finance',       emoji: '💰', count: 95  },
  { name: 'Product',       emoji: '🗂️', count: 130 },
  { name: 'Operations',    emoji: '⚙️', count: 75  },
  { name: 'Customer Success', emoji: '🤝', count: 90 },
]

function search() {
  router.push({ name: 'jobs', query: { q: query.value, location_type: locationType.value } })
}

function searchCategory(name) {
  router.push({ name: 'jobs', query: { category: name } })
}
</script>
