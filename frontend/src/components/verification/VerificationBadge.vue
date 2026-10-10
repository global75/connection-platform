<template>
  <span v-if="badge" :class="badge.class" class="text-xs whitespace-nowrap" :title="badge.title">
    {{ badge.label }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  // Employer badge
  verified: { type: Boolean, default: false },
  // Candidate badges
  identityVerified: { type: Boolean, default: false },
  badges: { type: Array, default: () => [] },
  // 'employer' | 'candidate'
  subject: { type: String, default: 'employer' },
})

const BADGE_LABELS = {
  id_verified:       'ID Verified',
  github_verified:   'GitHub Verified',
  linkedin_verified: 'LinkedIn Verified',
  domain_verified:   'Domain Verified',
  registry_verified: 'Registry Verified',
  skill_verified:    'Skill Verified',
}

const badge = computed(() => {
  if (props.subject === 'employer') {
    return props.verified
      ? { label: '✓ Verified Employer', class: 'badge-green', title: 'This company has verified its business identity.' }
      : null
  }

  if (props.identityVerified) {
    return { label: '✓ ID Verified', class: 'badge-green', title: 'This candidate has completed identity verification.' }
  }

  // Fall back to the strongest non-identity badge the candidate holds.
  const held = (props.badges ?? []).find((slug) => slug in BADGE_LABELS)

  return held
    ? { label: `✓ ${BADGE_LABELS[held]}`, class: 'badge-blue', title: `Verified: ${BADGE_LABELS[held]}.` }
    : null
})
</script>
