import client from './client'

export const applicationsApi = {
  // Job seeker
  myApplications: (params) => client.get('/job-seeker/applications', { params }),
  getApplication: (id)     => client.get(`/job-seeker/applications/${id}`),
  withdraw:       (id)     => client.patch(`/job-seeker/applications/${id}/withdraw`),

  // Employer
  list:         (params)   => client.get('/employer/applications', { params }),
  show:         (id)       => client.get(`/employer/applications/${id}`),
  updateStatus: (id, data) => client.patch(`/employer/applications/${id}/status`, data),

  // AI lead qualification
  requalify:            (id) => client.post(`/employer/applications/${id}/qualify`),
  qualificationSummary: ()   => client.get('/employer/applications/qualification-summary'),
}
