<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-900 mb-1">Create your account</h2>
    <p class="text-gray-500 text-sm mb-6">Join thousands of professionals on Remote Arena</p>

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
        <input v-model="form.name" type="text" class="input" placeholder="Maria Santos" required />
      </div>

      <div v-if="form.role === 'employer'">
        <label class="label">Company name</label>
        <input v-model="form.company_name" type="text" class="input" placeholder="Acme Corp" required />
      </div>

      <div>
        <label class="label">Email address</label>
        <input v-model="form.email" type="email" class="input" placeholder="you@example.com" required />
      </div>

      <div>
        <label class="label">Country</label>
        <input v-model="form.country" type="text" class="input" placeholder="Philippines" />
      </div>

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
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth    = useAuthStore()
const loading = ref(false)
const errors  = ref(null)
const roles   = [
  { value: 'job_seeker', label: '🌍 I\'m looking for work' },
  { value: 'employer',   label: '🏢 I\'m hiring' },
]
const form = ref({
  name: '', email: '', password: '', password_confirmation: '',
  role: 'job_seeker', company_name: '', country: '',
})

async function submit() {
  loading.value = true
  errors.value  = null
  try {
    await auth.register(form.value)
  } catch (err) {
    errors.value = err.response?.data?.errors ?? null
  } finally {
    loading.value = false
  }
}
</script>
