import client from './client'

export const servicesApi = {
  submitLocalizationLead: (data) => client.post('/services/localization', data),
}
