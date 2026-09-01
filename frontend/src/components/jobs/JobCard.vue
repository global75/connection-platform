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
        <div>
          <h3 class="font-semibold text-gray-900 truncate">{{ job.title }}</h3>
          <p class="text-sm text-gray-500 flex items-center gap-1.5">
            {{ job.employer?.company_name }}
            <VerificationBadge subject="employer" :verified="!!job.employer?.is_verified" />
          </p>
        </div>
        <span v-if="job.is_featured" class="badge-blue flex-shrink-0">Featured</span>
      </div>

      <div class="flex flex-wrap gap-2 mt-3">
        <span class="badge-gray">{{ locationLabel }}</span>
        <span class="badge-gray">{{ employmentLabel }}</span>
        <span class="badge-gray capitalize">{{ job.experience_level }}</span>
        <span v-if="job.visa_sponsorship" class="badge-green">Visa Sponsorship</span>
      </div>

      <div class="flex items-center justify-between mt-3">
        <p v-if="job.salary_visible && job.salary_min" class="text-sm font-medium text-gray-700">
          ${{ formatSalary(job.salary_min) }}{{ job.salary_max ? ` – $${formatSalary(job.salary_max)}` : '+' }}
          <span class="text-gray-400 font-normal">/ {{ job.salary_period }}</span>
        </p>
        <p v-else class="text-sm text-gray-400">Salary not disclosed</p>
        <p class="text-xs text-gray-400">{{ timeAgo(job.created_at) }}</p>
      </div>
    </div>
  </RouterLink>
</template>

<script setup>
import VerificationBadge from '@/components/verification/VerificationBadge.vue'
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps({ job: { type: Object, required: true } })

const locationMap   = { remote: 'Remote', hybrid: 'Hybrid', on_site: 'On-site' }
const employmentMap = { full_time: 'Full-time', part_time: 'Part-time', contract: 'Contract', freelance: 'Freelance', internship: 'Internship' }

const locationLabel   = computed(() => locationMap[props.job.location_type] ?? props.job.location_type)
const employmentLabel = computed(() => employmentMap[props.job.employment_type] ?? props.job.employment_type)

function formatSalary(v) {
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
