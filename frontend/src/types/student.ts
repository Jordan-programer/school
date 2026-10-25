export type GenderType = 'M' | 'F'

export type DocumentType = 'BI' | 'CEDULA' | 'ASSENTO' | 'PASSAPORTE' | 'PROVISORIO'

export type RelationshipType =
  | 'Pai'
  | 'Mãe'
  | 'Avô'
  | 'Avó'
  | 'Tio'
  | 'Tia'
  | 'Tutor Legal'
  | 'Outro'

export interface Guardian {
  id: string
  full_name: string
  id_document_type: DocumentType
  id_document_type_label: string
  id_document_number?: string
  phone_primary: string
  phone_secondary?: string
  email?: string
  profession?: string
  workplace?: string
  address?: string
  relationship_type?: RelationshipType | string
  is_primary_contact?: boolean
  is_financially_responsible?: boolean
  created_at?: string
}

export interface Student {
  id: string
  student_uid: string
  first_name: string
  last_name: string
  full_name: string
  birth_date: string
  birth_date_raw?: string
  age: number
  gender: GenderType
  gender_label: string
  id_document_type: DocumentType
  id_document_type_label: string
  id_document_number?: string
  has_special_needs: boolean
  special_needs_description?: string
  province: string
  municipality: string
  neighborhood?: string
  address?: string
  origin_school?: string
  photo_path?: string
  photo_url?: string
  is_active: boolean
  created_at: string
  updated_at?: string
  guardians: Guardian[]
}

export interface StudentFilters {
  search?: string
  gender?: GenderType | ''
  province?: string
  is_active?: boolean | ''
  has_special_needs?: boolean | ''
  page?: number
  per_page?: number
}

export interface CreateGuardianPayload {
  guardian_id?: string
  full_name: string
  id_document_type: DocumentType
  id_document_number?: string
  phone_primary: string
  phone_secondary?: string
  email?: string
  profession?: string
  workplace?: string
  address?: string
  relationship_type: RelationshipType
  is_primary_contact?: boolean
  is_financially_responsible?: boolean
}

export interface CreateStudentPayload {
  first_name: string
  last_name: string
  birth_date: string
  gender: GenderType
  id_document_type: DocumentType
  id_document_number?: string
  has_special_needs?: boolean
  special_needs_description?: string
  province: string
  municipality: string
  neighborhood?: string
  address?: string
  origin_school?: string
  guardians: CreateGuardianPayload[]
}

export interface UpdateStudentPayload {
  first_name?: string
  last_name?: string
  birth_date?: string
  gender?: GenderType
  id_document_type?: DocumentType
  id_document_number?: string
  has_special_needs?: boolean
  special_needs_description?: string
  province?: string
  municipality?: string
  neighborhood?: string
  address?: string
  origin_school?: string
}

export interface PaginatedStudentsResponse {
  data: Student[]
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
