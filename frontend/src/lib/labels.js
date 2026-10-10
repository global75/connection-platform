/**
 * Shared vocabulary for the three location concepts. Kept in one place so the
 * job card, search filters, posting form and profiles all say the same thing.
 */

/** How the work is performed. */
export const WORK_ARRANGEMENTS = [
  { value: 'on_site', label: 'On-site', icon: '🏢', hint: 'Work from the company location' },
  { value: 'hybrid',  label: 'Hybrid',  icon: '🔄', hint: 'Split between office and home' },
  { value: 'remote',  label: 'Remote',  icon: '🌎', hint: 'Work from anywhere allowed by the role' },
]

/** Who may apply. */
export const HIRING_SCOPES = [
  { value: 'local',              label: 'Local candidates',        hint: 'People who can get to this location' },
  { value: 'state',              label: 'State / province',        hint: 'People in the same state or province' },
  { value: 'national',           label: 'National',                hint: 'Anyone in the country the job is in' },
  { value: 'north_america',      label: 'North America',           hint: 'United States, Canada and Mexico' },
  { value: 'international',      label: 'International',           hint: 'Anyone, anywhere' },
  { value: 'specific_countries', label: 'Specific countries',      hint: 'Only the countries you choose' },
]

export const EMPLOYMENT_TYPES = [
  { value: 'full_time',  label: 'Full-time' },
  { value: 'part_time',  label: 'Part-time' },
  { value: 'contract',   label: 'Contract' },
  { value: 'freelance',  label: 'Freelance' },
  { value: 'internship', label: 'Internship' },
]

export const EXPERIENCE_LEVELS = [
  { value: 'entry',     label: 'Entry' },
  { value: 'mid',       label: 'Mid' },
  { value: 'senior',    label: 'Senior' },
  { value: 'lead',      label: 'Lead' },
  { value: 'executive', label: 'Executive' },
]

/** Plain-language search modes, so nobody has to know the filter model. */
export const SEARCH_MODES = [
  { value: 'anywhere',      label: 'Anywhere',      hint: 'Every job on the platform' },
  { value: 'near_me',       label: 'Near me',       hint: 'Jobs close to a location, plus remote roles open to it' },
  { value: 'nationwide',    label: 'Nationwide',    hint: 'Anywhere in one country' },
  { value: 'remote',        label: 'Remote',        hint: 'Remote roles only' },
  { value: 'international', label: 'International', hint: 'Roles open across countries' },
]

export const RADIUS_OPTIONS = [5, 10, 25, 50, 100]

/** Where a professional is willing to work. */
export const LOCATION_SCOPES = [
  { value: 'near_me',       label: 'Near me',                 hint: 'Within commuting distance' },
  { value: 'national',      label: 'Anywhere in my country',  hint: 'Including relocation or national remote roles' },
  { value: 'international', label: 'International',           hint: 'Open to roles in other countries' },
]

/** Where an employer hires. */
export const EMPLOYER_HIRING_SCOPES = [
  { value: 'local',         label: 'Local' },
  { value: 'national',      label: 'National' },
  { value: 'remote',        label: 'Remote' },
  { value: 'international', label: 'International' },
]

const byValue = (list) => Object.fromEntries(list.map((i) => [i.value, i]))

export const workArrangement = byValue(WORK_ARRANGEMENTS)
export const employmentType  = byValue(EMPLOYMENT_TYPES)
export const hiringScope     = byValue(HIRING_SCOPES)

export const labelFor = (map, value, fallback = '') => map[value]?.label ?? fallback
