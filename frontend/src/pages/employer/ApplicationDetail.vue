<template>
  <div class="max-w-3xl space-y-6">
    <div class="flex items-center gap-3">
      <RouterLink to="/employer/applications" class="text-sm text-primary-600 hover:underline">← Applications</RouterLink>
    </div>

    <div v-if="loading" class="card p-8 text-center text-gray-400">Loading…</div>

    <template v-else-if="application">
      <!-- Applicant header -->
      <div class="card p-6 flex items-start gap-5">
        <div class="w-14 h-14 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold text-xl flex-shrink-0">
          {{ application.job_seeker?.user?.name?.[0] }}
        </div>
        <div class="flex-1">
          <h1 class="text-xl font-bold">{{ application.job_seeker?.user?.name }}</h1>
          <p class="text-gray-500 text-sm">{{ application.job_seeker?.headline }}</p>
          <p class="text-gray-400 text-xs mt-1">
            {{ application.job_seeker?.current_city }}, {{ application.job_seeker?.current_country }} ·
            {{ application.job_seeker?.experience_level }} level ·
            {{ application.job_seeker?.years_of_experience }}y exp
          </p>
          <div class="flex flex-wrap gap-1 mt-2">
            <span v-for="skill in application.job_seeker?.skills" :key="skill.id" class="badge-gray text-xs">
              {{ skill.name }}
            </span>
          </div>
        </div>
        <div class="flex flex-col items-end gap-2">
          <span :class="badgeClass(application.status)" class="capitalize">{{ application.status.replace('_', ' ') }}</span>
          <p class="text-xs text-gray-400">Applied {{ timeAgo(application.created_at) }}</p>
        </div>
      </div>

      <!-- AI lead qualification -->
      <QualificationCard
        :qualification="application.qualification"
        :busy="requalifying"
        @requalify="requalify"
      />

      <!-- Job info -->
      <div class="card p-5">
        <p class="text-sm text-gray-500">Applied for</p>
        <p class="font-semibold">{{ application.job?.title }}</p>
      </div>

      <!-- Cover letter -->
      <div v-if="application.cover_letter" class="card p-6">
        <h2 class="font-semibold mb-3">Cover Letter</h2>
        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ application.cover_letter }}</p>
      </div>

      <!-- Resume -->
      <div v-if="application.resume" class="card p-5 flex items-center justify-between">
        <span class="font-medium">Resume</span>
        <a :href="`/storage/${application.resume}`" target="_blank" class="btn-secondary text-sm">View / Download</a>
      </div>

      <!-- Links -->
      <div v-if="application.job_seeker?.linkedin_url || application.job_seeker?.github_url || application.job_seeker?.portfolio_url" class="card p-5 space-y-1">
        <h2 class="font-semibold mb-2">Links</h2>
        <a v-if="application.job_seeker.linkedin_url" :href="application.job_seeker.linkedin_url" target="_blank" class="block text-sm text-primary-600 hover:underline">LinkedIn</a>
        <a v-if="application.job_seeker.github_url" :href="application.job_seeker.github_url" target="_blank" class="block text-sm text-primary-600 hover:underline">GitHub</a>
        <a v-if="application.job_seeker.portfolio_url" :href="application.job_seeker.portfolio_url" target="_blank" class="block text-sm text-primary-600 hover:underline">Portfolio</a>
      </div>

      <!-- Status update -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold">Update Status</h2>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="s in statuses" :key="s"
            @click="setStatus(s)"
            :class="[application.status === s ? 'btn-primary' : 'btn-secondary', 'text-sm capitalize']"
            :disabled="updating"
          >
            {{ s.replace('_', ' ') }}
          </button>
        </div>
        <div>
          <label class="label">Notes (internal)</label>
          <textarea v-model="notes" rows="3" class="input" placeholder="Add notes visible only to you…"></textarea>
        </div>
        <div v-if="updateSuccess" class="text-green-600 text-sm">✓ Status updated.</div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { applicationsApi } from '@/api/applications'
import QualificationCard from '@/components/leads/QualificationCard.vue'

const route       = useRoute()
const loading     = ref(true)
const updating    = ref(false)
const updateSuccess = ref(false)
const requalifying  = ref(false)
const application = ref(null)
const notes       = ref('')

const statuses = ['viewed', 'shortlisted', 'interview_scheduled', 'offer_extended', 'hired', 'rejected']

function badgeClass(s) {
  return { submitted: 'badge-gray', viewed: 'badge-yellow', shortlisted: 'badge-blue', interview_scheduled: 'badge-blue', offer_extended: 'badge-green', hired: 'badge-green', rejected: 'badge-red' }[s] ?? 'badge-gray'
}

function timeAgo(d) {
  const diff = Math.floor((Date.now() - new Date(d)) / 86400000)
  return diff === 0 ? 'today' : diff === 1 ? 'yesterday' : `${diff} days ago`
}

async function setStatus(status) {
  updating.value    = true
  updateSuccess.value = false
  await applicationsApi.updateStatus(route.params.id, { status, notes: notes.value })
  application.value.status = status
  updating.value    = false
  updateSuccess.value = true
}

async function refresh() {
  const { data } = await applicationsApi.show(route.params.id)
  application.value = data.application
  return data.application.qualification
}

// Qualification runs on the queue. The previous verdict is already "completed",
// so poll on qualified_at moving rather than on the status — otherwise the very
// first refresh looks finished and the employer is shown the stale score.
async function requalify() {
  requalifying.value = true
  const before = application.value?.qualification?.qualified_at ?? null

  try {
    await applicationsApi.requalify(route.params.id)

    for (let attempt = 0; attempt < 10; attempt++) {
      await new Promise((resolve) => setTimeout(resolve, 1500))
      const qualification = await refresh()
      if (!qualification) continue
      if (qualification.status === 'failed') break
      if (qualification.qualified_at && qualification.qualified_at !== before) break
    }
  } finally {
    requalifying.value = false
  }
}

onMounted(async () => {
  await refresh()
  loading.value = false
})
</script>
