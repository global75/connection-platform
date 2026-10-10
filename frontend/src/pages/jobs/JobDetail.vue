<template>
  <div v-if="store.loading" class="max-w-4xl mx-auto px-4 py-10 animate-pulse space-y-4">
    <div class="h-8 bg-gray-200 rounded w-3/4"></div>
    <div class="h-4 bg-gray-200 rounded w-1/2"></div>
  </div>

  <div v-else-if="job" class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex flex-col lg:flex-row gap-6">
      <!-- Job content -->
      <div class="flex-1 space-y-6">
        <!-- Header -->
        <div class="card p-6">
          <div class="flex items-start gap-4">
            <div class="w-16 h-16 rounded-xl bg-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
              <img v-if="job.employer?.logo" :src="`/storage/${job.employer.logo}`" class="w-full h-full object-contain" />
              <span v-else class="text-xl font-bold text-gray-400">{{ job.employer?.company_name?.[0] }}</span>
            </div>
            <div class="flex-1">
              <h1 class="text-2xl font-bold text-gray-900">{{ job.title }}</h1>
              <p class="text-gray-600 mt-0.5">{{ job.employer?.company_name }}</p>
              <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-3 text-sm text-gray-600">
                <span class="inline-flex items-center gap-1.5">
                  <span aria-hidden="true">{{ job.work_arrangement === 'remote' ? '🌎' : '📍' }}</span>{{ job.location_label }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                  <span aria-hidden="true">{{ arrangement.icon }}</span>{{ arrangement.label }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                  <span aria-hidden="true">💼</span>{{ employmentMap[job.employment_type]?.label ?? job.employment_type }}
                </span>
              </div>
              <div class="flex flex-wrap gap-2 mt-3">
                <span class="badge-gray capitalize">{{ job.experience_level }}</span>
                <span class="badge-blue">Open to: {{ job.hiring_scope_label }}</span>
                <span v-if="job.visa_sponsorship" class="badge-green">Visa Sponsorship</span>
              </div>
            </div>
          </div>

          <div class="border-t border-gray-100 mt-4 pt-4 flex flex-wrap gap-6 text-sm">
            <div v-if="job.salary_visible && job.salary_min">
              <p class="text-gray-400">Salary</p>
              <p class="font-semibold">${{ job.salary_min.toLocaleString() }}{{ job.salary_max ? ` – $${job.salary_max.toLocaleString()}` : '+' }} / {{ job.salary_period }}</p>
            </div>
            <div>
              <p class="text-gray-400">Location</p>
              <p class="font-semibold">{{ job.location_label }}</p>
            </div>
            <div>
              <p class="text-gray-400">Work arrangement</p>
              <p class="font-semibold">{{ arrangement.label }}</p>
            </div>
            <div>
              <p class="text-gray-400">Who can apply</p>
              <p class="font-semibold">{{ job.hiring_scope_label }}</p>
            </div>
          </div>

          <!-- Apply -->
          <div class="mt-5 flex gap-3">
            <button v-if="auth.isJobSeeker" @click="showApplyModal = true" class="btn-primary px-8">
              Apply Now
            </button>
            <RouterLink v-else-if="!auth.isAuthenticated" to="/register?role=job_seeker" class="btn-primary px-8">
              Sign up to Apply
            </RouterLink>
            <button v-if="auth.isJobSeeker" @click="jobsStore.toggleSave(job.id)" class="btn-secondary">
              {{ isSaved ? '✓ Saved' : 'Save Job' }}
            </button>
          </div>
        </div>

        <!-- Description -->
        <div class="card p-6 prose prose-gray max-w-none">
          <h2 class="text-lg font-semibold mb-3">About this role</h2>
          <div class="whitespace-pre-line text-gray-700">{{ job.description }}</div>
        </div>

        <div v-if="job.requirements" class="card p-6">
          <h2 class="text-lg font-semibold mb-3">Requirements</h2>
          <div class="whitespace-pre-line text-gray-700">{{ job.requirements }}</div>
        </div>

        <div v-if="job.benefits" class="card p-6">
          <h2 class="text-lg font-semibold mb-3">Benefits</h2>
          <div class="whitespace-pre-line text-gray-700">{{ job.benefits }}</div>
        </div>
      </div>

      <!-- Sidebar: company info -->
      <aside class="w-full lg:w-72 space-y-4">
        <div class="card p-5">
          <h3 class="font-semibold mb-3">About {{ job.employer?.company_name }}</h3>
          <p class="text-sm text-gray-600 line-clamp-4">{{ job.employer?.description }}</p>
          <dl class="mt-4 space-y-2 text-sm">
            <div v-if="job.employer?.company_size" class="flex justify-between">
              <dt class="text-gray-400">Company size</dt>
              <dd class="font-medium">{{ job.employer.company_size }} employees</dd>
            </div>
            <div v-if="job.employer?.founded_year" class="flex justify-between">
              <dt class="text-gray-400">Founded</dt>
              <dd class="font-medium">{{ job.employer.founded_year }}</dd>
            </div>
            <div v-if="job.employer?.headquarters_city" class="flex justify-between">
              <dt class="text-gray-400">HQ</dt>
              <dd class="font-medium">{{ job.employer.headquarters_city }}, {{ job.employer.headquarters_state }}</dd>
            </div>
          </dl>
          <a v-if="job.employer?.website" :href="job.employer.website" target="_blank"
            class="mt-4 block text-center text-sm text-primary-600 hover:underline">
            Visit website →
          </a>
        </div>

        <!-- Required skills -->
        <div v-if="job.skills?.length" class="card p-5">
          <h3 class="font-semibold mb-3">Skills</h3>
          <div class="flex flex-wrap gap-2">
            <span v-for="skill in job.skills" :key="skill.id"
              :class="['badge', skill.pivot?.is_required ? 'badge-blue' : 'badge-gray']">
              {{ skill.name }}
            </span>
          </div>
        </div>
      </aside>
    </div>
  </div>

  <!-- Apply modal -->
  <ApplyModal v-if="showApplyModal && job" :job="job" @close="showApplyModal = false" @applied="showApplyModal = false" />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useJobsStore } from '@/stores/jobs'
import { useAuthStore } from '@/stores/auth'
import ApplyModal from '@/components/jobs/ApplyModal.vue'
import { useSeo } from '@/composables/useSeo'
import { workArrangement, employmentType as employmentMap } from '@/lib/labels'

const route      = useRoute()
const store      = useJobsStore()
const auth       = useAuthStore()
const jobsStore  = useJobsStore()

const showApplyModal = ref(false)
const job   = computed(() => store.currentJob)
const isSaved = computed(() => store.savedIds.has(job.value?.id))

const arrangement = computed(
  () => workArrangement[job.value?.work_arrangement] ?? { label: '—', icon: '📍' }
)

useSeo(() => ({
  title: job.value ? `${job.value.title} — ${job.value.employer?.company_name}` : null,
  description: job.value
    ? `${job.value.title} at ${job.value.employer?.company_name}. ${job.value.location_label}, ${arrangement.value.label}.`
    : null,
  canonical: `/jobs/${route.params.slug}`,
}))

onMounted(() => store.fetchJob(route.params.slug))
</script>
