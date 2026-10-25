export type AttendanceStatus =
  | 'PRESENTE'
  | 'FALTA_INJUSTIFICADA'
  | 'FALTA_JUSTIFICADA'
  | 'ATRASO'

export interface AttendanceRecord {
  id: string
  attendance_id: string
  student_id: string
  enrollment_id?: string
  status: AttendanceStatus
  status_label: string
  abbreviation: string
  justification_reason?: string | null
  justified_at?: string | null
  justified_by?: {
    id: string
    name: string
  }
  student?: {
    id: string
    student_uid: string
    full_name: string
    gender: string
  }
}

export interface Attendance {
  id: string
  class_group_id: string
  subject_id?: string | null
  academic_year_id: string
  date: string
  date_formatted: string
  lesson_number: number
  topic?: string | null
  notes?: string | null
  client_mutation_id?: string | null
  synced_at?: string | null
  class_group?: {
    id: string
    name: string
    grade?: {
      id: string
      name: string
    }
  }
  subject?: {
    id: string
    code: string
    name: string
  } | null
  recorded_by?: {
    id: string
    name: string
  }
  records: AttendanceRecord[]
  stats: {
    total: number
    present: number
    unjustified: number
    justified: number
    late: number
  }
  created_at: string
  updated_at?: string
}

export interface AttendanceSheetItem {
  student_id: string
  enrollment_id: string
  order_number: number
  student_uid: string
  full_name: string
  gender: string
  photo_url?: string | null
  current_status: AttendanceStatus
  current_abbreviation: string
  record_id?: string | null
  justification_reason?: string | null
}

export interface AttendanceSheetResponse {
  class_group: {
    id: string
    name: string
    grade_name?: string
    shift_name?: string
    total_students: number
  }
  session: {
    date: string
    date_formatted: string
    lesson_number: number
    subject_id?: string | null
    is_already_recorded: boolean
    attendance_id?: string | null
    topic?: string | null
    notes?: string | null
  }
  sheet: AttendanceSheetItem[]
}

export interface SaveAttendancePayload {
  class_group_id: string
  subject_id?: string | null
  academic_year_id?: string
  date: string
  lesson_number: number
  topic?: string | null
  notes?: string | null
  client_mutation_id?: string
  records: Array<{
    student_id: string
    enrollment_id?: string
    status: AttendanceStatus
    justification_reason?: string | null
  }>
}

export interface OfflineMutation {
  client_mutation_id: string
  created_at: string
  class_name: string
  date_str: string
  lesson_number: number
  payload: SaveAttendancePayload
}

export interface StudentAttendanceSummary {
  student: {
    id: string
    full_name: string
    student_uid: string
  }
  summary: {
    total_sessions: number
    present_count: number
    unjustified_count: number
    justified_count: number
    late_count: number
    attendance_rate: number
    unjustified_rate: number
    at_risk_of_exclusion: boolean
    risk_message?: string | null
  }
}
