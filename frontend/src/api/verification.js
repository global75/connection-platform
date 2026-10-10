import client from './client'

export const verificationApi = {
  // Employer
  employerStatus: ()     => client.get('/employer/verification'),
  employerVerify: (data) => client.post('/employer/verification', data),

  // Job seeker
  candidateStatus: ()     => client.get('/job-seeker/verification'),
  candidateVerify: (data) => client.post('/job-seeker/verification', data),

  // Admin
  queue:  (params)      => client.get('/admin/verifications', { params }),
  review: (id, data)    => client.patch(`/admin/verifications/${id}`, data),
}
