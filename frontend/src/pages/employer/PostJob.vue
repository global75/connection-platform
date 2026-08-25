<template>
  <div class="max-w-3xl">
    <h1 class="text-2xl font-bold mb-6">{{ isEdit ? 'Edit Job' : 'Post a New Job' }}</h1>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- 1. What are you hiring for? -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold text-gray-700 border-b pb-2">What are you hiring for?</h2>

        <div>
          <label class="label">Job title *</label>
          <input v-model="form.title" class="input" placeholder="Marketing Coordinator" required />
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Category *</label>
            <input v-model="form.category" class="input" placeholder="Marketing" required />
          </div>
          <div>
            <label class="label">Experience level *</label>
            <select v-model="form.experience_level" class="input" required>
              <option v-for="level in EXPERIENCE_LEVELS" :key="level.value" :value="level.value">{{ level.label }}</option>
            </select>
          </div>
        </div>

        <div>
          <label class="label">Employment type *</label>
          <select v-model="form.employment_type" class="input" required>
            <option v-for="type in EMPLOYMENT_TYPES" :key="type.value" :value="type.value">{{ type.label }}</option>
          </select>
        </div>
      </div>

      <!-- 2. How will the work happen? -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold text-gray-700 border-b pb-2">How will the employee work?</h2>

        <div class="grid sm:grid-cols-3 gap-3">
          <button
            v-for="option in WORK_ARRANGEMENTS" :key="option.value"
            type="button"
            @click="form.work_arrangement = option.value"
            :class="['card p-4 text-left transition-all',
              form.work_arrangement === option.value
                ? 'border-primary-500 ring-1 ring-primary-500'
                : 'hover:border-primary-300']"
          >
            <span class="text-xl">{{ option.icon }}</span>
            <p class="font-medium mt-1">{{ option.label }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ option.hint }}</p>
          </button>
        </div>
      </div>

      <!-- 3. Where is the job? -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold text-gray-700 border-b pb-2">Where is the job?</h2>
        <p class="text-sm text-gray-500">
          {{ needsAddress
            ? 'On-site and hybrid roles need a city so local candidates can find them.'
            : 'A fully remote role only needs the country it is based in.' }}
        </p>

        <div class="grid sm:grid-cols-3 gap-4">
          <div>
            <label class="label">City {{ needsAddress ? '*' : '' }}</label>
            <input v-model="form.location_city" class="input" placeholder="Denver" :required="needsAddress" />
          </div>
          <div>
            <label class="label">State / province</label>
            <input v-model="form.location_state" class="input" placeholder="CO" />
          </div>
          <div>
            <label class="label">Country *</label>
            <select v-model="form.location_country" class="input" required>
              <option v-for="country in countries" :key="country.code" :value="country.code">{{ country.name }}</option>
            </select>
          </div>
        </div>

        <div v-if="needsAddress">
          <label class="label">Postal code</label>
          <input v-model="form.location_postal_code" class="input w-40" placeholder="80202" />
        </div>
      </div>

      <!-- 4. Who can apply? -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold text-gray-700 border-b pb-2">Who can apply?</h2>
        <p class="text-sm text-gray-500">This is separate from where the job is and how it is worked.</p>

        <div class="space-y-2">
          <label
            v-for="scope in HIRING_SCOPES" :key="scope.value"
            class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
            :class="form.hiring_scope === scope.value ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:bg-gray-50'"
          >
            <input type="radio" class="mt-1 text-primary-600" :value="scope.value" v-model="form.hiring_scope" />
            <span>
              <span class="font-medium text-sm">{{ scope.label }}</span>
              <span class="block text-xs text-gray-500">{{ scope.hint }}</span>
            </span>
          </label>
        </div>

        <div v-if="form.hiring_scope === 'specific_countries'">
          <label class="label">Countries candidates may apply from *</label>
          <select v-model="form.eligible_countries" multiple class="input h-40">
            <option v-for="country in countries" :key="country.code" :value="country.code">{{ country.name }}</option>
          </select>
          <p class="text-xs text-gray-400 mt-1">Hold ⌘ / Ctrl to select several.</p>
        </div>

        <div v-if="form.hiring_scope === 'local'">
          <label class="label">How far will you consider candidates?</label>
          <select v-model.number="form.local_radius_miles" class="input w-48">
            <option v-for="miles in RADIUS_OPTIONS" :key="miles" :value="miles">Within {{ miles }} miles</option>
          </select>
        </div>

        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.visa_sponsorship" type="checkbox" class="rounded text-primary-600" />
          We offer visa sponsorship
        </label>
      </div>

      <!-- Compensation -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold text-gray-700 border-b pb-2">Compensation</h2>
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="label">Min salary</label>
            <input v-model.number="form.salary_min" type="number" class="input" placeholder="60000" />
          </div>
          <div>
            <label class="label">Max salary</label>
            <input v-model.number="form.salary_max" type="number" class="input" placeholder="100000" />
          </div>
          <div>
            <label class="label">Period</label>
            <select v-model="form.salary_period" class="input">
              <option value="annual">Annual</option>
              <option value="monthly">Monthly</option>
              <option value="hourly">Hourly</option>
            </select>
          </div>
        </div>
        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.salary_visible" type="checkbox" class="rounded text-primary-600" />
          Show salary to applicants
        </label>
      </div>

      <!-- Description -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold text-gray-700 border-b pb-2">Job Details</h2>
        <div>
          <label class="label">Description *</label>
          <textarea v-model="form.description" rows="8" class="input" placeholder="Describe the role, responsibilities…" required minlength="100"></textarea>
        </div>
        <div>
          <label class="label">Requirements</label>
          <textarea v-model="form.requirements" rows="5" class="input" placeholder="List skills, qualifications…"></textarea>
        </div>
        <div>
          <label class="label">Benefits</label>
          <textarea v-model="form.benefits" rows="4" class="input" placeholder="Health insurance, equity…"></textarea>
        </div>
        <div>
          <label class="label">Expires at</label>
          <input v-model="form.expires_at" type="date" class="input" />
        </div>
      </div>

      <!-- How this job will read to candidates -->
      <div class="card p-5 bg-gray-50">
        <p class="text-xs uppercase tracking-wide text-gray-400 mb-2">Preview</p>
        <p class="font-semibold">{{ form.title || 'Job title' }}</p>
        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-sm text-gray-600">
          <span>{{ needsAddress ? '📍' : '🌎' }} {{ previewLocation }}</span>
          <span>{{ arrangementLabel.icon }} {{ arrangementLabel.label }}</span>
          <span>💼 {{ employmentLabel }}</span>
          <span>👥 {{ scopeLabel }}</span>
        </div>
      </div>

      <div v-if="errors" class="text-red-600 text-sm space-y-1">
        <p v-for="(msgs, field) in errors" :key="field">{{ msgs[0] }}</p>
      </div>

      <div class="flex flex-wrap gap-3">
        <button type="submit" class="btn-primary" :disabled="loading">
          {{ loading ? 'Saving…' : isEdit ? 'Update Job' : 'Publish Job' }}
        </button>
        <button type="button" @click="saveDraft" class="btn-secondary" :disabled="loading">Save as Draft</button>
        <RouterLink to="/employer/jobs" class="btn-secondary">Cancel</RouterLink>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { jobsApi } from '@/api/jobs'
