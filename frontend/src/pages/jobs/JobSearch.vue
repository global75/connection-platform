<template>
  <div class="max-w-7xl mx-auto px-4 py-8 lg:py-10">
    <!-- Page heading: location and mode pages get their own -->
    <header class="mb-6">
      <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">{{ heading }}</h1>
      <p class="text-gray-500 mt-1">{{ subheading }}</p>
    </header>

    <div class="flex flex-col lg:flex-row gap-6">
      <!-- Desktop filters -->
      <aside class="hidden lg:block w-72 flex-shrink-0">
        <div class="card p-5 sticky top-20 max-h-[calc(100vh-6rem)] overflow-y-auto">
          <h2 class="font-semibold text-gray-900 mb-4">Filters</h2>
          <JobFilters :filters="filters" :counts="counts" @apply="doSearch" @reset="resetFilters" />
        </div>
      </aside>

      <div class="flex-1 min-w-0">
        <!-- Mobile: one search box plus a filter drawer, never a wall of controls -->
        <div class="lg:hidden mb-4 flex gap-2">
          <input v-model="filters.q" type="text" class="input flex-1" placeholder="Search jobs" @keydown.enter="doSearch" />
          <button @click="drawerOpen = true" class="btn-secondary flex-shrink-0">
            Filters<span v-if="activeFilterCount" class="badge-blue ml-1">{{ activeFilterCount }}</span>
          </button>
        </div>

        <div class="flex items-center justify-between mb-4">
          <p class="text-sm text-gray-500">
            <template v-if="!store.loading">{{ store.pagination?.total ?? 0 }} jobs found</template>
            <template v-else>Searching…</template>
          </p>
          <select v-model="filters.sort" class="input w-auto text-sm py-1.5" @change="doSearch">
            <option value="recent">Most recent</option>
            <option value="salary">Highest salary</option>
          </select>
        </div>

        <div v-if="store.loading" class="space-y-4">
          <div v-for="i in 5" :key="i" class="card p-5 animate-pulse h-32 bg-gray-100" />
        </div>

        <div v-else-if="store.jobs.length === 0" class="card p-10 text-center">
          <p class="text-gray-500">No jobs match this search yet.</p>
          <p class="text-sm text-gray-400 mt-1">Try a wider distance, a different location, or search everywhere.</p>
          <button @click="searchEverywhere" class="btn-secondary mt-4">Search everywhere</button>
        </div>

        <div v-else class="space-y-4">
          <JobCard v-for="job in store.jobs" :key="job.id" :job="job" />
        </div>

        <div v-if="store.pagination?.last_page > 1" class="mt-8 flex flex-wrap justify-center gap-2">
          <button
            v-for="page in store.pagination.last_page" :key="page"
            @click="goToPage(page)"
            :class="['px-3 py-1.5 rounded-lg text-sm font-medium border transition-colors',
              page === store.pagination.current_page
                ? 'bg-primary-600 text-white border-primary-600'
                : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50']"
          >{{ page }}</button>
        </div>
      </div>
    </div>

    <!-- Mobile filter drawer -->
    <div v-if="drawerOpen" class="lg:hidden fixed inset-0 z-40" role="dialog" aria-modal="true">
      <div class="absolute inset-0 bg-black/40" @click="drawerOpen = false"></div>
      <div class="absolute inset-x-0 bottom-0 max-h-[85vh] bg-white rounded-t-2xl flex flex-col">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
          <h2 class="font-semibold">Filters</h2>
          <button @click="drawerOpen = false" class="text-gray-400 text-xl leading-none" aria-label="Close filters">&times;</button>
        </div>
        <div class="p-5 overflow-y-auto">
          <JobFilters
            :filters="filters" :counts="counts"
            @apply="() => { doSearch(); drawerOpen = false }"
            @reset="resetFilters"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useJobsStore } from '@/stores/jobs'
import { discoveryApi } from '@/api/discovery'
import { useSeo } from '@/composables/useSeo'
import JobCard from '@/components/jobs/JobCard.vue'
import JobFilters from '@/components/jobs/JobFilters.vue'
import { SEARCH_MODES } from '@/lib/labels'

const route  = useRoute()
const router = useRouter()
const store  = useJobsStore()

const drawerOpen = ref(false)
const counts     = ref({})
const place      = ref(null)

const emptyFilters = () => ({
  q: '', category: '', location: '', latitude: null, longitude: null,
  mode: 'anywhere', radius: 25, work_arrangement: [], employment_type: [],
  experience_level: '', salary_min: null, visa_sponsorship: false,
  sort: 'recent', page: 1,
})

const filters = reactive(emptyFilters())

