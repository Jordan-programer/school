import { api } from './api'
import type {
  Student,
  StudentFilters,
  CreateStudentPayload,
  UpdateStudentPayload,
  CreateGuardianPayload,
  PaginatedStudentsResponse,
} from '../types/student'

export const studentService = {
  async list(filters: StudentFilters = {}): Promise<PaginatedStudentsResponse> {
    const params: Record<string, any> = {}
    if (filters.search) params.search = filters.search
    if (filters.gender) params.gender = filters.gender
    if (filters.province) params.province = filters.province
    if (filters.is_active !== undefined && filters.is_active !== '') {
      params.is_active = filters.is_active
    }
    if (filters.has_special_needs !== undefined && filters.has_special_needs !== '') {
      params.has_special_needs = filters.has_special_needs
    }
    if (filters.page) params.page = filters.page
    if (filters.per_page) params.per_page = filters.per_page

    const response = await api.get<PaginatedStudentsResponse>('/students', { params })
    return response.data
  },

  async getById(id: string): Promise<Student> {
    const response = await api.get<{ student: Student }>(`/students/${id}`)
    return response.data.student
  },

  async create(payload: CreateStudentPayload): Promise<{ message: string; student: Student }> {
    const response = await api.post<{ message: string; student: Student }>('/students', payload)
    return response.data
  },

  async update(id: string, payload: UpdateStudentPayload): Promise<{ message: string; student: Student }> {
    const response = await api.put<{ message: string; student: Student }>(`/students/${id}`, payload)
    return response.data
  },

  async toggleStatus(id: string): Promise<{ message: string; is_active: boolean }> {
    const response = await api.patch<{ message: string; is_active: boolean }>(`/students/${id}/toggle-status`)
    return response.data
  },

  async delete(id: string): Promise<{ message: string }> {
    const response = await api.delete<{ message: string }>(`/students/${id}`)
    return response.data
  },

  async addGuardian(studentId: string, payload: CreateGuardianPayload): Promise<{ message: string; student: Student }> {
    const response = await api.post<{ message: string; student: Student }>(`/students/${studentId}/guardians`, payload)
    return response.data
  },

  async removeGuardian(studentId: string, guardianId: string): Promise<{ message: string }> {
    const response = await api.delete<{ message: string }>(`/students/${studentId}/guardians/${guardianId}`)
    return response.data
  },
}
