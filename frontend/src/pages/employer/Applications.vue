<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">Applications</h1>
      <div class="flex gap-2">
        <select v-model="filters.status" class="input text-sm w-40" @change="load">
          <option value="">All statuses</option>
          <option v-for="s in statuses" :key="s" :value="s" class="capitalize">{{ s }}</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="card p-4 h-20 animate-pulse bg-gray-100" />
    </div>

    <div v-else-if="applications.length === 0" class="card p-10 text-center text-gray-400">
      No applications yet.
    </div>

    <div v-else class="card divide-y divide-gray-100">
      <div
        v-for="app in applications" :key="app.id"
        class="p-4 flex items-center gap-4 hover:bg-gray-50 cursor-pointer"
        @click="router.push(`/employer/applications/${app.id}`)"
      >
        <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-semibold flex-shrink-0">
          {{ app.job_seeker?.user?.name?.[0] }}
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-medium text-sm flex items-center gap-1.5">
            {{ app.job_seeker?.user?.name }}
            <VerificationBadge
              subject="candidate"
              :identity-verified="!!app.job_seeker?.is_identity_verified"
              :badges="app.job_seeker?.verified_badges ?? []"
            />
          </p>
          <p class="text-xs text-gray-400">{{ app.job?.title }} · {{ app.job_seeker?.current_country }}</p>
        </div>
        <div class="flex items-center gap-3">
          <div class="hidden sm:flex gap-1 flex-wrap justify-end">
            <span v-for="skill in app.job_seeker?.skills?.slice(0,3)" :key="skill.id" class="badge-gray text-xs">{{ skill.name }}</span>
          </div>
          <span :class="badgeClass(app.status)" class="capitalize text-xs">{{ app.status.replace('_', ' ') }}</span>
          <p class="text-xs text-gray-400 whitespace-nowrap">{{ timeAgo(app.created_at) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { applicationsApi } from '@/api/applications'
import VerificationBadge from '@/components/verification/VerificationBadge.vue'

const router = useRouter()
const loading = ref(true)
const applications = ref([])
const filters = ref({ status: '' })
const statuses = ['submitted', 'viewed', 'shortlisted', 'interview_scheduled', 'offer_extended', 'hired', 'rejected']

function badgeClass(s) {
  return { submitted: 'badge-gray', viewed: 'badge-yellow', shortlisted: 'badge-blue', hired: 'badge-green', rejected: 'badge-red' }[s] ?? 'badge-gray'
}
function timeAgo(d) {
  const diff = Math.floor((Date.now() - new Date(d)) / 86400000)
  return diff === 0 ? 'Today' : diff === 1 ? 'Yesterday' : `${diff}d ago`
}

async function load() {
  loading.value = true
  const { data } = await applicationsApi.list(filters.value)
  applications.value = data.data
  loading.value = false
}

onMounted(load)
</script>