/** Routes like /jobs/remote and /jobs/in/denver-co are searches with a preset. */
const routeMode = computed(() => (SEARCH_MODES.some((m) => m.value === route.params.mode) ? route.params.mode : null))
const locationSlug = computed(() => route.params.location ?? null)

const heading = computed(() => {
  if (place.value) return `Jobs in ${place.value.label}`
  return {
    remote: 'Remote jobs',
    international: 'International opportunities',
    near_me: 'Jobs near you',
    nationwide: 'Nationwide jobs',
  }[filters.mode] ?? 'Browse jobs'
})

const subheading = computed(() => {
  if (place.value) return `Local, hybrid and remote roles open to candidates in ${place.value.label}.`
  return {
    remote: 'Roles performed from wherever the employer allows.',
    international: 'Roles open to candidates across countries.',
    near_me: 'Roles within reach of your location, plus remote roles open to it.',
    nationwide: 'Roles anywhere in the country you choose.',
  }[filters.mode] ?? 'On-site, hybrid and remote roles — local, national and international.'
})

useSeo(() => ({
  title: heading.value,
  description: subheading.value,
  canonical: route.path,
}))

const activeFilterCount = computed(() =>
  [filters.location, filters.experience_level, filters.salary_min, filters.category].filter(Boolean).length
  + filters.work_arrangement.length + filters.employment_type.length
  + (filters.visa_sponsorship ? 1 : 0)
  + (filters.mode !== 'anywhere' ? 1 : 0)
)

/** Only send filters the API cares about, and drop the empty ones. */
function queryParams() {
  const params = {
    q: filters.q || undefined,
    category: filters.category || undefined,
    mode: filters.mode !== 'anywhere' ? filters.mode : undefined,
    location: filters.location || undefined,
    latitude: filters.location ? filters.latitude ?? undefined : undefined,
    longitude: filters.location ? filters.longitude ?? undefined : undefined,
    radius: filters.mode === 'near_me' ? filters.radius : undefined,
    work_arrangement: filters.work_arrangement.length ? filters.work_arrangement.join(',') : undefined,
    employment_type: filters.employment_type.length ? filters.employment_type.join(',') : undefined,
    experience_level: filters.experience_level || undefined,
    salary_min: filters.salary_min || undefined,
    visa_sponsorship: filters.visa_sponsorship || undefined,
    sort: filters.sort !== 'recent' ? filters.sort : undefined,
    page: filters.page > 1 ? filters.page : undefined,
  }

  // A location page already says where it is; keep those out of the query.
  if (place.value) {
    params.city    = place.value.city ?? undefined
    params.state   = place.value.state ?? undefined
    params.country = place.value.country ?? undefined
    params.location = undefined
  }

  return Object.fromEntries(Object.entries(params).filter(([, v]) => v !== undefined && v !== ''))
}

function doSearch({ pushQuery = true } = {}) {
  const params = queryParams()
  if (pushQuery && !locationSlug.value) {
    router.replace({ query: params })
  }
  store.search(params)
}

function resetFilters() {
  Object.assign(filters, emptyFilters(), { mode: routeMode.value ?? 'anywhere' })
  filters.page = 1
  doSearch()
}

function searchEverywhere() {
  place.value = null
  Object.assign(filters, emptyFilters())
  router.push({ name: 'jobs' })
}

function goToPage(page) {
  filters.page = page
  doSearch()
  window.scrollTo({ top: 0 })
}

/** Rehydrate the form from the URL so search results stay shareable. */
function applyRouteState() {
  const q = route.query
  Object.assign(filters, emptyFilters(), {
    q: q.q ?? '',
    category: q.category ?? '',
    location: q.location ?? '',
    mode: routeMode.value ?? q.mode ?? 'anywhere',
    radius: Number(q.radius) || 25,
    work_arrangement: q.work_arrangement ? String(q.work_arrangement).split(',') : [],
    employment_type: q.employment_type ? String(q.employment_type).split(',') : [],
    experience_level: q.experience_level ?? '',
    salary_min: q.salary_min ? Number(q.salary_min) : null,
    visa_sponsorship: q.visa_sponsorship === 'true' || q.visa_sponsorship === true,
    sort: q.sort ?? 'recent',
    page: Number(q.page) || 1,
  })
}

async function load() {
  applyRouteState()
  place.value = null

  if (locationSlug.value) {
    try {
      const { data } = await discoveryApi.location(locationSlug.value)
      place.value = data.location
    } catch {
      // Unknown location slug: fall back to an unfiltered search rather than
      // showing a page that pretends the place exists.
      place.value = null
    }
  }

  doSearch({ pushQuery: false })
}

onMounted(async () => {
  await load()
  try {
    const { data } = await discoveryApi.filters()
    counts.value = data.arrangement_counts ?? {}
  } catch {
    counts.value = {}
  }
})

watch(() => route.fullPath, load)
</script>
