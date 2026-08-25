<template>
  <span v-if="!qualification" class="badge-gray text-xs">Not qualified</span>

  <span v-else-if="pending" class="badge-gray text-xs">Qualifying…</span>

  <span v-else-if="qualification.status === 'failed'" class="badge-red text-xs" :title="qualification.error">
    Qualification failed
  </span>

  <span v-else :class="tierClass" class="text-xs whitespace-nowrap" :title="tooltip">
    {{ tierLabel }} · {{ qualification.score }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  qualification: { type: Object, default: null },
})

const TIER_LABELS  = { hot: 'Hot lead', warm: 'Warm lead', cold: 'Cold lead' }
const TIER_CLASSES = { hot: 'badge-green', warm: 'badge-yellow', cold: 'badge-gray' }

const pending    = computed(() => ['pending', 'processing'].includes(props.qualification?.status))
const tierLabel  = computed(() => TIER_LABELS[props.qualification?.tier] ?? 'Unscored')
const tierClass  = computed(() => TIER_CLASSES[props.qualification?.tier] ?? 'badge-gray')
const tooltip    = computed(() => props.qualification?.summary ?? '')
</script>
