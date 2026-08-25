<template>
  <RouterLink
    :to="`/jobs/${job.slug}`"
    class="card p-5 flex gap-4 hover:shadow-md hover:border-primary-200 transition-all block"
  >
    <!-- Company logo -->
    <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0 overflow-hidden">
      <img v-if="job.employer?.logo" :src="`/storage/${job.employer.logo}`" :alt="job.employer.company_name" class="w-full h-full object-contain" />
      <span v-else class="text-lg font-bold text-gray-400">{{ job.employer?.company_name?.[0] }}</span>
    </div>

    <div class="flex-1 min-w-0">
      <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
          <h3 class="font-semibold text-gray-900 truncate">{{ job.title }}</h3>
          <p class="text-sm text-gray-500 truncate">{{ job.employer?.company_name }}</p>
        </div>
        <span v-if="job.is_featured" class="badge-blue flex-shrink-0">Featured</span>
      </div>

      <!-- Where it is, how it is worked, what kind of role -->
      <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-3 text-sm text-gray-600">
        <span class="inline-flex items-center gap-1.5 min-w-0">
          <span aria-hidden="true">{{ locationIcon }}</span>
          <span class="truncate">{{ job.location_label }}</span>
        </span>
        <span class="inline-flex items-center gap-1.5">
          <span aria-hidden="true">{{ arrangement.icon }}</span>{{ arrangement.label }}
        </span>
        <span class="inline-flex items-center gap-1.5">
          <span aria-hidden="true">💼</span>{{ employmentLabel }}
        </span>
      </div>

      <div class="flex flex-wrap gap-2 mt-3">
        <span class="badge-gray capitalize">{{ job.experience_level }}</span>
        <span v-if="showsInternationalBadge" class="badge-blue">Open internationally</span>
        <span v-if="job.visa_sponsorship" class="badge-green">Visa sponsorship</span>
        <span v-if="distance" class="badge-gray">{{ distance }}</span>
      </div>

      <div class="flex items-center justify-between mt-3">
        <p v-if="job.salary_visible && job.salary_min" class="text-sm font-medium text-gray-700">
          {{ salaryRange }}
          <span class="text-gray-400 font-normal">/ {{ job.salary_period }}</span>
        </p>
        <p v-else class="text-sm text-gray-400">Salary not disclosed</p>
        <p class="text-xs text-gray-400">{{ timeAgo(job.created_at) }}</p>
      </div>
    </div>
  </RouterLink>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { workArrangement, employmentType } from '@/lib/labels'

const props = defineProps({ job: { type: Object, required: true } })

const arrangement = computed(
  () => workArrangement[props.job.work_arrangement ?? props.job.location_type] ?? { label: '—', icon: '📍' }
)

// A placed job gets a pin; a remote one gets a globe.
const locationIcon = computed(() => (props.job.work_arrangement === 'remote' ? '🌎' : '📍'))

const employmentLabel = computed(
  () => employmentType[props.job.employment_type]?.label ?? props.job.employment_type
)

const showsInternationalBadge = computed(
  () => ['international', 'north_america', 'specific_countries'].includes(props.job.hiring_scope)
     && props.job.work_arrangement !== 'remote'
)

const distance = computed(() => {
  const miles = props.job.distance_miles
  return miles == null ? null : `${Math.round(miles)} mi away`
})

const salaryRange = computed(() => {
  const { salary_min: min, salary_max: max, currency } = props.job
  const symbol = currency && currency !== 'USD' ? `${currency} ` : '$'
  return `${symbol}${format(min)}${max ? ` – ${symbol}${format(max)}` : '+'}`
})

function format(v) {
  return v >= 1000 ? `${Math.round(v / 1000)}k` : v
}

function timeAgo(dateStr) {
  const diff = Math.floor((Date.now() - new Date(dateStr)) / 86400000)
  if (diff === 0) return 'Today'
  if (diff === 1) return 'Yesterday'
  if (diff < 30) return `${diff}d ago`
  return `${Math.floor(diff / 30)}mo ago`
}
</script>
