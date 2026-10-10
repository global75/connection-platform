<template>
  <div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col fixed h-full z-20">
      <div class="h-16 flex items-center px-6 border-b border-gray-200">
        <RouterLink to="/" class="text-xl font-bold text-primary-600">Remote Arena</RouterLink>
      </div>

      <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <template v-for="item in navItems" :key="item.to">
          <RouterLink
            :to="item.to"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-primary-50 hover:text-primary-700 transition-colors"
            active-class="bg-primary-50 text-primary-700"
          >
            <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
            {{ item.label }}
          </RouterLink>
        </template>
      </nav>

      <div class="px-3 py-4 border-t border-gray-200">
        <div class="flex items-center gap-3 px-3 py-2 mb-1">
          <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-semibold text-sm">
            {{ userInitial }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ auth.user?.name }}</p>
            <p class="text-xs text-gray-500 capitalize">{{ auth.user?.role?.replace('_', ' ') }}</p>
          </div>
        </div>
        <button @click="auth.logout()" class="w-full text-left px-3 py-2 text-sm text-gray-500 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors">
          Sign out
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 ml-64 flex flex-col min-h-screen">
      <header class="h-16 bg-white border-b border-gray-200 flex items-center px-6 sticky top-0 z-10">
        <slot name="header">
          <h1 class="text-lg font-semibold text-gray-900">{{ pageTitle }}</h1>
        </slot>
      </header>

      <main class="flex-1 p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  HomeIcon, BriefcaseIcon, UserIcon, ChatBubbleLeftRightIcon,
  ClipboardDocumentListIcon, BookmarkIcon, UsersIcon,
  ChartBarIcon, FlagIcon,
} from '@heroicons/vue/24/outline'

const auth  = useAuthStore()
const route = useRoute()

const userInitial = computed(() => auth.user?.name?.[0]?.toUpperCase() ?? '?')
const pageTitle   = computed(() => route.meta?.title ?? '')

const employerNav = [
  { to: '/employer/dashboard',     label: 'Dashboard',    icon: HomeIcon },
  { to: '/employer/jobs',          label: 'My Jobs',      icon: BriefcaseIcon },
  { to: '/employer/applications',  label: 'Applications', icon: ClipboardDocumentListIcon },
  { to: '/employer/messages',      label: 'Messages',     icon: ChatBubbleLeftRightIcon },
  { to: '/employer/profile',       label: 'Company Profile', icon: UserIcon },
]

const seekerNav = [
  { to: '/job-seeker/dashboard',   label: 'Dashboard',    icon: HomeIcon },
  { to: '/jobs',                   label: 'Browse Jobs',  icon: BriefcaseIcon },
  { to: '/job-seeker/applications',label: 'Applications', icon: ClipboardDocumentListIcon },
  { to: '/job-seeker/saved',       label: 'Saved Jobs',   icon: BookmarkIcon },
  { to: '/job-seeker/messages',    label: 'Messages',     icon: ChatBubbleLeftRightIcon },
  { to: '/job-seeker/profile',     label: 'My Profile',   icon: UserIcon },
]

const adminNav = [
  { to: '/admin/dashboard', label: 'Dashboard', icon: ChartBarIcon },
  { to: '/admin/users',     label: 'Users',     icon: UsersIcon },
  { to: '/admin/jobs',      label: 'Jobs',      icon: BriefcaseIcon },
  { to: '/admin/reports',   label: 'Reports',   icon: FlagIcon },
]

const navItems = computed(() => {
  if (auth.isAdmin)     return adminNav
  if (auth.isEmployer)  return employerNav
  return seekerNav
})
</script>
