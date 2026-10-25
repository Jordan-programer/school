import { api } from './api'
import type {
  Attendance,
  AttendanceRecord,
  AttendanceSheetResponse,
  SaveAttendancePayload,
  StudentAttendanceSummary,
} from '../types/attendance'

export const attendanceService = {
  async getSheet(params: {
    class_group_id: string
    date?: string
    lesson_number?: number
    subject_id?: string
  }): Promise<AttendanceSheetResponse> {
    const response = await api.get<AttendanceSheetResponse>('/attendances/sheet', { params })
    return response.data
  },

  async save(payload: SaveAttendancePayload): Promise<{ message: string; attendance: Attendance }> {
    const response = await api.post<{ message: string; attendance: Attendance }>('/attendances', payload)
    return response.data
  },

  async syncOffline(
    mutations: SaveAttendancePayload[]
  ): Promise<{ message: string; synced_count: number; processed_ids: string[] }> {
    const response = await api.post<{ message: string; synced_count: number; processed_ids: string[] }>(
      '/attendances/sync-offline',
      { mutations }
    )
    return response.data
  },

  async justify(
    recordId: string,
    reason: string
  ): Promise<{ message: string; record: AttendanceRecord }> {
    const response = await api.patch<{ message: string; record: AttendanceRecord }>(
      `/attendance-records/${recordId}/justify`,
      { justification_reason: reason }
    )
    return response.data
  },

  async getStudentSummary(
    studentId: string,
    academicYearId?: string
  ): Promise<StudentAttendanceSummary> {
    const params = academicYearId ? { academic_year_id: academicYearId } : {}
    const response = await api.get<StudentAttendanceSummary>(
      `/attendances/student-summary/${studentId}`,
      { params }
    )
    return response.data
  },

  async list(params: Record<string, any> = {}): Promise<{ data: Attendance[] }> {
    const response = await api.get<{ data: Attendance[] }>('/attendances', { params })
    return response.data
  },
}
