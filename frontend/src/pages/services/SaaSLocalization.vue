<template>
  <div class="bg-slate-900 text-white">
    <!-- Hero -->
    <section class="max-w-5xl mx-auto px-4 pt-20 pb-16 text-center">
      <p class="text-indigo-400 font-semibold text-sm tracking-wide uppercase mb-4">SaaS Localization Service</p>
      <h1 class="text-4xl sm:text-5xl font-bold leading-tight mb-6">
        Ship your app in <span class="text-indigo-400">every market</span>, not just English
      </h1>
      <p class="text-lg text-slate-300 max-w-2xl mx-auto mb-10">
        We audit your product, extract every hardcoded string into clean i18n JSON, and fix the RTL layout
        bugs that break when you flip to Arabic — with a 48-hour turnaround on the initial audit.
      </p>
      <a href="#audit-form" class="inline-block bg-indigo-600 hover:bg-indigo-500 transition-colors px-8 py-3 rounded-xl font-semibold">
        Request a Free Audit
      </a>
    </section>

    <!-- Feature highlights -->
    <section class="max-w-5xl mx-auto px-4 pb-16 grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="f in features" :key="f.title" class="bg-slate-800/50 border border-slate-800 rounded-xl p-6">
        <span class="text-2xl">{{ f.emoji }}</span>
        <p class="font-semibold mt-3">{{ f.title }}</p>
        <p class="text-sm text-slate-400 mt-1">{{ f.description }}</p>
      </div>
    </section>

    <!-- Pricing -->
    <section class="bg-slate-950/40 border-y border-slate-800 py-16">
      <div class="max-w-5xl mx-auto px-4">
        <h2 class="text-2xl font-bold text-center mb-10">Pricing Packages</h2>
        <div class="grid md:grid-cols-3 gap-6">
          <div
            v-for="pkg in packages" :key="pkg.name"
            :class="['bg-slate-900 border rounded-xl p-6 flex flex-col',
              pkg.featured ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-slate-800']"
          >
            <p v-if="pkg.featured" class="text-indigo-400 text-xs font-semibold uppercase mb-2">Most Popular</p>
            <p class="font-bold text-lg">{{ pkg.name }}</p>
            <p class="text-3xl font-bold mt-2">{{ pkg.price }}</p>
            <ul class="text-sm text-slate-300 mt-4 space-y-2 flex-1">
              <li v-for="item in pkg.features" :key="item" class="flex items-start gap-2">
                <span class="text-indigo-400">✓</span> {{ item }}
              </li>
            </ul>
            <a href="#audit-form" class="mt-6 text-center bg-indigo-600 hover:bg-indigo-500 transition-colors px-4 py-2.5 rounded-xl font-semibold">
              Get Started
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Lead form -->
    <section id="audit-form" class="max-w-2xl mx-auto px-4 py-20">
      <h2 class="text-2xl font-bold text-center mb-2">Request Your Free Localization Audit</h2>
      <p class="text-slate-400 text-center mb-8">Tell us about your app and we'll reply within 48 hours.</p>

      <form v-if="!submitted" @submit.prevent="submit" class="bg-slate-800/50 border border-slate-800 rounded-xl p-6 space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1">Name</label>
          <input v-model="form.name" type="text" required maxlength="255"
            class="w-full rounded-xl bg-slate-900 border border-slate-800 px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1">Work email</label>
          <input v-model="form.email" type="email" required maxlength="255"
            class="w-full rounded-xl bg-slate-900 border border-slate-800 px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1">App URL</label>
          <input v-model="form.app_url" type="url" required maxlength="255" placeholder="https://app.example.com"
            class="w-full rounded-xl bg-slate-900 border border-slate-800 px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-300 mb-2">Target languages</label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="lang in availableLanguages" :key="lang.value"
              type="button"
              @click="toggleLanguage(lang.value)"
              :class="['px-3 py-1.5 rounded-lg text-sm border transition-colors',
                form.target_languages.includes(lang.value)
                  ? 'bg-indigo-600 border-indigo-500 text-white'
                  : 'bg-slate-900 border-slate-800 text-slate-300 hover:border-slate-600']"
            >
              {{ lang.label }}
            </button>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1">Anything else? (optional)</label>
          <textarea v-model="form.message" rows="3" maxlength="2000"
            class="w-full rounded-xl bg-slate-900 border border-slate-800 px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="RTL support, specific launch date, etc."></textarea>
        </div>

        <div v-if="errors" class="text-red-400 text-sm space-y-1">
          <p v-for="(msgs, field) in errors" :key="field">{{ msgs[0] }}</p>
        </div>

        <button type="submit" :disabled="loading"
          class="w-full bg-indigo-600 hover:bg-indigo-500 disabled:opacity-60 transition-colors px-4 py-3 rounded-xl font-semibold">
          {{ loading ? 'Sending…' : 'Request Free Audit' }}
        </button>
      </form>

      <div v-else class="bg-slate-800/50 border border-slate-800 rounded-xl p-8 text-center">
        <p class="text-2xl mb-2">✓</p>
        <p class="font-semibold">{{ successMessage }}</p>
        <p class="text-slate-400 text-sm mt-1">We'll be in touch within 48 hours.</p>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { servicesApi } from '@/api/services'

const availableLanguages = [
  { value: 'french',     label: 'French' },
  { value: 'arabic',     label: 'Arabic' },
  { value: 'spanish',    label: 'Spanish' },
  { value: 'german',     label: 'German' },
  { value: 'portuguese', label: 'Portuguese' },
  { value: 'japanese',   label: 'Japanese' },
  { value: 'mandarin',   label: 'Mandarin' },
]

const features = [
  { emoji: '🌐', title: 'i18n JSON extraction',        description: 'Every hardcoded string pulled into clean, structured translation files.' },
  { emoji: '🇫🇷', title: 'French & Arabic localization', description: 'Native-quality translation, not machine output dropped in raw.' },
  { emoji: '↔️', title: 'RTL UI fixes',                 description: 'Layout, spacing and icon-mirroring fixes for right-to-left languages.' },
  { emoji: '⚡', title: '48-hour audit SLA',             description: 'A full localization-readiness report back in your inbox within two days.' },
]

const packages = [
  { name: 'Basic i18n',      price: '$149',   features: ['String extraction to i18n JSON', 'Up to 2 languages', 'Localization-readiness report'] },
  { name: 'Full App + RTL',  price: '$449',   featured: true, features: ['Everything in Basic', 'Up to 5 languages', 'RTL layout fixes', 'Native review pass'] },
  { name: 'Enterprise',      price: 'Custom', features: ['Unlimited languages', 'Ongoing translation pipeline', 'Dedicated support'] },
]

const form = reactive({
  name: '', email: '', app_url: '', target_languages: [], message: '',
})
const loading        = ref(false)
const errors          = ref(null)
const submitted       = ref(false)
const successMessage  = ref('')

function toggleLanguage(value) {
  const i = form.target_languages.indexOf(value)
  if (i === -1) form.target_languages.push(value)
  else form.target_languages.splice(i, 1)
}

async function submit() {
  loading.value = true
  errors.value  = null
  try {
    const { data } = await servicesApi.submitLocalizationLead(form)
    successMessage.value = data.message
    submitted.value = true
  } catch (err) {
    errors.value = err.response?.data?.errors ?? null
  } finally {
    loading.value = false
  }
}
</script>
