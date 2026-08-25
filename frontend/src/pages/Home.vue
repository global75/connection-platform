<template>
  <!-- Hero -->
  <section class="bg-gradient-to-br from-primary-700 to-primary-900 text-white py-20 px-4">
    <div class="max-w-4xl mx-auto text-center">
      <h1 class="text-4xl sm:text-5xl font-bold leading-tight mb-6">
        Find talent. Find opportunities. <span class="text-primary-300">Anywhere.</span>
      </h1>
      <p class="text-lg sm:text-xl text-primary-100 mb-10 max-w-2xl mx-auto">
        Connextion connects professionals and businesses for local, national and international
        hiring — from on-site and hybrid roles to remote opportunities.
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <RouterLink to="/jobs" class="btn bg-white text-primary-700 hover:bg-primary-50 text-base px-8 py-3">
          Find Jobs
        </RouterLink>
        <RouterLink to="/for-employers" class="btn border-2 border-white text-white hover:bg-white/10 text-base px-8 py-3">
          Hire Talent
        </RouterLink>
      </div>
    </div>
  </section>

  <!-- Quick search: keyword, place, and how you want to work -->
  <section class="max-w-5xl mx-auto px-4 -mt-8">
    <div class="card p-4 grid grid-cols-1 sm:grid-cols-12 gap-3">
      <input
        v-model="query" type="text" placeholder="Job title, skill or company"
        class="input sm:col-span-4" @keydown.enter="search"
      />
      <div class="sm:col-span-4">
        <LocationInput v-model="location" placeholder="City, state or country" @select="onPlace" />
      </div>
      <select v-model="arrangement" class="input sm:col-span-2">
        <option value="">Any arrangement</option>
        <option v-for="option in WORK_ARRANGEMENTS" :key="option.value" :value="option.value">{{ option.label }}</option>
      </select>
      <button @click="search" class="btn-primary sm:col-span-2">Search</button>
    </div>

    <!-- The four ways to look, in plain words -->
    <div class="flex flex-wrap justify-center gap-2 mt-4">
      <RouterLink to="/jobs/near_me" class="badge-gray hover:bg-gray-200 px-3 py-1.5">📍 Near me</RouterLink>
      <RouterLink to="/jobs/nationwide" class="badge-gray hover:bg-gray-200 px-3 py-1.5">🇺🇸 Nationwide</RouterLink>
      <RouterLink to="/jobs/remote" class="badge-gray hover:bg-gray-200 px-3 py-1.5">🌎 Remote</RouterLink>
      <RouterLink to="/jobs/international" class="badge-gray hover:bg-gray-200 px-3 py-1.5">✈️ International</RouterLink>
    </div>
  </section>

  <!-- Real totals only: anything at zero is simply not shown -->
  <section v-if="visibleStats.length" class="max-w-7xl mx-auto px-4 py-14 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
    <div v-for="stat in visibleStats" :key="stat.label" class="card p-6">
      <p class="text-3xl font-bold text-primary-600">{{ stat.value.toLocaleString() }}</p>
      <p class="text-sm text-gray-500 mt-1">{{ stat.label }}</p>
    </div>
  </section>

  <!-- Categories, from what is actually posted -->
  <section v-if="categories.length" class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4">
      <h2 class="text-2xl font-bold mb-8 text-center">Browse by category</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <button
          v-for="cat in categories" :key="cat.name"
          @click="searchCategory(cat.name)"
          class="card p-5 text-left hover:border-primary-300 hover:shadow-md transition-all cursor-pointer group"
        >
          <span class="text-2xl">{{ emojiFor(cat.name) }}</span>
          <p class="font-semibold mt-2 group-hover:text-primary-600">{{ cat.name }}</p>
          <p class="text-sm text-gray-400">{{ cat.jobs_count }} {{ cat.jobs_count === 1 ? 'job' : 'jobs' }}</p>
        </button>
      </div>
    </div>
  </section>

  <!-- Places that really have jobs right now -->
  <section v-if="cities.length || countries.length" class="py-16 px-4">
    <div class="max-w-7xl mx-auto">
      <h2 class="text-2xl font-bold mb-2 text-center">Find opportunities near you</h2>
      <p class="text-gray-500 text-center mb-8">Every location below has live roles on the platform today.</p>

      <div class="flex flex-wrap justify-center gap-3">
        <RouterLink
          v-for="city in cities" :key="city.slug"
          :to="`/jobs/in/${city.slug}`"
          class="card px-4 py-3 hover:border-primary-300 hover:shadow-md transition-all"
        >
          <span class="font-medium">📍 {{ city.label }}</span>
          <span class="text-sm text-gray-400 ml-2">{{ city.jobs_count }}</span>
        </RouterLink>
      </div>

      <div v-if="countries.length" class="flex flex-wrap justify-center gap-3 mt-4">
        <RouterLink
          v-for="country in countries" :key="country.slug"
          :to="`/jobs/in/${country.slug}`"
          class="text-sm text-gray-600 hover:text-primary-600 underline underline-offset-4"
        >
          Jobs in {{ country.label }}
        </RouterLink>
      </div>
    </div>
  </section>

  <!-- How work happens here: three arrangements, one marketplace -->
  <section class="bg-white py-16 px-4">
    <div class="max-w-5xl mx-auto">
      <h2 class="text-2xl font-bold mb-8 text-center">However you want to work</h2>
      <div class="grid gap-6 md:grid-cols-3">
        <div v-for="option in WORK_ARRANGEMENTS" :key="option.value" class="card p-6">
          <span class="text-2xl">{{ option.icon }}</span>
          <h3 class="font-semibold mt-3">{{ option.label }}</h3>
          <p class="text-sm text-gray-500 mt-1">{{ option.hint }}</p>
          <RouterLink
            :to="{ name: 'jobs', query: { work_arrangement: option.value } }"
            class="text-sm text-primary-600 font-medium mt-3 inline-block"
          >Browse {{ option.label.toLowerCase() }} roles →</RouterLink>
        </div>
      </div>
    </div>
  </section>

  <!-- For employers -->
  <section id="employers" class="bg-primary-50 py-16 px-4">
    <div class="max-w-3xl mx-auto text-center">
      <h2 class="text-3xl font-bold mb-4">Hire the right talent — locally or globally</h2>
      <p class="text-gray-600 mb-8">
        Find professionals in your city, across your country, or around the world. Choose on-site,
        hybrid or remote hiring based on what your business actually needs.
      </p>
      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <RouterLink to="/register?role=employer" class="btn-primary text-base px-8 py-3">Post a Job</RouterLink>
        <RouterLink to="/for-employers" class="btn-secondary text-base px-8 py-3">How hiring works</RouterLink>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { discoveryApi } from '@/api/discovery'
