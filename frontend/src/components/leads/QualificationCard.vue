<template>
  <div class="card p-6 space-y-5">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h2 class="font-semibold flex items-center gap-2">
          AI Lead Qualification
          <span class="badge-gray text-[10px] uppercase tracking-wide">Beta</span>
        </h2>
        <p class="text-xs text-gray-400 mt-0.5">{{ provenance }}</p>
      </div>

      <button class="btn-secondary text-sm" :disabled="busy" @click="$emit('requalify')">
        {{ busy ? 'Re-running…' : 'Re-run' }}
      </button>
    </div>

    <!-- No verdict yet -->
    <p v-if="!qualification" class="text-sm text-gray-400">
      This application has not been qualified yet.
    </p>

    <p v-else-if="isPending" class="text-sm text-gray-400">
      Qualification is running — this usually takes a few seconds.
    </p>

    <div v-else-if="qualification.status === 'failed'" class="text-sm text-red-600">
      Qualification failed. {{ qualification.error }}
    </div>

    <template v-else>
      <!-- Headline score -->
      <div class="flex items-center gap-5">
        <div class="flex flex-col items-center justify-center w-20 h-20 rounded-full border-4 flex-shrink-0"
             :class="ringClass">
          <span class="text-2xl font-bold leading-none">{{ qualification.score }}</span>
          <span class="text-[10px] text-gray-400 mt-0.5">/ 100</span>
        </div>
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span :class="tierClass" class="capitalize">{{ qualification.tier }} lead</span>
            <span :class="actionClass">{{ ACTION_LABELS[qualification.recommended_action] }}</span>
          </div>
          <p class="text-sm text-gray-700 mt-2">{{ qualification.summary }}</p>
        </div>
      </div>

      <!-- Per-criterion breakdown -->
      <div v-if="criteria.length" class="space-y-2">
        <div v-for="item in criteria" :key="item.key" class="flex items-center gap-3">
          <span class="text-xs text-gray-500 w-40 flex-shrink-0">{{ item.label }}</span>
          <div class="flex-1 h-1.5 rounded-full bg-gray-100 overflow-hidden">
            <div class="h-full rounded-full" :class="barClass(item.value)" :style="{ width: `${item.value}%` }" />
          </div>
          <span class="text-xs text-gray-500 w-8 text-right">{{ item.value }}</span>
        </div>
      </div>

      <!-- Strengths / concerns -->
      <div class="grid sm:grid-cols-2 gap-5">
        <div v-if="qualification.strengths?.length">
          <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Strengths</h3>
          <ul class="space-y-1.5">
            <li v-for="(strength, i) in qualification.strengths" :key="i" class="text-sm text-gray-700 flex gap-2">
              <span class="text-green-600 flex-shrink-0">✓</span><span>{{ strength }}</span>
            </li>
          </ul>
        </div>

        <div v-if="qualification.concerns?.length">
          <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Concerns</h3>
          <ul class="space-y-1.5">
            <li v-for="(concern, i) in qualification.concerns" :key="i" class="text-sm text-gray-700 flex gap-2">
              <span class="text-amber-600 flex-shrink-0">!</span><span>{{ concern }}</span>
            </li>
          </ul>
        </div>
      </div>

      <p class="text-xs text-gray-400 border-t border-gray-100 pt-4">
        Scores are decision support, not a decision. Review the full application before acting on them.
      </p>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  qualification: { type: Object, default: null },
  busy:          { type: Boolean, default: false },
})

defineEmits(['requalify'])

const CRITERIA_LABELS = {
  skills:       'Skills coverage',
  experience:   'Experience fit',
  compensation: 'Compensation fit',
  logistics:    'Location & work auth',
  intent:       'Application intent',
}

const ACTION_LABELS = {
  shortlist: 'Recommended: shortlist',
  review:    'Recommended: review',
  reject:    'Recommended: reject',
}

const ACTION_CLASSES = {
  shortlist: 'badge-green',
  review:    'badge-yellow',
  reject:    'badge-red',
}

const TIER_CLASSES = { hot: 'badge-green', warm: 'badge-yellow', cold: 'badge-gray' }

const isPending  = computed(() => ['pending', 'processing'].includes(props.qualification?.status))
const tierClass  = computed(() => TIER_CLASSES[props.qualification?.tier] ?? 'badge-gray')
const actionClass = computed(() => ACTION_CLASSES[props.qualification?.recommended_action] ?? 'badge-gray')

const ringClass = computed(() => {
  const score = props.qualification?.score ?? 0
  if (score >= 75) return 'border-green-400 text-green-700'
  if (score >= 50) return 'border-yellow-400 text-yellow-700'
  return 'border-gray-300 text-gray-500'
})

const criteria = computed(() =>
  Object.entries(props.qualification?.criteria ?? {})
    .filter(([key]) => key in CRITERIA_LABELS)
    .map(([key, value]) => ({ key, label: CRITERIA_LABELS[key], value })),
)

const provenance = computed(() => {
  const q = props.qualification
  if (!q?.qualified_at) return 'Scored automatically when the application arrives.'

  const scoredBy = q.provider === 'claude' ? `Claude (${q.model})` : 'built-in scoring'
  return `Scored by ${scoredBy} on ${new Date(q.qualified_at).toLocaleString()}`
})

function barClass(value) {
  if (value >= 75) return 'bg-green-500'
  if (value >= 50) return 'bg-yellow-400'
  return 'bg-gray-300'
}
</script>
