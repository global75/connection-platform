<template>
  <div class="max-w-3xl space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">My Profile</h1>
      <div v-if="profile" class="flex items-center gap-2">
        <div class="w-32 bg-gray-200 rounded-full h-2">
          <div class="bg-primary-600 h-2 rounded-full" :style="`width:${profile.completion}%`"></div>
        </div>

    <VerificationPortal subject="candidate" />
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
        <div class="grid grid-cols-2 gap-4">
          <div><label class="label">City</label><input v-model="form.current_city" class="input" /></div>
          <div><label class="label">Country</label><input v-model="form.current_country" class="input" /></div>
        </div>
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
        <div class="flex gap-4">
          <label class="flex items-center gap-2 text-sm">
            <input v-model="form.open_to_remote" type="checkbox" class="rounded text-primary-600" /> Open to remote
          </label>
          <label class="flex items-center gap-2 text-sm">
            <input v-model="form.willing_to_relocate" type="checkbox" class="rounded text-primary-600" /> Willing to relocate
          </label>
        </div>
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
import VerificationPortal from '@/components/verification/VerificationPortal.vue'
import { ref, onMounted } from 'vue'
import client from '@/api/client'

const profile = ref(null)
const saving  = ref(false)
const success = ref(false)
const errors  = ref(null)
const resume  = ref(null)

const form = ref({
  headline: '', bio: '', current_city: '', current_country: '', nationality: '',
  current_job_title: '', desired_job_title: '', experience_level: 'mid',
  years_of_experience: 0, desired_salary_min: null, desired_salary_max: null,
  availability: 'negotiable', open_to_remote: true, willing_to_relocate: false,
  linkedin_url: '', github_url: '', portfolio_url: '',
})

function onResume(e) { resume.value = e.target.files[0] }

async function save() {
  saving.value  = true
  success.value = false
  errors.value  = null
  try {
    const fd = new FormData()
    Object.entries(form.value).forEach(([k, v]) => { if (v !== null && v !== undefined) fd.append(k, v) })
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
  const { data } = await client.get('/job-seeker/profile')
  profile.value = data.profile
  Object.assign(form.value, data.profile)
})
</script>