import { useSeo } from '@/composables/useSeo'
import LocationInput from '@/components/jobs/LocationInput.vue'
import { WORK_ARRANGEMENTS } from '@/lib/labels'

const router      = useRouter()
const query       = ref('')
const location    = ref('')
const arrangement = ref('')
const coords      = ref({ latitude: null, longitude: null })

const stats      = ref({})
const categories = ref([])
const cities     = ref([])
const countries  = ref([])

useSeo({
  title: null,
  description: 'Connextion connects professionals and businesses for local, national and international hiring — on-site, hybrid and remote roles in one marketplace.',
  canonical: '/',
})

/**
 * Only real, non-zero numbers reach the page. An empty marketplace shows no
 * counters rather than invented ones.
 */
const visibleStats = computed(() => [
  { label: 'Active jobs', value: stats.value.active_jobs },
  { label: 'Hiring companies', value: stats.value.hiring_companies },
  { label: 'Professionals', value: stats.value.professionals },
  { label: 'Countries with jobs', value: stats.value.countries_with_jobs },
].filter((s) => Number(s.value) > 0))

const CATEGORY_EMOJI = {
  Engineering: '💻', Design: '🎨', Marketing: '📣', 'Data Science': '📊',
  Finance: '💰', Product: '🗂️', Operations: '⚙️', 'Customer Success': '🤝',
  Sales: '📈', Healthcare: '🩺', Education: '🎓', Administration: '🗃️',
}
const emojiFor = (name) => CATEGORY_EMOJI[name] ?? '📁'

function onPlace(suggestion) {
  coords.value = { latitude: suggestion.latitude, longitude: suggestion.longitude }
}

function search() {
  router.push({
    name: 'jobs',
    query: {
      q: query.value || undefined,
      location: location.value || undefined,
      latitude: location.value ? coords.value.latitude ?? undefined : undefined,
      longitude: location.value ? coords.value.longitude ?? undefined : undefined,
      work_arrangement: arrangement.value || undefined,
    },
  })
}

function searchCategory(name) {
  router.push({ name: 'jobs', query: { category: name } })
}

onMounted(async () => {
  const [statsRes, categoriesRes, locationsRes] = await Promise.allSettled([
    discoveryApi.stats(),
    discoveryApi.categories(),
    discoveryApi.locations({ limit: 8 }),
  ])

  if (statsRes.status === 'fulfilled') stats.value = statsRes.value.data.stats
  if (categoriesRes.status === 'fulfilled') categories.value = categoriesRes.value.data.categories
  if (locationsRes.status === 'fulfilled') {
    cities.value    = locationsRes.value.data.cities
    countries.value = locationsRes.value.data.countries
  }
})
</script>