import { discoveryApi } from '@/api/discovery'
import { authApi } from '@/api/auth'
import {
  WORK_ARRANGEMENTS, HIRING_SCOPES, EMPLOYMENT_TYPES, EXPERIENCE_LEVELS, RADIUS_OPTIONS,
  workArrangement, employmentType, hiringScope,
} from '@/lib/labels'

const route  = useRoute()
const router = useRouter()

const isEdit    = computed(() => !!route.params.id)
const loading   = ref(false)
const errors    = ref(null)
const countries = ref([{ code: 'US', name: 'United States' }])

const form = ref({
  title: '', category: '', experience_level: 'mid', employment_type: 'full_time',
  work_arrangement: 'on_site',
  location_city: '', location_state: '', location_country: 'US', location_postal_code: '',
  hiring_scope: 'national', eligible_countries: [], local_radius_miles: 50,
  salary_min: null, salary_max: null, salary_period: 'annual', salary_visible: true,
  visa_sponsorship: false,
  description: '', requirements: '', benefits: '', expires_at: '', status: 'active',
})

const needsAddress = computed(() => ['on_site', 'hybrid'].includes(form.value.work_arrangement))
const arrangementLabel = computed(() => workArrangement[form.value.work_arrangement] ?? { label: '—', icon: '' })
const employmentLabel  = computed(() => employmentType[form.value.employment_type]?.label ?? '')
const scopeLabel       = computed(() => hiringScope[form.value.hiring_scope]?.label ?? '')

const previewLocation = computed(() => {
  const { location_city: city, location_state: state, location_country: country } = form.value
  const place = [city, state].filter(Boolean).join(', ')
  const countryName = countries.value.find((c) => c.code === country)?.name ?? country
  return place || countryName || 'Location'
})

// Switching to fully remote should not leave a stale hiring radius behind.
watch(needsAddress, (needs) => {
  if (!needs && form.value.hiring_scope === 'local') {
    form.value.hiring_scope = 'national'
  }
})

async function submit() { await save('active') }
async function saveDraft() { await save('draft') }

async function save(status) {
  loading.value = true
  errors.value  = null
  try {
    form.value.status = status
    if (isEdit.value) {
      await jobsApi.updateJob(route.params.id, form.value)
    } else {
      await jobsApi.createJob(form.value)
    }
    router.push('/employer/jobs')
  } catch (err) {
    errors.value = err.response?.data?.errors ?? null
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  const [filtersRes] = await Promise.allSettled([discoveryApi.filters()])
  if (filtersRes.status === 'fulfilled') countries.value = filtersRes.value.data.countries

  if (isEdit.value) {
    const { data } = await jobsApi.getJob(route.params.id)
    Object.assign(form.value, data.job, {
      eligible_countries: data.job.eligible_countries ?? [],
      expires_at: data.job.expires_at?.slice(0, 10) ?? '',
    })
    return
  }

  // A new job starts where the company is, and how it says it hires.
  try {
    const { data } = await authApi.me()
    const employer = data.user?.employer_profile
    if (employer) {
      form.value.location_city    = employer.headquarters_city ?? ''
      form.value.location_state   = employer.headquarters_state ?? ''
      form.value.location_country = employer.headquarters_country ?? 'US'
      const scopes = employer.hiring_scopes ?? []
      if (scopes.includes('international')) form.value.hiring_scope = 'international'
      else if (scopes.includes('local') && !scopes.includes('national')) form.value.hiring_scope = 'local'
    }
  } catch {
    // Defaults are fine if the profile cannot be loaded.
  }
})
</script>
