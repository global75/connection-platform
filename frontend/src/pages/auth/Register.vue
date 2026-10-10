<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-900 mb-1">Create your account</h2>
    <p class="text-gray-500 text-sm mb-6">Hiring and job searching — local, national and international.</p>

    <!-- Role toggle -->
    <div class="flex rounded-lg border border-gray-200 p-1 mb-6 bg-gray-50">
      <button
        v-for="r in roles" :key="r.value"
        type="button"
        @click="form.role = r.value"
        :class="['flex-1 py-2 text-sm font-medium rounded-md transition-all',
          form.role === r.value
            ? 'bg-white shadow text-primary-700 border border-gray-200'
            : 'text-gray-500 hover:text-gray-700']"
      >
        {{ r.label }}
      </button>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="label">Full name</label>
        <input v-model="form.name" type="text" class="input" placeholder="Alex Rivera" required />
      </div>

      <div v-if="isEmployer">
        <label class="label">Company name</label>
        <input v-model="form.company_name" type="text" class="input" placeholder="Acme Corp" required />
      </div>

      <div>
        <label class="label">Email address</label>
        <input v-model="form.email" type="email" class="input" placeholder="you@example.com" required />
      </div>

      <!-- Onboarding: where you are -->
      <fieldset class="space-y-3">
        <legend class="label">{{ isEmployer ? 'Where is your company located?' : 'Where are you located?' }}</legend>
        <select v-model="form.country" class="input">
          <option value="">Select a country</option>
          <option v-for="country in countries" :key="country.code" :value="country.code">{{ country.name }}</option>
        </select>
        <div class="grid grid-cols-2 gap-3">
          <input v-model="form.state" type="text" class="input" placeholder="State / province" />
          <input v-model="form.city" type="text" class="input" placeholder="City" />
        </div>
      </fieldset>

      <!-- Onboarding: how and where you want to work / hire -->
      <fieldset v-if="!isEmployer" class="space-y-3">
        <legend class="label">How do you want to work?</legend>
        <div class="flex flex-wrap gap-3">
          <label v-for="option in WORK_ARRANGEMENTS" :key="option.value" class="flex items-center gap-2 text-sm">
            <input type="checkbox" class="rounded text-primary-600" :value="option.value" v-model="form.work_arrangements" />
            {{ option.icon }} {{ option.label }}
          </label>
        </div>

        <legend class="label pt-1">Where are you willing to work?</legend>
        <div class="flex flex-wrap gap-3">
          <label v-for="scope in LOCATION_SCOPES" :key="scope.value" class="flex items-center gap-2 text-sm">
            <input type="checkbox" class="rounded text-primary-600" :value="scope.value" v-model="form.location_scopes" />
            {{ scope.label }}
          </label>
        </div>
      </fieldset>

      <fieldset v-else class="space-y-3">
        <legend class="label">Where are you hiring?</legend>
        <div class="flex flex-wrap gap-3">
          <label v-for="scope in EMPLOYER_HIRING_SCOPES" :key="scope.value" class="flex items-center gap-2 text-sm">
            <input type="checkbox" class="rounded text-primary-600" :value="scope.value" v-model="form.hiring_scopes" />
            {{ scope.label }}
          </label>
        </div>
      </fieldset>

      <div>
        <label class="label">Password</label>
        <input v-model="form.password" type="password" class="input" placeholder="Min. 8 characters" required minlength="8" />
      </div>

      <div>
        <label class="label">Confirm password</label>
        <input v-model="form.password_confirmation" type="password" class="input" placeholder="Repeat password" required />
      </div>

      <div v-if="errors" class="text-red-600 text-sm space-y-1">
        <p v-for="(msgs, field) in errors" :key="field">{{ msgs[0] }}</p>
      </div>

      <button type="submit" class="btn-primary w-full" :disabled="loading">
        <span v-if="loading">Creating account…</span>
        <span v-else>Create account</span>
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
      Already have an account?
      <RouterLink to="/login" class="text-primary-600 font-medium hover:underline">Sign in</RouterLink>
    </p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { discoveryApi } from '@/api/discovery'
import { WORK_ARRANGEMENTS, LOCATION_SCOPES, EMPLOYER_HIRING_SCOPES } from '@/lib/labels'

const auth      = useAuthStore()
const route     = useRoute()
const loading   = ref(false)
const errors    = ref(null)
const countries = ref([])

const roles = [
  { value: 'job_seeker', label: '🔎 I\'m looking for work' },
  { value: 'employer',   label: '🏢 I\'m hiring' },
]

const form = ref({
  name: '', email: '', password: '', password_confirmation: '',
  role: route.query.role === 'employer' ? 'employer' : 'job_seeker',
  company_name: '', country: '', state: '', city: '',
  work_arrangements: [], location_scopes: [], hiring_scopes: [],
})

const isEmployer = computed(() => form.value.role === 'employer')

async function submit() {
  loading.value = true
  errors.value  = null
  try {
    // Only send the preference set that belongs to the chosen role.
    const { work_arrangements, location_scopes, hiring_scopes, ...rest } = form.value
    await auth.register(isEmployer.value
      ? { ...rest, hiring_scopes }
      : { ...rest, work_arrangements, location_scopes })
  } catch (err) {
    errors.value = err.response?.data?.errors ?? null
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await discoveryApi.filters()
    countries.value = data.countries
  } catch {
    countries.value = []
  }
})
</script>
