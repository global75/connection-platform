<template>
  <section class="bg-gradient-to-br from-primary-700 to-primary-900 text-white py-20 px-4">
    <div class="max-w-3xl mx-auto text-center">
      <h1 class="text-4xl font-bold mb-5">Hire the right talent — locally or globally</h1>
      <p class="text-lg text-primary-100 mb-8">
        Find professionals in your city, across your country, or around the world. Choose on-site,
        hybrid or remote hiring based on your business needs.
      </p>
      <RouterLink to="/register?role=employer" class="btn bg-white text-primary-700 hover:bg-primary-50 text-base px-8 py-3">
        Post a Job
      </RouterLink>
    </div>
  </section>

  <!-- Posting a job is three questions, not a form marathon -->
  <section class="max-w-5xl mx-auto px-4 py-16">
    <h2 class="text-2xl font-bold text-center mb-3">Three questions decide who sees your job</h2>
    <p class="text-gray-500 text-center mb-10 max-w-2xl mx-auto">
      Where the job is, how the work happens, and who is allowed to apply are separate choices —
      so a Denver store role and a role open worldwide both work here.
    </p>

    <div class="grid gap-6 md:grid-cols-3">
      <div class="card p-6">
        <span class="text-2xl">📍</span>
        <h3 class="font-semibold mt-3">Where is the job?</h3>
        <p class="text-sm text-gray-500 mt-1">
          A city and state for on-site and hybrid roles, or just a country for fully remote ones.
        </p>
      </div>
      <div class="card p-6">
        <span class="text-2xl">🏢</span>
        <h3 class="font-semibold mt-3">How will the work happen?</h3>
        <p class="text-sm text-gray-500 mt-1">
          On-site, hybrid or remote. This is how the job is performed — not who can apply.
        </p>
      </div>
      <div class="card p-6">
        <span class="text-2xl">🌎</span>
        <h3 class="font-semibold mt-3">Who can apply?</h3>
        <p class="text-sm text-gray-500 mt-1">
          Local candidates, your state, your country, North America, specific countries, or anyone.
        </p>
      </div>
    </div>
  </section>

  <section class="bg-white py-16 px-4">
    <div class="max-w-5xl mx-auto">
      <h2 class="text-2xl font-bold text-center mb-10">Roles that all live on one platform</h2>
      <div class="grid gap-4 sm:grid-cols-2">
        <div v-for="example in examples" :key="example.title" class="card p-5">
          <p class="font-semibold">{{ example.title }}</p>
          <p class="text-sm text-gray-500">{{ example.company }}</p>
          <div class="flex flex-wrap gap-x-4 gap-y-1 mt-3 text-sm text-gray-600">
            <span>{{ example.location }}</span>
            <span>{{ example.arrangement }}</span>
            <span>{{ example.eligibility }}</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="py-16 px-4">
    <div class="max-w-3xl mx-auto text-center">
      <h2 class="text-2xl font-bold mb-4">Ready to hire?</h2>
      <p class="text-gray-600 mb-8">Create an employer account and post your first role in minutes.</p>
      <RouterLink to="/register?role=employer" class="btn-primary text-base px-8 py-3">Post a Job</RouterLink>
    </div>
  </section>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { useSeo } from '@/composables/useSeo'

useSeo({
  title: 'For employers — hire locally, nationally or globally',
  description: 'Post on-site, hybrid and remote roles. Choose where the job is, how the work happens, and who can apply.',
  canonical: '/for-employers',
})

// Shapes of hiring the platform supports, described rather than counted.
const examples = [
  { title: 'Marketing Coordinator', company: 'A Denver retail group', location: '📍 Denver, CO', arrangement: '🏢 On-site', eligibility: '👥 Local candidates' },
  { title: 'Operations Analyst',    company: 'A Colorado employer',   location: '📍 Boulder, CO', arrangement: '🔄 Hybrid',  eligibility: '👥 Statewide' },
  { title: 'Customer Support',      company: 'A US-wide team',        location: '🌎 United States', arrangement: '🏠 Remote', eligibility: '👥 Nationwide' },
  { title: 'Full-Stack Developer',  company: 'A distributed company', location: '🌎 International', arrangement: '🏠 Remote', eligibility: '👥 Selected countries' },
]
</script>
