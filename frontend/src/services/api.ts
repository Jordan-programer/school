import axios from 'axios'

export const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
})

// Injectar token Sanctum se presente no armazenamento local
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('sisgestesc_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Interceptar respostas para tratar erros 401 (não autorizado)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('sisgestesc_token')
      localStorage.removeItem('sisgestesc_user')
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)
