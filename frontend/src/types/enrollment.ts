import type { Student } from './student'

export type EnrollmentType = 'NOVA_MATRICULA' | 'CONFIRMACAO' | 'TRANSFERENCIA'

export type EnrollmentStatus = 'CONFIRMADA' | 'PENDENTE' | 'CANCELADA' | 'TRANSFERIDO'

export interface StudentTransfer {
  id: string
  transfer_type: 'SAIDA' | 'ENTRADA'
  transfer_guide_number?: string
  destination_school?: string
  origin_school?: string
  reason?: string
  transfer_date: string
  registered_by?: {
    id: string
    name: string
  }
  created_at: string
}

export interface Enrollment {
  id: string
  enrollment_code: string
  enrollment_type: EnrollmentType
  enrollment_type_label: string
  status: EnrollmentStatus
  status_label: string
  order_number?: number
  age_at_enrollment: number
  has_age_distortion: boolean
  distortion_details?: string
  enrolled_at: string
  enrolled_at_date: string
  notes?: string
  student: Student
  academic_year: {
    id: string
    name: string
    is_active: boolean
  }
  grade: {
    id: string
    code: string
    name: string
    order_index: number
  }
  class_group?: {
    id: string
    name: string
    max_students: number
    shift?: {
      id: string
      name: string
    }
    classroom?: {
      id: string
      name: string
    }
  } | null
  school: {
    id: string
    code: string
    name: string
    province: string
    municipality: string
  }
  enrolled_by?: {
    id: string
    name: string
  }
  transfers?: StudentTransfer[]
  created_at: string
  updated_at?: string
}

export interface CreateEnrollmentPayload {
  student_id: string
  academic_year_id: string
  grade_id: string
  class_group_id?: string | null
  school_id?: string
  enrollment_type?: EnrollmentType
  notes?: string
}

export interface ConfirmEnrollmentPayload {
  student_id: string
  academic_year_id: string
  grade_id: string
  class_group_id?: string | null
  school_id?: string
  notes?: string
}

export interface AssignClassPayload {
  class_group_id?: string | null
  force_capacity?: boolean
}

export interface TransferPayload {
  destination_school: string
  transfer_guide_number?: string
  reason?: string
  transfer_date: string
}

export interface EnrollmentFilters {
  search?: string
  academic_year_id?: string
  grade_id?: string
  class_group_id?: string
  enrollment_type?: string
  status?: string
  has_age_distortion?: boolean | ''
  unassigned_class?: boolean
  page?: number
  per_page?: number
}

export interface PaginatedEnrollmentsResponse {
  data: Enrollment[]
  links: {
    first: string
    last: string
    prev: string | null
    next: string | null
  }
  meta: {
    current_page: number
    from: number
    last_page: number
    per_page: number
    to: number
    total: number
  }
}

export interface EnrollmentStats {
  academic_year_id: string
  total_confirmed: number
  total_new: number
  total_renewals: number
  total_transfers: number
  total_distortion: number
  distortion_percentage: number
  unassigned_count: number
  by_grade: Array<{
    grade_id: string
    grade_name: string
    count: number
  }>
}
