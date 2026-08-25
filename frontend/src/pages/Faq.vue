<template>
  <div class="max-w-3xl mx-auto px-4 py-12">
    <header class="text-center mb-10">
      <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Frequently Asked Questions</h1>
      <p class="text-gray-500 mt-3">
        Everything you need to know about finding opportunities, hiring talent and using Connextion.
      </p>
    </header>

    <!-- What the marketplace covers, stated once up front -->
    <div class="card p-6 mb-10">
      <p class="text-sm text-gray-600">
        Connextion is a hiring marketplace that connects professionals and employers locally,
        nationally and internationally. Every job says three separate things:
      </p>
      <dl class="grid sm:grid-cols-3 gap-4 mt-4 text-sm">
        <div>
          <dt class="font-semibold">📍 Where</dt>
          <dd class="text-gray-500">The city, state/province or country the job belongs to.</dd>
        </div>
        <div>
          <dt class="font-semibold">🏢 How</dt>
          <dd class="text-gray-500">On-site, hybrid or remote — how the work is performed.</dd>
        </div>
        <div>
          <dt class="font-semibold">🌎 Who</dt>
          <dd class="text-gray-500">Which candidates the employer accepts applications from.</dd>
        </div>
      </dl>
    </div>

    <section v-for="group in groups" :key="group.title" class="mb-10">
      <h2 class="text-xl font-bold text-gray-900 mb-4">{{ group.title }}</h2>

      <div class="card divide-y divide-gray-100">
        <div v-for="item in group.items" :key="item.q">
          <h3>
            <button
              type="button"
              class="w-full flex items-start justify-between gap-4 text-left px-5 py-4 hover:bg-gray-50"
              :aria-expanded="isOpen(item.q)"
              @click="toggle(item.q)"
            >
              <span class="font-medium text-gray-900">{{ item.q }}</span>
              <span class="text-gray-400 flex-shrink-0" aria-hidden="true">{{ isOpen(item.q) ? '−' : '+' }}</span>
            </button>
          </h3>
          <div v-show="isOpen(item.q)" class="px-5 pb-5 -mt-1 text-sm text-gray-600 space-y-3">
            <p v-for="(paragraph, i) in item.a" :key="i">{{ paragraph }}</p>
            <ul v-if="item.list" class="list-disc list-inside space-y-1">
              <li v-for="entry in item.list" :key="entry">{{ entry }}</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <div class="card p-6 text-center">
      <h2 class="text-lg font-semibold mb-2">Still have questions?</h2>
      <p class="text-sm text-gray-500 mb-5">
        Create an account to explore the marketplace, or browse what employers are hiring for right now.
      </p>
      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <RouterLink to="/jobs" class="btn-primary">Browse jobs</RouterLink>
        <RouterLink to="/for-employers" class="btn-secondary">Hiring on Connextion</RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useSeo } from '@/composables/useSeo'

useSeo({
  title: 'FAQ',
  description: 'How hiring works on Connextion — local, national and international roles, on-site, hybrid and remote, for job seekers and employers.',
  canonical: '/faq',
})

const open = ref(new Set())
const isOpen = (q) => open.value.has(q)
function toggle(q) {
  const next = new Set(open.value)
  next.has(q) ? next.delete(q) : next.add(q)
  open.value = next
}

/**
 * Every answer below describes behaviour that exists in the product today.
 * Where something is not built yet, the answer says so rather than implying it.
 */
