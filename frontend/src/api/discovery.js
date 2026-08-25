import client from './client'

/** Marketplace discovery: locations, categories and totals that really exist. */
export const discoveryApi = {
  locations:       (params) => client.get('/locations', { params }),
  location:        (slug)   => client.get(`/locations/${slug}`),
  suggestLocation: (q)      => client.get('/locations/suggest', { params: { q } }),
  categories:      ()       => client.get('/categories'),
  stats:           ()       => client.get('/stats'),
  filters:         (params) => client.get('/search-filters', { params }),
}
