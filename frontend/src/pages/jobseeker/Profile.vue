<template>
  <div class="max-w-3xl space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">My Profile</h1>
      <div v-if="profile" class="flex items-center gap-2">
        <div class="w-32 bg-gray-200 rounded-full h-2">
          <div class="bg-primary-600 h-2 rounded-full" :style="`width:${profile.completion}%`"></div>
        </div>
        <span class="text-sm font-medium text-primary-700">{{ profile.completion }}% complete</span>
      </div>
    </div>

    <form @submit.prevent="save" class="space-y-5">
      <!-- Basic -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold border-b pb-2">Basic Info</h2>
        <div><label class="label">Headline</label>
          <input v-model="form.headline" class="input" placeholder="Senior Laravel Developer" /></div>
        <div><label class="label">Bio</label>
          <textarea v-model="form.bio" rows="4" class="input" placeholder="Tell employers about yourself…"></textarea></div>
        <div class="grid sm:grid-cols-3 gap-4">
          <div><label class="label">City</label><input v-model="form.current_city" class="input" placeholder="Denver" /></div>
          <div><label class="label">State / province</label><input v-model="form.current_state" class="input" placeholder="CO" /></div>
          <div>
            <label class="label">Country</label>
            <select v-model="form.current_country" class="input">
              <option value="">Select a country</option>
              <option v-for="country in countries" :key="country.code" :value="country.code">{{ country.name }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Where and how they want to work: two separate questions -->
      <div class="card p-6 space-y-5">
        <h2 class="font-semibold border-b pb-2">Work preferences</h2>

        <fieldset>
          <legend class="label">How do you want to work?</legend>
          <div class="grid sm:grid-cols-3 gap-3">
            <label
              v-for="option in WORK_ARRANGEMENTS" :key="option.value"
              class="flex items-start gap-2 p-3 rounded-lg border cursor-pointer transition-colors"
              :class="form.work_arrangements.includes(option.value) ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:bg-gray-50'"
            >
              <input type="checkbox" class="mt-1 rounded text-primary-600" :value="option.value" v-model="form.work_arrangements" />
              <span>
                <span class="text-sm font-medium">{{ option.icon }} {{ option.label }}</span>
                <span class="block text-xs text-gray-500">{{ option.hint }}</span>
              </span>
            </label>
          </div>
        </fieldset>

        <fieldset>
          <legend class="label">Where are you willing to work?</legend>
          <div class="grid sm:grid-cols-3 gap-3">
            <label
              v-for="scope in LOCATION_SCOPES" :key="scope.value"
              class="flex items-start gap-2 p-3 rounded-lg border cursor-pointer transition-colors"
              :class="form.location_scopes.includes(scope.value) ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:bg-gray-50'"
            >
              <input type="checkbox" class="mt-1 rounded text-primary-600" :value="scope.value" v-model="form.location_scopes" />
              <span>
                <span class="text-sm font-medium">{{ scope.label }}</span>
                <span class="block text-xs text-gray-500">{{ scope.hint }}</span>
              </span>
            </label>
          </div>
        </fieldset>

        <div v-if="form.location_scopes.includes('near_me')">
          <label class="label">How far will you travel?</label>
          <select v-model.number="form.max_commute_miles" class="input w-48">
            <option v-for="miles in RADIUS_OPTIONS" :key="miles" :value="miles">Within {{ miles }} miles</option>
          </select>
        </div>

        <fieldset>
          <legend class="label">Job types you want</legend>
          <div class="flex flex-wrap gap-3">
            <label v-for="type in EMPLOYMENT_TYPES" :key="type.value" class="flex items-center gap-2 text-sm">
              <input type="checkbox" class="rounded text-primary-600" :value="type.value" v-model="form.employment_types" />
              {{ type.label }}
            </label>
          </div>
        </fieldset>
      </div>

      <!-- Experience -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold border-b pb-2">Experience</h2>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="label">Current title</label><input v-model="form.current_job_title" class="input" /></div>
          <div><label class="label">Desired title</label><input v-model="form.desired_job_title" class="input" /></div>
          <div>
            <label class="label">Experience level</label>
            <select v-model="form.experience_level" class="input">
              <option value="entry">Entry</option>
              <option value="mid">Mid</option>
              <option value="senior">Senior</option>
              <option value="lead">Lead</option>
              <option value="executive">Executive</option>
            </select>
          </div>
          <div><label class="label">Years of experience</label>
            <input v-model.number="form.years_of_experience" type="number" min="0" class="input" /></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="label">Desired salary min (USD)</label>
            <input v-model.number="form.desired_salary_min" type="number" class="input" /></div>
          <div><label class="label">Desired salary max (USD)</label>
            <input v-model.number="form.desired_salary_max" type="number" class="input" /></div>
        </div>
        <div>
          <label class="label">Availability</label>
          <select v-model="form.availability" class="input">
            <option value="immediately">Immediately</option>
            <option value="two_weeks">2 weeks notice</option>
            <option value="one_month">1 month notice</option>
            <option value="negotiable">Negotiable</option>
          </select>
        </div>
        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.willing_to_relocate" type="checkbox" class="rounded text-primary-600" /> Willing to relocate
        </label>
      </div>

      <!-- Links -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold border-b pb-2">Links</h2>
        <div><label class="label">LinkedIn</label><input v-model="form.linkedin_url" type="url" class="input" /></div>
        <div><label class="label">GitHub</label><input v-model="form.github_url" type="url" class="input" /></div>
        <div><label class="label">Portfolio</label><input v-model="form.portfolio_url" type="url" class="input" /></div>
      </div>

      <!-- Resume -->
      <div class="card p-6 space-y-3">
        <h2 class="font-semibold border-b pb-2">Resume</h2>
        <p v-if="profile?.resume" class="text-sm text-gray-600">Current resume: <a :href="`/storage/${profile.resume}`" target="_blank" class="text-primary-600 hover:underline">View</a></p>
        <div><label class="label">Upload new resume (PDF/DOCX, max 5MB)</label>
          <input @change="onResume" type="file" accept=".pdf,.doc,.docx" class="input" /></div>
      </div>

      <div v-if="success" class="text-green-600 text-sm">✓ Profile updated successfully.</div>
      <div v-if="errors" class="text-red-600 text-sm space-y-1">
        <p v-for="(msgs, f) in errors" :key="f">{{ msgs[0] }}</p>
      </div>

      <button type="submit" class="btn-primary px-8" :disabled="saving">
        {{ saving ? 'Saving…' : 'Save Profile' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import client from '@/api/client'
import { discoveryApi } from '@/api/discovery'
import { WORK_ARRANGEMENTS, LOCATION_SCOPES, EMPLOYMENT_TYPES, RADIUS_OPTIONS } from '@/lib/labels'

const countries = ref([])
const profile = ref(null)
const saving  = ref(false)
const success = ref(false)
const errors  = ref(null)
const resume  = ref(null)

const form = ref({
  headline: '', bio: '', current_city: '', current_state: '', current_country: '', nationality: '',
  current_job_title: '', desired_job_title: '', experience_level: 'mid',
  years_of_experience: 0, desired_salary_min: null, desired_salary_max: null,
  availability: 'negotiable', willing_to_relocate: false,
  work_arrangements: [], location_scopes: [], max_commute_miles: 25, employment_types: [],
  linkedin_url: '', github_url: '', portfolio_url: '',
})

function onResume(e) { resume.value = e.target.files[0] }

async function save() {
  saving.value  = true
  success.value = false
  errors.value  = null
  try {
    const fd = new FormData()
    Object.entries(form.value).forEach(([key, value]) => {
      if (value === null || value === undefined || value === '') return
      // Arrays go one entry per item, booleans as 1/0 — both are what the API
      // validates against.
      if (Array.isArray(value)) {
        value.forEach((item) => fd.append(`${key}[]`, item))
      } else if (typeof value === 'boolean') {
        fd.append(key, value ? '1' : '0')
      } else {
        fd.append(key, value)
      }
    })
    if (resume.value) fd.append('resume', resume.value)
    fd.append('_method', 'PUT')
    const { data } = await client.post('/job-seeker/profile', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    profile.value = data.profile
    success.value = true
  } catch (err) {
    errors.value = err.response?.data?.errors ?? null
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  const [profileRes, filtersRes] = await Promise.allSettled([
    client.get('/job-seeker/profile'),
    discoveryApi.filters(),
  ])

  if (filtersRes.status === 'fulfilled') countries.value = filtersRes.value.data.countries

  if (profileRes.status === 'fulfilled') {
    profile.value = profileRes.value.data.profile
    Object.assign(form.value, profile.value, {
      work_arrangements: profile.value.work_arrangements ?? [],
      location_scopes: profile.value.location_scopes ?? [],
      employment_types: profile.value.employment_types ?? [],
      max_commute_miles: profile.value.max_commute_miles ?? 25,
    })
  }
})
</script>
