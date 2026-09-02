<template>
  <div class="max-w-3xl space-y-6">
    <h1 class="text-2xl font-bold">Company Profile</h1>

    <VerificationPortal subject="employer" />

    <form @submit.prevent="save" class="space-y-5">
      <!-- Basic -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold border-b pb-2">Company Info</h2>
        <div><label class="label">Company name *</label>
          <input v-model="form.company_name" class="input" required /></div>
        <div><label class="label">Description</label>
          <textarea v-model="form.description" rows="4" class="input" placeholder="Tell job seekers about your company…"></textarea></div>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="label">Industry</label><input v-model="form.industry" class="input" /></div>
          <div>
            <label class="label">Company size</label>
            <select v-model="form.company_size" class="input">
              <option value="1-10">1–10</option>
              <option value="11-50">11–50</option>
              <option value="51-200">51–200</option>
              <option value="201-500">201–500</option>
              <option value="501-1000">501–1000</option>
              <option value="1000+">1000+</option>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="label">Website</label><input v-model="form.website" type="url" class="input" /></div>
          <div><label class="label">Founded year</label><input v-model.number="form.founded_year" type="number" min="1800" :max="new Date().getFullYear()" class="input" /></div>
        </div>
      </div>

      <!-- Location -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold border-b pb-2">Headquarters</h2>
        <div class="grid grid-cols-3 gap-4">
          <div><label class="label">City</label><input v-model="form.headquarters_city" class="input" /></div>
          <div><label class="label">State</label><input v-model="form.headquarters_state" class="input" /></div>
          <div><label class="label">Country</label><input v-model="form.headquarters_country" class="input" /></div>
        </div>
      </div>

      <!-- Social -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold border-b pb-2">Social Links</h2>
        <div><label class="label">LinkedIn</label><input v-model="form.linkedin_url" type="url" class="input" /></div>
        <div><label class="label">Twitter / X</label><input v-model="form.twitter_url" type="url" class="input" /></div>
      </div>

      <!-- Logo -->
      <div class="card p-6 space-y-3">
        <h2 class="font-semibold border-b pb-2">Company Logo</h2>
        <div v-if="profile?.logo" class="flex items-center gap-3">
          <img :src="`/storage/${profile.logo}`" class="w-16 h-16 object-contain rounded border" />
          <span class="text-sm text-gray-500">Current logo</span>
        </div>
        <div><label class="label">Upload new logo (PNG/JPG, max 2MB)</label>
          <input @change="onLogo" type="file" accept="image/*" class="input" /></div>
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
const logo    = ref(null)

const form = ref({
  company_name: '', description: '', industry: '', company_size: '',
  website: '', founded_year: null, headquarters_city: '', headquarters_state: '',
  headquarters_country: '', linkedin_url: '', twitter_url: '',
})

function onLogo(e) { logo.value = e.target.files[0] }

async function save() {
  saving.value  = true
  success.value = false
  errors.value  = null
  try {
    const fd = new FormData()
    Object.entries(form.value).forEach(([k, v]) => { if (v !== null && v !== undefined && v !== '') fd.append(k, v) })
    if (logo.value) fd.append('logo', logo.value)
    fd.append('_method', 'PUT')
    const { data } = await client.post('/employer/profile', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    profile.value = data.profile
    success.value = true
  } catch (err) {
    errors.value = err.response?.data?.errors ?? null
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  const { data } = await client.get('/employer/profile')
  profile.value = data.profile
  Object.assign(form.value, data.profile)
})
</script>
