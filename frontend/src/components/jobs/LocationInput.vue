<template>
  <div class="relative">
    <input
      :value="modelValue"
      type="text"
      class="input"
      :placeholder="placeholder"
      autocomplete="off"
      @input="onInput"
      @focus="open = true"
      @keydown.esc="open = false"
      @keydown.enter="choose(suggestions[0])"
      @blur="close"
    />
    <ul
      v-if="open && suggestions.length"
      class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
    >
      <li v-for="s in suggestions" :key="s.label">
        <button
          type="button"
          class="w-full text-left px-3 py-2 text-sm hover:bg-primary-50"
          @mousedown.prevent="choose(s)"
        >
          📍 {{ s.label }}
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { discoveryApi } from '@/api/discovery'

defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'City, state, or country' },
})
const emit = defineEmits(['update:modelValue', 'select'])

const suggestions = ref([])
const open = ref(false)
let timer = null

/**
 * Suggestions come from places the platform can actually locate, so a picked
 * suggestion always resolves to real coordinates for distance search.
 */
function onInput(event) {
  const value = event.target.value
  emit('update:modelValue', value)
  open.value = true

  clearTimeout(timer)
  timer = setTimeout(async () => {
    if (value.trim().length < 2) {
      suggestions.value = []
      return
    }
    try {
      const { data } = await discoveryApi.suggestLocation(value)
      suggestions.value = data.suggestions
    } catch {
      suggestions.value = []
    }
  }, 200)
}

function choose(suggestion) {
  if (!suggestion) return
  emit('update:modelValue', suggestion.label)
  emit('select', suggestion)
  suggestions.value = []
  open.value = false
}

function close() {
  setTimeout(() => (open.value = false), 100)
}
</script>
