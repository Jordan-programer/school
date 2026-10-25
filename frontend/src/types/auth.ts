export type Role =
  | 'Director'
  | 'Subdirector Pedagógico'
  | 'Subdirector Administrativo'
  | 'Secretaria'
  | 'Professor'
  | 'Director de Turma'
  | 'Coordenador'
  | 'Tesoureiro'
  | 'Encarregado'
  | 'Aluno'
  | 'Gestor Municipal/Provincial'

export interface Staff {
  id: string
  staff_code: string
  full_name: string
  id_document_type: string
  id_document_number?: string
  position: string
  academic_degree?: string
  specialty?: string
  phone_primary?: string
  email?: string
  hire_date?: string
  is_active: boolean
}

export interface User {
  id: string
  name: string
  email: string
  phone?: string
  is_active: boolean
  last_login_at?: string
  created_at?: string
  roles: Role[]
  permissions: string[]
  staff?: Staff
}

export interface LoginResponse {
  message: string
  access_token: string
  token_type: string
  user: User
}
