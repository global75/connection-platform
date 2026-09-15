import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior: () => ({ top: 0 }),
  routes: [
    // ── Public ──────────────────────────────────────────────────
    {
      path: '/',
      component: () => import('@/layouts/PublicLayout.vue'),
      children: [
        { path: '',       name: 'home',    component: () => import('@/pages/Home.vue') },
        { path: 'jobs',   name: 'jobs',    component: () => import('@/pages/jobs/JobSearch.vue') },
        { path: 'jobs/:slug', name: 'job', component: () => import('@/pages/jobs/JobDetail.vue') },
      ],
    },

    // ── Auth ─────────────────────────────────────────────────────
    {
      path: '/',
      component: () => import('@/layouts/AuthLayout.vue'),
      meta: { guest: true },
      children: [
        { path: 'login',    name: 'login',    component: () => import('@/pages/auth/Login.vue') },
        { path: 'register', name: 'register', component: () => import('@/pages/auth/Register.vue') },
      ],
    },

    // ── Employer ─────────────────────────────────────────────────
    {
      path: '/employer',
      component: () => import('@/layouts/AppLayout.vue'),
      meta: { auth: true, role: 'employer' },
      children: [
        { path: 'dashboard',              name: 'employer.dashboard',     component: () => import('@/pages/employer/Dashboard.vue') },
        { path: 'profile',                name: 'employer.profile',       component: () => import('@/pages/employer/Profile.vue') },
        { path: 'jobs',                   name: 'employer.jobs',          component: () => import('@/pages/employer/Jobs.vue') },
        { path: 'jobs/new',               name: 'employer.jobs.create',   component: () => import('@/pages/employer/PostJob.vue') },
        { path: 'jobs/:id/edit',          name: 'employer.jobs.edit',     component: () => import('@/pages/employer/PostJob.vue') },
        { path: 'applications',           name: 'employer.applications',  component: () => import('@/pages/employer/Applications.vue') },
        { path: 'applications/:id',       name: 'employer.application',   component: () => import('@/pages/employer/ApplicationDetail.vue') },
        { path: 'messages',               name: 'employer.messages',      component: () => import('@/pages/shared/Messages.vue') },
      ],
    },

    // ── Job Seeker ───────────────────────────────────────────────
    {
      path: '/job-seeker',
      component: () => import('@/layouts/AppLayout.vue'),
      meta: { auth: true, role: 'job_seeker' },
      children: [
        { path: 'dashboard',          name: 'seeker.dashboard',      component: () => import('@/pages/jobseeker/Dashboard.vue') },
        { path: 'profile',            name: 'seeker.profile',        component: () => import('@/pages/jobseeker/Profile.vue') },
        { path: 'applications',       name: 'seeker.applications',   component: () => import('@/pages/jobseeker/Applications.vue') },
        { path: 'saved',              name: 'seeker.saved',          component: () => import('@/pages/jobseeker/SavedJobs.vue') },
        { path: 'messages',           name: 'seeker.messages',       component: () => import('@/pages/shared/Messages.vue') },
      ],
    },

    // ── Admin ────────────────────────────────────────────────────
    {
      path: '/admin',
      component: () => import('@/layouts/AppLayout.vue'),
      meta: { auth: true, role: 'admin' },
      children: [
        { path: 'dashboard', name: 'admin.dashboard', component: () => import('@/pages/admin/Dashboard.vue') },
        { path: 'users',     name: 'admin.users',     component: () => import('@/pages/admin/Users.vue') },
        { path: 'jobs',      name: 'admin.jobs',      component: () => import('@/pages/admin/Jobs.vue') },
        { path: 'reports',   name: 'admin.reports',   component: () => import('@/pages/admin/Reports.vue') },
      ],
    },

    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/pages/NotFound.vue') },
  ],
})

router.beforeEach((to, _from, next) => {
  const auth = useAuthStore()

  if (to.meta.guest && auth.isAuthenticated) {
    return next('/')
  }
  if (to.meta.auth && !auth.isAuthenticated) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }
  if (to.meta.role && auth.user?.role !== to.meta.role && auth.user?.role !== 'admin') {
    return next('/')
  }

  next()
})

export default router
