import { api } from './api'
import type {
  Enrollment,
  EnrollmentFilters,
  CreateEnrollmentPayload,
  ConfirmEnrollmentPayload,
  AssignClassPayload,
  TransferPayload,
  PaginatedEnrollmentsResponse,
  EnrollmentStats,
} from '../types/enrollment'

export const enrollmentService = {
  async list(filters: EnrollmentFilters = {}): Promise<PaginatedEnrollmentsResponse> {
    const params: Record<string, any> = {}
    if (filters.search) params.search = filters.search
    if (filters.academic_year_id) params.academic_year_id = filters.academic_year_id
    if (filters.grade_id) params.grade_id = filters.grade_id
    if (filters.class_group_id) params.class_group_id = filters.class_group_id
    if (filters.enrollment_type) params.enrollment_type = filters.enrollment_type
    if (filters.status) params.status = filters.status
    if (filters.has_age_distortion !== undefined && filters.has_age_distortion !== '') {
      params.has_age_distortion = filters.has_age_distortion
    }
    if (filters.unassigned_class) params.unassigned_class = true
    if (filters.page) params.page = filters.page
    if (filters.per_page) params.per_page = filters.per_page

    const response = await api.get<PaginatedEnrollmentsResponse>('/enrollments', { params })
    return response.data
  },

  async getById(id: string): Promise<Enrollment> {
    const response = await api.get<{ enrollment: Enrollment }>(`/enrollments/${id}`)
    return response.data.enrollment
  },

  async create(payload: CreateEnrollmentPayload): Promise<{ message: string; enrollment: Enrollment }> {
    const response = await api.post<{ message: string; enrollment: Enrollment }>('/enrollments', payload)
    return response.data
  },

  async confirm(payload: ConfirmEnrollmentPayload): Promise<{ message: string; enrollment: Enrollment }> {
    const response = await api.post<{ message: string; enrollment: Enrollment }>('/enrollments/confirm', payload)
    return response.data
  },

  async assignClass(
    id: string,
    payload: AssignClassPayload
  ): Promise<{ message: string; enrollment: Enrollment }> {
    const response = await api.patch<{ message: string; enrollment: Enrollment }>(
      `/enrollments/${id}/assign-class`,
      payload
    )
    return response.data
  },

  async cancel(id: string): Promise<{ message: string }> {
    const response = await api.patch<{ message: string }>(`/enrollments/${id}/cancel`)
    return response.data
  },

  async transfer(id: string, payload: TransferPayload): Promise<{ message: string }> {
    const response = await api.post<{ message: string }>(`/enrollments/${id}/transfer`, payload)
    return response.data
  },

  async getStats(academicYearId?: string): Promise<EnrollmentStats> {
    const params = academicYearId ? { academic_year_id: academicYearId } : {}
    const response = await api.get<EnrollmentStats>('/enrollments/stats', { params })
    return response.data
  },
}
