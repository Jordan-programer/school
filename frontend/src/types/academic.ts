export interface School {
  id: string
  code: string
  name: string
  tax_id?: string
  contact_email?: string
  contact_phone?: string
  province: string
  municipality: string
  address?: string
  is_active: boolean
}

export interface Term {
  id: string
  term_number: number
  name: string
  start_date: string
  end_date: string
  grades_deadline?: string
  is_closed: boolean
}

export interface AcademicYear {
  id: string
  name: string
  start_date: string
  end_date: string
  is_active: boolean
  is_closed: boolean
  terms?: Term[]
}

export interface Grade {
  id: string
  code: string
  name: string
  order_index: number
  is_cycle_ending: boolean
  cycle?: {
    id: string
    name: string
    level_id?: string
  }
}

export interface Cycle {
  id: string
  cycle_number: number
  name: string
  order_index: number
  grades?: Grade[]
}

export interface Level {
  id: string
  code: string
  name: string
  order_index: number
  cycles?: Cycle[]
}

export interface Subject {
  id: string
  code: string
  name: string
}

export interface Shift {
  id: string
  name: string
  start_time: string
  end_time: string
}

export interface Classroom {
  id: string
  name: string
  capacity: number
  location_block?: string
}

export interface TeachingAssignment {
  id: string
  subject: {
    id: string
    code: string
    name: string
  }
  teacher: {
    id: string
    staff_code: string
    full_name: string
    specialty?: string
  }
}

export interface ClassGroup {
  id: string
  name: string
  max_students: number
  is_active: boolean
  academic_year_id?: string
  grade_id?: string
  shift_id?: string
  classroom_id?: string
  academic_year: {
    id: string
    name: string
    is_active: boolean
  }
  grade: Grade
  shift: Shift
  classroom?: Classroom | null
  teaching_assignments?: TeachingAssignment[]
}
