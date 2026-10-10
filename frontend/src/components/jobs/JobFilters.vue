<template>
  <div class="space-y-5">
    <!-- Search mode: the plain-language shortcut over the location filters -->
    <div>
      <label class="label">Show me</label>
      <div class="grid grid-cols-2 gap-2">
        <button
          v-for="mode in SEARCH_MODES" :key="mode.value"
          type="button"
          :title="mode.hint"
          @click="setMode(mode.value)"
          :class="['px-2.5 py-2 rounded-lg text-xs font-medium border transition-colors',
            filters.mode === mode.value
              ? 'bg-primary-600 text-white border-primary-600'
              : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50']"
        >{{ mode.label }}</button>
      </div>
      <p class="text-xs text-gray-400 mt-1.5">{{ modeHint }}</p>
    </div>

    <div>
      <label class="label">Keyword</label>
      <input v-model="filters.q" type="text" class="input" placeholder="Job title, skill or company" @keydown.enter="$emit('apply')" />
    </div>

    <!-- Where the job is -->
    <div v-if="filters.mode !== 'remote' && filters.mode !== 'international'">
      <label class="label">Location</label>
      <LocationInput v-model="filters.location" @select="onLocationSelect" />
    </div>

    <!-- Distance only makes sense once we know where "here" is -->
    <div v-if="filters.mode === 'near_me'">
      <label class="label">Distance</label>
      <select v-model="filters.radius" class="input">
        <option v-for="miles in RADIUS_OPTIONS" :key="miles" :value="miles">Within {{ miles }} miles</option>
      </select>
      <p v-if="!filters.location" class="text-xs text-amber-600 mt-1.5">
        Enter a location to search by distance.
      </p>
    </div>

    <!-- How the work happens -->
    <fieldset>
      <legend class="label">Work arrangement</legend>
      <label
        v-for="option in WORK_ARRANGEMENTS" :key="option.value"
        class="flex items-center gap-2 text-sm text-gray-700 py-1"
      >
        <input
          type="checkbox"
          class="rounded text-primary-600"
          :value="option.value"
          v-model="filters.work_arrangement"
        />
        <span>{{ option.icon }} {{ option.label }}</span>
        <span v-if="counts[option.value]" class="text-xs text-gray-400">({{ counts[option.value] }})</span>
      </label>
    </fieldset>

    <fieldset>
      <legend class="label">Employment type</legend>
      <label
        v-for="option in EMPLOYMENT_TYPES" :key="option.value"
        class="flex items-center gap-2 text-sm text-gray-700 py-1"
      >
        <input type="checkbox" class="rounded text-primary-600" :value="option.value" v-model="filters.employment_type" />
        {{ option.label }}
      </label>
    </fieldset>

    <div>
      <label class="label">Experience level</label>
      <select v-model="filters.experience_level" class="input">
        <option value="">Any</option>
        <option v-for="option in EXPERIENCE_LEVELS" :key="option.value" :value="option.value">{{ option.label }}</option>
      </select>
    </div>

    <div>
      <label class="label">Minimum salary</label>
      <input v-model.number="filters.salary_min" type="number" class="input" placeholder="e.g. 60000" min="0" step="1000" />
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-700">
      <input v-model="filters.visa_sponsorship" type="checkbox" class="rounded text-primary-600" />
      Offers visa sponsorship
    </label>

    <div class="flex gap-2 pt-1">
      <button @click="$emit('apply')" class="btn-primary flex-1">Apply filters</button>
      <button @click="$emit('reset')" class="btn-secondary">Reset</button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import LocationInput from './LocationInput.vue'
import {
  SEARCH_MODES, RADIUS_OPTIONS, WORK_ARRANGEMENTS, EMPLOYMENT_TYPES, EXPERIENCE_LEVELS,
} from '@/lib/labels'

const props = defineProps({
  filters: { type: Object, required: true },
  counts:  { type: Object, default: () => ({}) },
})
defineEmits(['apply', 'reset'])

const modeHint = computed(
  () => SEARCH_MODES.find((m) => m.value === props.filters.mode)?.hint ?? ''
)

function setMode(mode) {
  props.filters.mode = mode
  if (mode === 'near_me' && !props.filters.radius) {
    props.filters.radius = 25
  }
}

// A picked suggestion carries real coordinates, which makes distance exact.
function onLocationSelect(suggestion) {
  props.filters.latitude  = suggestion.latitude
  props.filters.longitude = suggestion.longitude
}
</script>
