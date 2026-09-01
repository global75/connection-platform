<template>
  <div class="card p-6 space-y-6">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h2 class="font-semibold">Verification</h2>
        <p class="text-xs text-gray-400 mt-0.5">
          {{ isEmployer
            ? 'Verified companies rank higher and can contact candidates directly.'
            : 'Verified candidates stand out to employers hiring internationally.' }}
        </p>
      </div>
      <span v-if="verified" class="badge-green">✓ Verified</span>
      <span v-else class="badge-gray">Unverified</span>
    </div>

    <p v-if="loading" class="text-sm text-gray-400">Loading verification status…</p>

    <template v-else>
      <div v-if="error" class="text-sm text-red-600">{{ error }}</div>

      <!-- Nothing this deployment can run -->
      <p v-if="!state?.available_types?.length" class="text-sm text-gray-500">
        No verification providers are configured on this deployment yet.
      </p>

      <!-- Employer: DNS domain proof -->
      <section v-if="isEmployer && canRun('work_email_domain')" class="space-y-3">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Domain ownership</h3>
        <p class="text-sm text-gray-600">
          Publish this TXT record on <strong>{{ state.dns_instructions.host || 'your company domain' }}</strong>,
          then run the check. DNS changes can take a few minutes to propagate.
        </p>
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 overflow-x-auto">
          <code class="text-xs text-gray-700 whitespace-nowrap">
            {{ state.dns_instructions.host }} &nbsp; TXT &nbsp; "{{ state.dns_instructions.value }}"
          </code>
        </div>
        <button class="btn-primary text-sm" :disabled="busy" @click="run('work_email_domain')">
          {{ busy === 'work_email_domain' ? 'Checking…' : 'Check DNS record' }}
        </button>
      </section>

      <!-- Employer: business registry -->
      <section v-if="isEmployer && canRun('company_registry')" class="space-y-3">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Business registration</h3>
        <div>
          <label class="label">Registration number</label>
          <input v-model="registrationNumber" class="input" placeholder="e.g. 12345678" />
        </div>
        <button class="btn-secondary text-sm" :disabled="busy || !registrationNumber" @click="run('company_registry')">
          {{ busy === 'company_registry' ? 'Checking…' : 'Verify registration' }}
        </button>
      </section>

      <!-- Candidate: GitHub -->
      <section v-if="!isEmployer && canRun('github_oauth')" class="space-y-3">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Developer profile</h3>
        <p class="text-sm text-gray-600">
          Link your GitHub account to confirm your development history.
        </p>
        <a :href="githubAuthUrl" class="btn-secondary text-sm inline-flex">Link GitHub</a>
      </section>

      <!-- Candidate: identity -->
      <section v-if="!isEmployer && canRun('government_id')" class="space-y-3">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Identity</h3>
        <button class="btn-secondary text-sm" :disabled="busy" @click="run('government_id')">
          {{ busy === 'government_id' ? 'Starting…' : 'Start identity check' }}
        </button>
      </section>

      <!-- Existing records -->
      <section v-if="state?.records?.length" class="space-y-2 border-t border-gray-100 pt-5">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Status</h3>
        <div v-for="record in state.records" :key="record.type" class="flex items-start justify-between gap-3 text-sm">
          <div class="min-w-0">
            <p class="font-medium">{{ label(record.type) }}</p>
            <p v-if="record.rejection_reason" class="text-xs text-red-600 mt-0.5">{{ record.rejection_reason }}</p>
            <p v-else-if="record.metadata?.awaiting === 'dns_txt_record'" class="text-xs text-gray-400 mt-0.5">
              Waiting for the TXT record to appear in DNS.
            </p>
            <p v-else-if="record.expires_at" class="text-xs text-gray-400 mt-0.5">
              Expires {{ new Date(record.expires_at).toLocaleDateString() }}
            </p>
          </div>
          <span :class="statusClass(record.status)" class="capitalize flex-shrink-0">{{ record.status }}</span>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { verificationApi } from '@/api/verification'

const props = defineProps({
  subject: { type: String, default: 'employer' }, // 'employer' | 'candidate'
})

const TYPE_LABELS = {
  work_email_domain: 'Domain ownership',
  company_registry:  'Business registration',
  government_id:     'Government ID',
  github_oauth:      'GitHub account',
  linkedin_oauth:    'LinkedIn account',
  skill_badge:       'Skill badge',
}

const loading = ref(true)
const busy = ref(null)
const error = ref('')
const state = ref(null)
const registrationNumber = ref('')

const isEmployer = computed(() => props.subject === 'employer')
const verified = computed(() =>
  isEmployer.value ? !!state.value?.is_verified : !!state.value?.is_identity_verified,
)

// GitHub sends the user back with ?code=…, which the portal posts on mount.
const githubAuthUrl = computed(() => {
  const redirect = `${window.location.origin}${window.location.pathname}`
  return `https://github.com/login/oauth/authorize?scope=read:user&redirect_uri=${encodeURIComponent(redirect)}`
})

function canRun(type) {
  return (state.value?.available_types ?? []).includes(type)
}
function label(type) {
  return TYPE_LABELS[type] ?? type
}
function statusClass(status) {
  return {
    approved: 'badge-green',
    pending: 'badge-yellow',
    processing: 'badge-yellow',
    rejected: 'badge-red',
    expired: 'badge-gray',
  }[status] ?? 'badge-gray'
}

async function load() {
  loading.value = true
  try {
    const { data } = isEmployer.value
      ? await verificationApi.employerStatus()
      : await verificationApi.candidateStatus()
    state.value = data.verification
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Could not load verification status.'
  } finally {
    loading.value = false
  }
}

async function run(type, extra = {}) {
  busy.value = type
  error.value = ''
  try {
    const payload = { type, ...extra }
    if (type === 'company_registry') payload.business_registration_number = registrationNumber.value

    isEmployer.value
      ? await verificationApi.employerVerify(payload)
      : await verificationApi.candidateVerify(payload)

    await load()
  } catch (e) {
    // 503 means the provider isn't configured — say so rather than "failed".
    error.value = e.response?.data?.message ?? 'The verification check could not be completed.'
  } finally {
    busy.value = null
  }
}

onMounted(async () => {
  await load()

  const code = new URLSearchParams(window.location.search).get('code')
  if (code && !isEmployer.value && canRun('github_oauth')) {
    await run('github_oauth', { code })
    window.history.replaceState({}, '', window.location.pathname)
  }
})
</script>