const groups = [
  {
    title: '🌎 For job seekers',
    items: [
      {
        q: 'Is Connextion free for job seekers?',
        a: [
          'Yes. Creating a professional profile, browsing jobs and applying to jobs are free for job seekers. Connextion does not charge candidates a fee to apply for employment opportunities.',
        ],
      },
      {
        q: 'What kinds of jobs can I find on Connextion?',
        a: [
          'Connextion supports local, national and international opportunities across a range of industries. Depending on the employer and the position, jobs may be on-site, hybrid or remote.',
        ],
      },
      {
        q: 'Can I find jobs near me?',
        a: [
          'Yes. You can search by city, state or province, country, or by distance from a place — 5 to 100 miles. Distance search covers jobs whose location we can place on a map; roles without a mapped location still appear in city, state and country searches.',
          'Which jobs you see depends on what employers have posted in your area, so results vary by location.',
        ],
      },
      {
        q: 'Can I apply for jobs outside my city or country?',
        a: [
          'Yes, when the employer accepts candidates from your location. Every listing states where the job is, how the work is performed and who may apply, so you can tell before you apply.',
          'Being open to international candidates is a choice each employer makes per job — it is not automatic.',
        ],
      },
      {
        q: 'Does Connextion only have remote jobs?',
        a: [
          'No. Remote is one of three work arrangements on the platform, alongside on-site and hybrid. Jobs may be open to local, statewide, national, North American, country-specific or international candidates, depending on what the employer requires.',
        ],
        list: ['On-site jobs', 'Hybrid jobs', 'Remote jobs'],
      },
      {
        q: 'How do I apply to a job?',
        a: [
          'Create your professional profile, browse opportunities and apply from the job page. Your application carries your profile, and you can add a cover letter, a resume file and your expected salary.',
          'The employer receives the application and can reply to you through the messaging built into the platform.',
        ],
      },
      {
        q: 'Will recruiters or staffing agencies contact me?',
        a: [
          'Connextion is designed to connect professionals directly with employers, and the platform does not charge candidates placement fees or ask candidates to pay a recruiter to apply.',
          'The platform does not currently prevent an agency from registering as an employer, so a listing may come from one. Each listing shows the account that posted it.',
        ],
      },
    ],
  },
  {
    title: '🏢 For employers',
    items: [
      {
        q: 'What kinds of candidates can I hire through Connextion?',
        a: [
          'You can hire locally, nationally or internationally, depending on your requirements. On every job you set where the role is, whether it is on-site, hybrid or remote, and which candidates may apply.',
        ],
      },
      {
        q: 'Can I hire someone locally?',
        a: [
          'Yes. Post the city and state or province the role belongs to, and set the job to local candidates. You can also set how far from that location you will consider candidates, from 5 to 100 miles.',
        ],
      },
      {
        q: 'Can I hire nationally?',
        a: [
          'Yes. Set the job to national candidates and it is open to people anywhere in the country the role belongs to, subject to the requirements of the position.',
        ],
      },
      {
        q: 'Can I hire internationally?',
        a: [
          'Yes, when you open the job to candidates in the applicable countries — either internationally, across North America, or to a specific list of countries you choose.',
          'Employers are responsible for ensuring their hiring complies with applicable employment, tax, labour and work-authorisation requirements. Connextion is a marketplace: it does not provide immigration sponsorship, legal advice, tax advice or employer-of-record services.',
        ],
      },
      {
        q: 'Can I post remote, hybrid and on-site jobs?',
        a: [
          'Yes. The work arrangement is set per job and is separate from who may apply — a remote job can be limited to one country, and an on-site job can be open to candidates who are willing to relocate.',
          'On-site and hybrid roles require a city so local candidates can find them. A fully remote role only needs the country it is based in.',
        ],
      },
      {
        q: 'How do I post a job?',
        a: [
          'Create an employer account, fill in your company information, and post a job. Free accounts start with three job-post credits; paid plans post without consuming them.',
        ],
      },
      {
        q: 'Do I need a business email to create an employer account?',
        a: [
          'No. Any valid email address can be used to register an employer account — the platform does not currently restrict free email providers.',
          'You are asked for company information when you complete your employer profile, and that information is what candidates see on your listings.',
        ],
      },
      {
        q: 'How does employer verification work?',
        a: [
          'Employer accounts carry a verified status that our team applies after reviewing the company. It is a manual review rather than an automatic domain check, and it is not currently required in order to post a job.',
          'Because of that, a listing without the verified marker is not necessarily suspicious, and the marker is not a guarantee about any particular employer.',
        ],
      },
      {
        q: 'Are there placement or middleman fees?',
        a: [
          'Connextion connects employers and professionals directly. Employers pay for job-posting capacity and plan features; the platform does not currently charge a fee on a hire, and it is not a recruiter or staffing agency.',
        ],
      },
    ],
  },
  {
    title: '🤖 AI Solutions',
    items: [
      {
        q: 'What is AI Lead Qualification?',
        a: [
          'AI Lead Qualification is a separate business product in development, not yet available in the platform. It is planned to help businesses analyse and prioritise leads using information the business provides and available lead data — identifying which leads may be a stronger fit, organising prospects and producing qualification summaries.',
          'It is designed to sit alongside the hiring marketplace, not inside it. Nothing about it changes how jobs, applications or profiles work.',
        ],
      },
      {
        q: 'Will the AI guarantee that a lead becomes a customer?',
        a: [
          'No. Lead qualification produces analysis and prioritisation. It cannot guarantee that a prospect will buy anything.',
        ],
      },
      {
        q: 'Will AI make decisions for my sales team?',
        a: [
          'No. The product is designed to provide recommendations and qualification insights. Businesses remain responsible for reviewing leads and making their own sales decisions.',
        ],
      },
      {
        q: 'Can the AI invent information about a lead?',
        a: [
          'It is being designed not to. Where information is unavailable or cannot be verified, the system is intended to mark it as unavailable rather than present an assumption as a fact.',
        ],
      },
    ],
  },
  {
    title: '🛡️ Trust & safety',
    items: [
      {
        q: 'How do you prevent fake job listings?',
        a: [
          'Listings are moderated: our team can review employer accounts, remove job listings and suspend accounts. Reports about listings and accounts are recorded and reviewed by that team.',
          'Not every employer on the platform has been through verification, so treat the verified marker as one signal among several rather than a guarantee.',
        ],
      },
      {
        q: 'What should I do if a listing asks me for money?',
        a: [
          'Legitimate employers do not require candidates to pay to apply or to interview. Do not send payment, and do not share bank details, card numbers or identity documents in order to be considered.',
          'Tell us about the listing so our moderation team can review it. We can take listings down and suspend the accounts behind them. An in-app report button is not available yet, so contact us directly for now.',
        ],
      },
      {
        q: 'Does Connextion provide visa sponsorship or immigration services?',
        a: [
          'No. Connextion is a hiring marketplace. A job being open to international candidates does not mean the employer provides visa sponsorship or immigration support.',
          'Some employers mark a job as offering visa sponsorship, and you can filter for those. Discuss work authorisation directly with the employer. Connextion does not provide immigration or legal advice.',
        ],
      },
      {
        q: 'Can I apply for a job if I live in another country?',
        a: [
          'That depends on the employer and the position. Some jobs are open to candidates worldwide, others require candidates to live in a particular country, state, province or area.',
          'Always review the location and eligibility shown on the listing before applying.',
        ],
      },
    ],
  },
  {
    title: '🔐 Privacy & accounts',
    items: [
      {
        q: 'How is my data handled?',
        a: [
          'Your profile is stored on the platform so you can apply with it. When you apply to a job, that employer receives your application together with your profile, and anything you attach to it, such as a resume or cover letter.',
          'Employers can also start a conversation with a professional through the platform’s messaging. There are no per-field visibility controls on a profile today — treat what you put in your profile as information an employer may see.',
        ],
      },
      {
        q: 'Can employers find my professional profile?',
        a: [
          'There is no public directory or talent search on the platform today, so profiles are not browsable by anyone who visits the site.',
          'Employers see your profile when you apply to one of their jobs, and they can reach you through platform messaging. If a talent marketplace with profile search is added later, it will come with visibility controls and this answer will change with it.',
        ],
      },
      {
        q: 'Do I need an account to browse jobs?',
        a: [
          'No. Job search, location pages and job details are public. You need an account to apply, save jobs, message employers or post a job.',
        ],
      },
    ],
  },
]
</script>
