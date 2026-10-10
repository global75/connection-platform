<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-900 mb-1">Welcome back</h2>
    <p class="text-gray-500 text-sm mb-6">Sign in to your Remote Arena account</p>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="label">Email address</label>
        <input v-model="form.email" type="email" class="input" placeholder="you@example.com" required />
      </div>
      <div>
        <label class="label">Password</label>
        <input v-model="form.password" type="password" class="input" placeholder="••••••••" required />
      </div>

      <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>

      <button type="submit" class="btn-primary w-full" :disabled="loading">
        <span v-if="loading">Signing in…</span>
        <span v-else>Sign in</span>
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
      Don't have an account?
      <RouterLink to="/register" class="text-primary-600 font-medium hover:underline">Sign up</RouterLink>
    </p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth    = useAuthStore()
const loading = ref(false)
const error   = ref('')
const form    = ref({ email: '', password: '' })

async function submit() {
  loading.value = true
  error.value   = ''
  try {
    await auth.login(form.value)
  } catch (err) {
    error.value = err.response?.data?.errors?.email?.[0]
      ?? err.response?.data?.message
      ?? 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
