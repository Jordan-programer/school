import React, { useState, useEffect, useCallback } from 'react'
import {
  GraduationCap,
  Plus,
  Search,
  Filter,
  Users,
  ShieldCheck,
  ShieldAlert,
  AlertCircle,
  Eye,
  CheckCircle,
  X,
  Phone,
  Mail,
  UserCheck,
  UserX,
  Sparkles,
  FileText,
} from 'lucide-react'
import { studentService } from '../../services/studentService'
import type {
  Student,
  StudentFilters,
  CreateStudentPayload,
  CreateGuardianPayload,
  GenderType,
  DocumentType,
  RelationshipType,
} from '../../types/student'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'

const PROVINCES_ANGOLA = [
  'Bengo',
  'Benguela',
  'Bié',
  'Cabinda',
  'Cuando Cubango',
  'Cuanza Norte',
  'Cuanza Sul',
  'Cunene',
  'Huambo',
  'Huíla',
  'Luanda',
  'Lunda Norte',
  'Lunda Sul',
  'Malanje',
  'Moxico',
  'Namibe',
  'Uíge',
  'Zaire',
]

const RELATIONSHIPS: RelationshipType[] = [
  'Pai',
  'Mãe',
  'Avô',
  'Avó',
  'Tio',
  'Tia',
  'Tutor Legal',
  'Outro',
]

export const StudentsScreen: React.FC = () => {
  const [students, setStudents] = useState<Student[]>([])
  const [loading, setLoading] = useState<boolean>(true)
  const [totalCount, setTotalCount] = useState<number>(0)
  const [currentPage, setCurrentPage] = useState<number>(1)
  const [lastPage, setLastPage] = useState<number>(1)

  // Filtros
  const [filters, setFilters] = useState<StudentFilters>({
    search: '',
    gender: '',
    province: '',
    is_active: '',
    has_special_needs: '',
  })

  // Mensagens de feedback
  const [successMessage, setSuccessMessage] = useState<string | null>(null)
  const [errorMessage, setErrorMessage] = useState<string | null>(null)

  // Modais
  const [isCreateModalOpen, setIsCreateModalOpen] = useState<boolean>(false)
  const [selectedStudent, setSelectedStudent] = useState<Student | null>(null)
  const [isDetailModalOpen, setIsDetailModalOpen] = useState<boolean>(false)
  const [isAddGuardianModalOpen, setIsAddGuardianModalOpen] = useState<boolean>(false)

  // Submissão
  const [submitting, setSubmitting] = useState<boolean>(false)

  // Novo Aluno Form State
  const initialStudentForm: CreateStudentPayload = {
    first_name: '',
    last_name: '',
    birth_date: '',
    gender: 'M',
    id_document_type: 'BI',
    id_document_number: '',
    has_special_needs: false,
    special_needs_description: '',
    province: 'Luanda',
    municipality: 'Luanda',
    neighborhood: '',
    address: '',
    origin_school: '',
    guardians: [
      {
        full_name: '',
        id_document_type: 'BI',
        id_document_number: '',
        phone_primary: '',
        phone_secondary: '',
        email: '',
        profession: '',
        workplace: '',
        address: '',
        relationship_type: 'Pai',
        is_primary_contact: true,
        is_financially_responsible: true,
      },
    ],
  }

  const [studentForm, setStudentForm] = useState<CreateStudentPayload>(initialStudentForm)
  const [activeTab, setActiveTab] = useState<'aluno' | 'encarregado'>('aluno')

  // Novo Encarregado para aluno existente
  const [newGuardianForm, setNewGuardianForm] = useState<CreateGuardianPayload>({
    full_name: '',
    id_document_type: 'BI',
    id_document_number: '',
    phone_primary: '',
    phone_secondary: '',
    email: '',
    profession: '',
    workplace: '',
    address: '',
    relationship_type: 'Mãe',
    is_primary_contact: false,
    is_financially_responsible: false,
  })

  const loadStudents = useCallback(async () => {
    setLoading(true)
    try {
      const response = await studentService.list({
        ...filters,
        page: currentPage,
        per_page: 15,
      })
      setStudents(response.data)
      setTotalCount(response.meta.total)
      setLastPage(response.meta.last_page)
    } catch (err: any) {
      setErrorMessage(
        err.response?.data?.message || 'Erro ao carregar a lista de alunos da escola.'
      )
    } finally {
      setLoading(false)
    }
  }, [filters, currentPage])

  useEffect(() => {
    loadStudents()
  }, [loadStudents])

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setCurrentPage(1)
    loadStudents()
  }

  const handleToggleStatus = async (student: Student) => {
    try {
      const response = await studentService.toggleStatus(student.id)
      setSuccessMessage(response.message)
      setStudents((prev) =>
        prev.map((s) => (s.id === student.id ? { ...s, is_active: response.is_active } : s))
      )
      if (selectedStudent && selectedStudent.id === student.id) {
        setSelectedStudent({ ...selectedStudent, is_active: response.is_active })
      }
      setTimeout(() => setSuccessMessage(null), 4000)
    } catch (err: any) {
      setErrorMessage(
        err.response?.data?.message || 'Não foi possível alterar o estado da matrícula.'
      )
      setTimeout(() => setErrorMessage(null), 4000)
    }
  }

  const handleOpenDetail = async (student: Student) => {
    try {
      const detailed = await studentService.getById(student.id)
      setSelectedStudent(detailed)
      setIsDetailModalOpen(true)
    } catch {
      setSelectedStudent(student)
      setIsDetailModalOpen(true)
    }
  }

  const handleCreateStudent = async (e: React.FormEvent) => {
    e.preventDefault()
    setSubmitting(true)
    setErrorMessage(null)

    try {
      const response = await studentService.create(studentForm)
      setSuccessMessage(response.message)
      setIsCreateModalOpen(false)
      setStudentForm(initialStudentForm)
      setActiveTab('aluno')
      loadStudents()
      setTimeout(() => setSuccessMessage(null), 5000)
    } catch (err: any) {
      const msg =
        err.response?.data?.message ||
        (err.response?.data?.errors
          ? Object.values(err.response.data.errors).flat().join(' ')
          : 'Falha ao registar novo aluno. Verifique os campos.')
      setErrorMessage(msg)
    } finally {
      setSubmitting(false)
    }
  }

  const handleAddGuardian = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!selectedStudent) return

    setSubmitting(true)
    setErrorMessage(null)

    try {
      const response = await studentService.addGuardian(selectedStudent.id, newGuardianForm)
      setSuccessMessage(response.message)
      setSelectedStudent(response.student)
      setIsAddGuardianModalOpen(false)
      setNewGuardianForm({
        full_name: '',
        id_document_type: 'BI',
        id_document_number: '',
        phone_primary: '',
        phone_secondary: '',
        email: '',
        profession: '',
        workplace: '',
        address: '',
        relationship_type: 'Mãe',
        is_primary_contact: false,
        is_financially_responsible: false,
      })
      loadStudents()
      setTimeout(() => setSuccessMessage(null), 5000)
    } catch (err: any) {
      const msg =
        err.response?.data?.message ||
        (err.response?.data?.errors
          ? Object.values(err.response.data.errors).flat().join(' ')
          : 'Erro ao associar encarregado.')
      setErrorMessage(msg)
    } finally {
      setSubmitting(false)
    }
  }

  const handleRemoveGuardian = async (guardianId: string) => {
    if (!selectedStudent) return
    if (!window.confirm('Tem a certeza que deseja desassociar este encarregado de educação?')) return

    try {
      const response = await studentService.removeGuardian(selectedStudent.id, guardianId)
      setSuccessMessage(response.message)
      const refreshed = await studentService.getById(selectedStudent.id)
      setSelectedStudent(refreshed)
      loadStudents()
      setTimeout(() => setSuccessMessage(null), 4000)
    } catch (err: any) {
      setErrorMessage(err.response?.data?.message || 'Erro ao remover encarregado.')
      setTimeout(() => setErrorMessage(null), 4000)
    }
  }

  // Estatísticas Rápidas
  const activeCount = students.filter((s) => s.is_active).length
  const inactiveCount = students.filter((s) => !s.is_active).length
  const specialNeedsCount = students.filter((s) => s.has_special_needs).length

  return (
    <div className="space-y-6">
      {/* Cabeçalho da Página */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-linha">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="font-serif text-2xl font-bold text-mata-escura">
              Alunos & Encarregados de Educação
            </h1>
            <Badge variant="folha">Módulo 3</Badge>
          </div>
          <p className="text-xs text-tinta-suave mt-1">
            Ficha biográfica, número de processo (UID angolano), tutela legal e contactos de encarregados.
          </p>
        </div>

        <Button
          onClick={() => {
            setStudentForm(initialStudentForm)
            setActiveTab('aluno')
            setIsCreateModalOpen(true)
          }}
          className="bg-mata hover:bg-mata-escura text-white shadow-sm flex items-center gap-2 text-xs"
        >
          <Plus className="w-4 h-4" />
          <span>Registar Novo Aluno</span>
        </Button>
      </div>

      {/* Alertas de Notificação */}
      {successMessage && (
        <div className="p-3 bg-folha/10 border border-folha/30 rounded flex items-center justify-between text-xs text-mata-escura">
          <div className="flex items-center gap-2">
            <CheckCircle className="w-4 h-4 text-folha shrink-0" />
            <span>{successMessage}</span>
          </div>
          <button onClick={() => setSuccessMessage(null)} className="text-tinta-suave hover:text-tinta">
            <X className="w-4 h-4" />
          </button>
        </div>
      )}

      {errorMessage && (
        <div className="p-3 bg-tijolo/10 border border-tijolo/30 rounded flex items-center justify-between text-xs text-tijolo">
          <div className="flex items-center gap-2">
            <AlertCircle className="w-4 h-4 text-tijolo shrink-0" />
            <span>{errorMessage}</span>
          </div>
          <button onClick={() => setErrorMessage(null)} className="text-tijolo/80 hover:text-tijolo">
            <X className="w-4 h-4" />
          </button>
        </div>
      )}

      {/* Cartões de Estatísticas Rápidas */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="p-4 bg-superficie rounded border border-linha shadow-sm">
          <div className="flex items-center justify-between text-tinta-suave text-xs mb-1">
            <span>Total Cadastrados</span>
            <Users className="w-4 h-4 text-mata" />
          </div>
          <div className="font-serif text-2xl font-bold text-mata-escura">
            {totalCount}
          </div>
          <div className="text-[10px] text-tinta-suave mt-1">Alunos no sistema</div>
        </div>

        <div className="p-4 bg-superficie rounded border border-linha shadow-sm">
          <div className="flex items-center justify-between text-tinta-suave text-xs mb-1">
            <span>Matrículas Activas</span>
            <ShieldCheck className="w-4 h-4 text-folha" />
          </div>
          <div className="font-serif text-2xl font-bold text-mata-escura">
            {activeCount}
          </div>
          <div className="text-[10px] text-folha mt-1">Regulares na lista actual</div>
        </div>

        <div className="p-4 bg-superficie rounded border border-linha shadow-sm">
          <div className="flex items-center justify-between text-tinta-suave text-xs mb-1">
            <span>Desactivados / Suspensos</span>
            <ShieldAlert className="w-4 h-4 text-tijolo" />
          </div>
          <div className="font-serif text-2xl font-bold text-tijolo">
            {inactiveCount}
          </div>
          <div className="text-[10px] text-tijolo/80 mt-1">Matrícula suspensa</div>
        </div>

        <div className="p-4 bg-superficie rounded border border-linha shadow-sm">
          <div className="flex items-center justify-between text-tinta-suave text-xs mb-1">
            <span>Apoio Pedagógico (NEE)</span>
            <Sparkles className="w-4 h-4 text-girassol" />
          </div>
          <div className="font-serif text-2xl font-bold text-mata-escura">
            {specialNeedsCount}
          </div>
          <div className="text-[10px] text-tinta-suave mt-1">Necessidades especiais</div>
        </div>
      </div>

      {/* Barra de Filtros e Pesquisa */}
      <div className="p-4 bg-superficie rounded border border-linha shadow-sm space-y-3">
        <form onSubmit={handleSearchSubmit} className="flex flex-col md:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-tinta-suave" />
            <input
              type="text"
              placeholder="Pesquisar por nome do aluno, Nº de processo (UID) ou documento..."
              value={filters.search || ''}
              onChange={(e) => setFilters((prev) => ({ ...prev, search: e.target.value }))}
              className="w-full pl-9 pr-3 py-2 text-xs rounded border border-linha bg-papel text-tinta placeholder:text-tinta-suave/60 focus:outline-none focus:border-mata"
            />
          </div>

          <div className="flex flex-wrap items-center gap-2">
            <select
              value={filters.gender || ''}
              onChange={(e) => {
                setFilters((prev) => ({ ...prev, gender: e.target.value as GenderType | '' }))
                setCurrentPage(1)
              }}
              className="px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
            >
              <option value="">Género: Todos</option>
              <option value="M">Masculino</option>
              <option value="F">Feminino</option>
            </select>

            <select
              value={filters.province || ''}
              onChange={(e) => {
                setFilters((prev) => ({ ...prev, province: e.target.value }))
                setCurrentPage(1)
              }}
              className="px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
            >
              <option value="">Província: Todas</option>
              {PROVINCES_ANGOLA.map((prov) => (
                <option key={prov} value={prov}>
                  {prov}
                </option>
              ))}
            </select>

            <select
              value={filters.is_active === '' ? '' : String(filters.is_active)}
              onChange={(e) => {
                const val = e.target.value === '' ? '' : e.target.value === 'true'
                setFilters((prev) => ({ ...prev, is_active: val }))
                setCurrentPage(1)
              }}
              className="px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
            >
              <option value="">Estado: Todos</option>
              <option value="true">Activos</option>
              <option value="false">Desactivados</option>
            </select>

            <Button type="submit" variant="secondary" className="text-xs flex items-center gap-1.5">
              <Filter className="w-3.5 h-3.5" />
              <span>Filtrar</span>
            </Button>
          </div>
        </form>
      </div>

      {/* Tabela de Alunos */}
      <div className="bg-superficie rounded border border-linha shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-[#FAF7F2] border-b border-linha text-tinta-suave uppercase text-[10px] tracking-wider">
              <tr>
                <th className="py-3 px-4">Nº Processo</th>
                <th className="py-3 px-4">Nome Completo</th>
                <th className="py-3 px-4">Nascimento / Idade</th>
                <th className="py-3 px-4">Documento</th>
                <th className="py-3 px-4">Naturalidade</th>
                <th className="py-3 px-4">Encarregado Principal</th>
                <th className="py-3 px-4 text-center">Estado</th>
                <th className="py-3 px-4 text-right">Acções</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-linha text-tinta">
              {loading ? (
                <tr>
                  <td colSpan={8} className="py-12 text-center text-tinta-suave">
                    <div className="flex flex-col items-center gap-2">
                      <div className="w-6 h-6 border-2 border-mata border-t-transparent rounded-full animate-spin" />
                      <span>A carregar fichas de alunos...</span>
                    </div>
                  </td>
                </tr>
              ) : students.length === 0 ? (
                <tr>
                  <td colSpan={8} className="py-12 text-center text-tinta-suave">
                    <GraduationCap className="w-8 h-8 mx-auto mb-2 text-linha" />
                    <p className="font-medium">Nenhum aluno encontrado.</p>
                    <p className="text-[11px] mt-0.5">
                      Registe novos alunos ou ajuste os filtros de pesquisa.
                    </p>
                  </td>
                </tr>
              ) : (
                students.map((student) => {
                  const primaryGuardian =
                    student.guardians?.find((g) => g.is_primary_contact) ||
                    student.guardians?.[0]

                  return (
                    <tr key={student.id} className="hover:bg-papel/60 transition-colors">
                      {/* UID */}
                      <td className="py-3 px-4 font-mono font-medium text-mata">
                        <span className="px-1.5 py-0.5 bg-mata/10 rounded border border-mata/20">
                          {student.student_uid}
                        </span>
                      </td>

                      {/* Nome e Género */}
                      <td className="py-3 px-4">
                        <div className="font-semibold text-mata-escura flex items-center gap-1.5">
                          <span>{student.full_name}</span>
                          {student.has_special_needs && (
                            <span
                              title={`Necessidades Especiais: ${student.special_needs_description || 'Apoio Especial'}`}
                              className="px-1 rounded bg-girassol/20 text-girassol text-[9px] font-bold"
                            >
                              NEE
                            </span>
                          )}
                        </div>
                        <div className="text-[10px] text-tinta-suave">
                          {student.gender_label || (student.gender === 'M' ? 'Masculino' : 'Feminino')}
                        </div>
                      </td>

                      {/* Nascimento e Idade */}
                      <td className="py-3 px-4">
                        <div className="font-mono">{student.birth_date}</div>
                        <div className="text-[10px] text-tinta-suave">{student.age} anos</div>
                      </td>

                      {/* Documento */}
                      <td className="py-3 px-4">
                        <div className="text-[11px] font-medium">
                          {student.id_document_type_label || student.id_document_type}
                        </div>
                        <div className="font-mono text-[10px] text-tinta-suave">
                          {student.id_document_number || 'Sem número provisório'}
                        </div>
                      </td>

                      {/* Localização */}
                      <td className="py-3 px-4">
                        <div>{student.province}</div>
                        <div className="text-[10px] text-tinta-suave">{student.municipality}</div>
                      </td>

                      {/* Encarregado Principal */}
                      <td className="py-3 px-4">
                        {primaryGuardian ? (
                          <div>
                            <div className="font-medium text-tinta">
                              {primaryGuardian.full_name}
                              <span className="text-[10px] text-tinta-suave ml-1 font-normal">
                                ({primaryGuardian.relationship_type})
                              </span>
                            </div>
                            <div className="text-[10px] font-mono text-mata flex items-center gap-1">
                              <Phone className="w-2.5 h-2.5" />
                              <span>{primaryGuardian.phone_primary}</span>
                            </div>
                          </div>
                        ) : (
                          <span className="text-[11px] text-tijolo italic">Sem encarregado</span>
                        )}
                      </td>

                      {/* Estado */}
                      <td className="py-3 px-4 text-center">
                        <Badge variant={student.is_active ? 'folha' : 'tijolo'}>
                          {student.is_active ? 'Activo' : 'Desactivado'}
                        </Badge>
                      </td>

                      {/* Acções */}
                      <td className="py-3 px-4 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <button
                            onClick={() => handleOpenDetail(student)}
                            title="Ver Ficha Biográfica"
                            className="p-1.5 text-tinta-suave hover:text-mata hover:bg-mata/10 rounded transition-colors"
                          >
                            <Eye className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => handleToggleStatus(student)}
                            title={student.is_active ? 'Desactivar Matrícula' : 'Activar Matrícula'}
                            className={`p-1.5 rounded transition-colors ${
                              student.is_active
                                ? 'text-tinta-suave hover:text-tijolo hover:bg-tijolo/10'
                                : 'text-tinta-suave hover:text-folha hover:bg-folha/10'
                            }`}
                          >
                            {student.is_active ? (
                              <UserX className="w-4 h-4" />
                            ) : (
                              <UserCheck className="w-4 h-4" />
                            )}
                          </button>
                        </div>
                      </td>
                    </tr>
                  )
                })
              )}
            </tbody>
          </table>
        </div>

        {/* Paginação */}
        {lastPage > 1 && (
          <div className="p-3 bg-[#FAF7F2] border-t border-linha flex items-center justify-between text-xs text-tinta-suave">
            <div>
              A mostrar página {currentPage} de {lastPage} ({totalCount} alunos no total)
            </div>
            <div className="flex items-center gap-1">
              <button
                disabled={currentPage === 1}
                onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                className="px-2.5 py-1 rounded border border-linha bg-superficie hover:bg-papel disabled:opacity-40 disabled:cursor-not-allowed"
              >
                Anterior
              </button>
              <button
                disabled={currentPage === lastPage}
                onClick={() => setCurrentPage((p) => Math.min(lastPage, p + 1))}
                className="px-2.5 py-1 rounded border border-linha bg-superficie hover:bg-papel disabled:opacity-40 disabled:cursor-not-allowed"
              >
                Próxima
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Modal: Registar Novo Aluno */}
      {isCreateModalOpen && (
        <div className="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-superficie rounded-lg border border-linha shadow-xl w-full max-w-3xl my-8 overflow-hidden">
            <div className="px-6 py-4 bg-mata-escura text-white flex items-center justify-between">
              <div className="flex items-center gap-2">
                <GraduationCap className="w-5 h-5 text-girassol" />
                <h3 className="font-serif font-bold text-base">Ficha de Inscrição de Aluno</h3>
              </div>
              <button
                onClick={() => setIsCreateModalOpen(false)}
                className="text-white/70 hover:text-white"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Abas do Formulário */}
            <div className="flex border-b border-linha bg-[#FAF7F2] text-xs">
              <button
                type="button"
                onClick={() => setActiveTab('aluno')}
                className={`flex-1 py-3 px-4 font-medium flex items-center justify-center gap-2 border-b-2 transition-colors ${
                  activeTab === 'aluno'
                    ? 'border-mata text-mata bg-superficie font-semibold'
                    : 'border-transparent text-tinta-suave hover:text-tinta'
                }`}
              >
                <FileText className="w-4 h-4" />
                <span>1. Dados do Aluno</span>
              </button>
              <button
                type="button"
                onClick={() => setActiveTab('encarregado')}
                className={`flex-1 py-3 px-4 font-medium flex items-center justify-center gap-2 border-b-2 transition-colors ${
                  activeTab === 'encarregado'
                    ? 'border-mata text-mata bg-superficie font-semibold'
                    : 'border-transparent text-tinta-suave hover:text-tinta'
                }`}
              >
                <Users className="w-4 h-4" />
                <span>2. Encarregado de Educação Principal</span>
              </button>
            </div>

            <form onSubmit={handleCreateStudent} className="p-6 space-y-4">
              {activeTab === 'aluno' && (
                <div className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">
                        Primeiro Nome *
                      </label>
                      <Input
                        required
                        placeholder="Ex: Manuel"
                        value={studentForm.first_name}
                        onChange={(e) =>
                          setStudentForm((prev) => ({ ...prev, first_name: e.target.value }))
                        }
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">
                        Apelido / Sobrenome *
                      </label>
                      <Input
                        required
                        placeholder="Ex: da Costa"
                        value={studentForm.last_name}
                        onChange={(e) =>
                          setStudentForm((prev) => ({ ...prev, last_name: e.target.value }))
                        }
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">
                        Data de Nascimento *
                      </label>
                      <Input
                        type="date"
                        required
                        value={studentForm.birth_date}
                        onChange={(e) =>
                          setStudentForm((prev) => ({ ...prev, birth_date: e.target.value }))
                        }
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">
                        Género *
                      </label>
                      <select
                        required
                        value={studentForm.gender}
                        onChange={(e) =>
                          setStudentForm((prev) => ({
                            ...prev,
                            gender: e.target.value as GenderType,
                          }))
                        }
                        className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                      >
                        <option value="M">Masculino</option>
                        <option value="F">Feminino</option>
                      </select>
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">
                        Tipo de Documento *
                      </label>
                      <select
                        required
                        value={studentForm.id_document_type}
                        onChange={(e) =>
                          setStudentForm((prev) => ({
                            ...prev,
                            id_document_type: e.target.value as DocumentType,
                          }))
                        }
                        className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                      >
                        <option value="BI">Bilhete de Identidade (BI)</option>
                        <option value="CEDULA">Cédula Pessoal</option>
                        <option value="ASSENTO">Assento de Nascimento</option>
                        <option value="PASSAPORTE">Passaporte</option>
                        <option value="PROVISORIO">Número Provisório</option>
                      </select>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">
                        Número do Documento (ou em branco se provisório)
                      </label>
                      <Input
                        placeholder="Ex: 004812345LA041"
                        value={studentForm.id_document_number || ''}
                        onChange={(e) =>
                          setStudentForm((prev) => ({
                            ...prev,
                            id_document_number: e.target.value,
                          }))
                        }
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">
                        Escola de Origem (caso transferência)
                      </label>
                      <Input
                        placeholder="Ex: Colégio Sagrada Esperança"
                        value={studentForm.origin_school || ''}
                        onChange={(e) =>
                          setStudentForm((prev) => ({
                            ...prev,
                            origin_school: e.target.value,
                          }))
                        }
                      />
                    </div>
                  </div>

                  {/* Localização */}
                  <div className="p-3 bg-[#FAF7F2] rounded border border-linha space-y-3">
                    <span className="text-[11px] font-semibold text-mata-escura uppercase tracking-wider block">
                      Localização e Morada
                    </span>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                      <div>
                        <label className="block text-xs font-medium text-tinta mb-1">
                          Província *
                        </label>
                        <select
                          required
                          value={studentForm.province}
                          onChange={(e) =>
                            setStudentForm((prev) => ({ ...prev, province: e.target.value }))
                          }
                          className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                        >
                          {PROVINCES_ANGOLA.map((prov) => (
                            <option key={prov} value={prov}>
                              {prov}
                            </option>
                          ))}
                        </select>
                      </div>
                      <div>
                        <label className="block text-xs font-medium text-tinta mb-1">
                          Município *
                        </label>
                        <Input
                          required
                          placeholder="Ex: Luanda"
                          value={studentForm.municipality}
                          onChange={(e) =>
                            setStudentForm((prev) => ({ ...prev, municipality: e.target.value }))
                          }
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-medium text-tinta mb-1">Bairro</label>
                        <Input
                          placeholder="Ex: Alvalade"
                          value={studentForm.neighborhood || ''}
                          onChange={(e) =>
                            setStudentForm((prev) => ({ ...prev, neighborhood: e.target.value }))
                          }
                        />
                      </div>
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">
                        Endereço Completo
                      </label>
                      <Input
                        placeholder="Ex: Rua Comandante Gika, Casa 12"
                        value={studentForm.address || ''}
                        onChange={(e) =>
                          setStudentForm((prev) => ({ ...prev, address: e.target.value }))
                        }
                      />
                    </div>
                  </div>

                  {/* Apoio Especial */}
                  <div className="p-3 bg-papel rounded border border-linha space-y-2">
                    <label className="flex items-center gap-2 cursor-pointer text-xs font-medium text-tinta">
                      <input
                        type="checkbox"
                        checked={studentForm.has_special_needs}
                        onChange={(e) =>
                          setStudentForm((prev) => ({
                            ...prev,
                            has_special_needs: e.target.checked,
                          }))
                        }
                        className="rounded border-linha text-mata focus:ring-mata"
                      />
                      <span>Aluno com Necessidades Educativas Especiais (NEE)</span>
                    </label>
                    {studentForm.has_special_needs && (
                      <Input
                        placeholder="Descrição da necessidade (ex: Baixa visão, dislexia, apoio motor)..."
                        value={studentForm.special_needs_description || ''}
                        onChange={(e) =>
                          setStudentForm((prev) => ({
                            ...prev,
                            special_needs_description: e.target.value,
                          }))
                        }
                      />
                    )}
                  </div>

                  <div className="flex justify-end pt-2">
                    <Button
                      type="button"
                      onClick={() => setActiveTab('encarregado')}
                      className="bg-mata text-white text-xs"
                    >
                      Avançar para Encarregado →
                    </Button>
                  </div>
                </div>
              )}

              {activeTab === 'encarregado' && (
                <div className="space-y-4">
                  <div className="p-3 bg-girassol/10 border border-girassol/30 rounded text-xs text-mata-escura">
                    <strong>Atenção:</strong> Conforme o regulamento escolar angolano, a matrícula de alunos menores de idade exige obrigatoriamente a identificação e contacto telefónico de pelo menos um encarregado de educação.
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">
                        Nome Completo do Encarregado *
                      </label>
                      <Input
                        required
                        placeholder="Ex: Domingos António Capitão"
                        value={studentForm.guardians[0].full_name}
                        onChange={(e) => {
                          const val = e.target.value
                          setStudentForm((prev) => {
                            const updated = [...prev.guardians]
                            updated[0].full_name = val
                            return { ...prev, guardians: updated }
                          })
                        }}
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">
                        Grau de Parentesco *
                      </label>
                      <select
                        required
                        value={studentForm.guardians[0].relationship_type}
                        onChange={(e) => {
                          const val = e.target.value as RelationshipType
                          setStudentForm((prev) => {
                            const updated = [...prev.guardians]
                            updated[0].relationship_type = val
                            return { ...prev, guardians: updated }
                          })
                        }}
                        className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                      >
                        {RELATIONSHIPS.map((rel) => (
                          <option key={rel} value={rel}>
                            {rel}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">
                        Telefone Principal *
                      </label>
                      <Input
                        required
                        placeholder="Ex: +244923456789"
                        value={studentForm.guardians[0].phone_primary}
                        onChange={(e) => {
                          const val = e.target.value
                          setStudentForm((prev) => {
                            const updated = [...prev.guardians]
                            updated[0].phone_primary = val
                            return { ...prev, guardians: updated }
                          })
                        }}
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">
                        Telefone Secundário
                      </label>
                      <Input
                        placeholder="Ex: +244912345678"
                        value={studentForm.guardians[0].phone_secondary || ''}
                        onChange={(e) => {
                          const val = e.target.value
                          setStudentForm((prev) => {
                            const updated = [...prev.guardians]
                            updated[0].phone_secondary = val
                            return { ...prev, guardians: updated }
                          })
                        }}
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">
                        E-mail de Contacto
                      </label>
                      <Input
                        type="email"
                        placeholder="Ex: encarregado@email.com"
                        value={studentForm.guardians[0].email || ''}
                        onChange={(e) => {
                          const val = e.target.value
                          setStudentForm((prev) => {
                            const updated = [...prev.guardians]
                            updated[0].email = val
                            return { ...prev, guardians: updated }
                          })
                        }}
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">
                        Tipo de Documento
                      </label>
                      <select
                        value={studentForm.guardians[0].id_document_type}
                        onChange={(e) => {
                          const val = e.target.value as DocumentType
                          setStudentForm((prev) => {
                            const updated = [...prev.guardians]
                            updated[0].id_document_type = val
                            return { ...prev, guardians: updated }
                          })
                        }}
                        className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                      >
                        <option value="BI">Bilhete de Identidade (BI)</option>
                        <option value="PASSAPORTE">Passaporte</option>
                        <option value="CEDULA">Cédula</option>
                        <option value="PROVISORIO">Provisório</option>
                      </select>
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">
                        Nº de Documento
                      </label>
                      <Input
                        placeholder="Ex: 003928123LA032"
                        value={studentForm.guardians[0].id_document_number || ''}
                        onChange={(e) => {
                          const val = e.target.value
                          setStudentForm((prev) => {
                            const updated = [...prev.guardians]
                            updated[0].id_document_number = val
                            return { ...prev, guardians: updated }
                          })
                        }}
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">Profissão</label>
                      <Input
                        placeholder="Ex: Engenheiro Civil"
                        value={studentForm.guardians[0].profession || ''}
                        onChange={(e) => {
                          const val = e.target.value
                          setStudentForm((prev) => {
                            const updated = [...prev.guardians]
                            updated[0].profession = val
                            return { ...prev, guardians: updated }
                          })
                        }}
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-tinta mb-1">
                        Local de Trabalho
                      </label>
                      <Input
                        placeholder="Ex: Ministério das Obras Públicas"
                        value={studentForm.guardians[0].workplace || ''}
                        onChange={(e) => {
                          const val = e.target.value
                          setStudentForm((prev) => {
                            const updated = [...prev.guardians]
                            updated[0].workplace = val
                            return { ...prev, guardians: updated }
                          })
                        }}
                      />
                    </div>
                  </div>

                  <div className="p-3 bg-papel rounded border border-linha flex items-center gap-6">
                    <label className="flex items-center gap-2 cursor-pointer text-xs font-medium text-tinta">
                      <input
                        type="checkbox"
                        checked={studentForm.guardians[0].is_financially_responsible}
                        onChange={(e) => {
                          const val = e.target.checked
                          setStudentForm((prev) => {
                            const updated = [...prev.guardians]
                            updated[0].is_financially_responsible = val
                            return { ...prev, guardians: updated }
                          })
                        }}
                        className="rounded border-linha text-mata focus:ring-mata"
                      />
                      <span>Responsável Financeiro pelas Propinas e Emolumentos</span>
                    </label>
                  </div>

                  <div className="flex items-center justify-between pt-4 border-t border-linha">
                    <Button
                      type="button"
                      variant="secondary"
                      onClick={() => setActiveTab('aluno')}
                      className="text-xs"
                    >
                      ← Voltar aos Dados do Aluno
                    </Button>
                    <div className="flex gap-2">
                      <Button
                        type="button"
                        variant="secondary"
                        onClick={() => setIsCreateModalOpen(false)}
                        className="text-xs"
                      >
                        Cancelar
                      </Button>
                      <Button
                        type="submit"
                        disabled={submitting}
                        className="bg-mata text-white text-xs flex items-center gap-2"
                      >
                        {submitting && (
                          <div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                        )}
                        <span>Confirmar Inscrição</span>
                      </Button>
                    </div>
                  </div>
                </div>
              )}
            </form>
          </div>
        </div>
      )}

      {/* Modal / Drawer: Ficha Biográfica Detalhada do Aluno */}
      {isDetailModalOpen && selectedStudent && (
        <div className="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-superficie rounded-lg border border-linha shadow-2xl w-full max-w-2xl my-8 overflow-hidden">
            {/* Cabeçalho da Ficha */}
            <div className="p-5 bg-mata-escura text-white flex items-start justify-between">
              <div>
                <div className="flex items-center gap-2">
                  <span className="font-mono text-xs px-2 py-0.5 rounded bg-mata text-girassol font-semibold border border-girassol/30">
                    {selectedStudent.student_uid}
                  </span>
                  <Badge variant={selectedStudent.is_active ? 'folha' : 'tijolo'}>
                    {selectedStudent.is_active ? 'Matrícula Activa' : 'Matrícula Desactivada'}
                  </Badge>
                </div>
                <h2 className="font-serif text-xl font-bold mt-2">{selectedStudent.full_name}</h2>
                <div className="text-xs text-[#EDE7DA] mt-0.5">
                  {selectedStudent.age} anos ({selectedStudent.birth_date}) •{' '}
                  {selectedStudent.gender_label || (selectedStudent.gender === 'M' ? 'Masculino' : 'Feminino')}
                </div>
              </div>
              <button
                onClick={() => setIsDetailModalOpen(false)}
                className="text-white/70 hover:text-white"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Conteúdo da Ficha */}
            <div className="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
              {/* Informações Civis & Residência */}
              <div className="grid grid-cols-2 gap-4 text-xs">
                <div className="p-3 bg-[#FAF7F2] rounded border border-linha">
                  <span className="text-[10px] text-tinta-suave uppercase font-semibold block mb-1">
                    Documento de Identificação
                  </span>
                  <div className="font-semibold text-mata-escura">
                    {selectedStudent.id_document_type_label || selectedStudent.id_document_type}
                  </div>
                  <div className="font-mono text-tinta mt-0.5">
                    {selectedStudent.id_document_number || 'Sem documento registado'}
                  </div>
                </div>

                <div className="p-3 bg-[#FAF7F2] rounded border border-linha">
                  <span className="text-[10px] text-tinta-suave uppercase font-semibold block mb-1">
                    Residência / Naturalidade
                  </span>
                  <div className="font-semibold text-mata-escura">
                    {selectedStudent.municipality}, {selectedStudent.province}
                  </div>
                  <div className="text-tinta-suave mt-0.5">
                    {selectedStudent.neighborhood ? `Bairro ${selectedStudent.neighborhood}` : ''}
                    {selectedStudent.address ? ` • ${selectedStudent.address}` : ''}
                  </div>
                </div>
              </div>

              {/* Necessidades Educativas Especiais */}
              {selectedStudent.has_special_needs && (
                <div className="p-3 bg-girassol/10 border border-girassol/30 rounded text-xs">
                  <div className="flex items-center gap-1.5 font-semibold text-mata-escura">
                    <Sparkles className="w-4 h-4 text-girassol" />
                    <span>Necessidades Educativas Especiais (NEE)</span>
                  </div>
                  <p className="text-tinta mt-1">
                    {selectedStudent.special_needs_description || 'Necessita de acompanhamento pedagógico adaptado.'}
                  </p>
                </div>
              )}

              {/* Secção de Encarregados de Educação */}
              <div className="space-y-3">
                <div className="flex items-center justify-between border-b border-linha pb-2">
                  <div className="flex items-center gap-2">
                    <Users className="w-4 h-4 text-mata" />
                    <h4 className="font-serif font-bold text-sm text-mata-escura">
                      Encarregados de Educação ({selectedStudent.guardians?.length || 0})
                    </h4>
                  </div>
                  <Button
                    onClick={() => setIsAddGuardianModalOpen(true)}
                    variant="secondary"
                    className="text-[11px] py-1 px-2.5 h-auto flex items-center gap-1 text-mata border-mata/30 hover:bg-mata/10"
                  >
                    <Plus className="w-3.5 h-3.5" />
                    <span>Adicionar Encarregado</span>
                  </Button>
                </div>

                <div className="space-y-2">
                  {selectedStudent.guardians?.map((guardian) => (
                    <div
                      key={guardian.id}
                      className="p-3.5 bg-superficie rounded border border-linha shadow-sm space-y-2"
                    >
                      <div className="flex items-start justify-between">
                        <div>
                          <div className="font-semibold text-xs text-mata-escura flex items-center gap-2">
                            <span>{guardian.full_name}</span>
                            <span className="text-[11px] font-normal text-tinta-suave">
                              • {guardian.relationship_type}
                            </span>
                            {guardian.is_primary_contact && (
                              <span className="px-1.5 py-0.5 rounded bg-folha/15 text-mata text-[9px] font-bold">
                                Contacto Principal
                              </span>
                            )}
                            {guardian.is_financially_responsible && (
                              <span className="px-1.5 py-0.5 rounded bg-girassol/20 text-[#8C6D1F] text-[9px] font-bold">
                                Responsável Financeiro
                              </span>
                            )}
                          </div>
                          <div className="text-[11px] text-tinta-suave mt-0.5">
                            {guardian.profession && `${guardian.profession} `}
                            {guardian.workplace && `(${guardian.workplace})`}
                          </div>
                        </div>

                        {/* Acção de Remoção se houver mais de um encarregado */}
                        {selectedStudent.guardians.length > 1 && (
                          <button
                            onClick={() => handleRemoveGuardian(guardian.id)}
                            title="Desassociar Encarregado"
                            className="text-tinta-suave hover:text-tijolo p-1 rounded hover:bg-tijolo/10 transition-colors"
                          >
                            <X className="w-3.5 h-3.5" />
                          </button>
                        )}
                      </div>

                      <div className="grid grid-cols-2 gap-2 pt-2 border-t border-linha/50 text-[11px]">
                        <div className="flex items-center gap-1.5 font-mono text-mata">
                          <Phone className="w-3 h-3 text-tinta-suave" />
                          <span>{guardian.phone_primary}</span>
                          {guardian.phone_secondary && (
                            <span className="text-tinta-suave">/ {guardian.phone_secondary}</span>
                          )}
                        </div>
                        {guardian.email && (
                          <div className="flex items-center gap-1.5 text-tinta-suave">
                            <Mail className="w-3 h-3" />
                            <span>{guardian.email}</span>
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Acções na Ficha */}
              <div className="flex items-center justify-between pt-4 border-t border-linha">
                <Button
                  onClick={() => handleToggleStatus(selectedStudent)}
                  variant="secondary"
                  className={`text-xs flex items-center gap-1.5 ${
                    selectedStudent.is_active
                      ? 'text-tijolo border-tijolo/30 hover:bg-tijolo/10'
                      : 'text-folha border-folha/30 hover:bg-folha/10'
                  }`}
                >
                  {selectedStudent.is_active ? (
                    <>
                      <UserX className="w-3.5 h-3.5" />
                      <span>Desactivar Matrícula</span>
                    </>
                  ) : (
                    <>
                      <UserCheck className="w-3.5 h-3.5" />
                      <span>Reactivar Matrícula</span>
                    </>
                  )}
                </Button>

                <Button
                  onClick={() => setIsDetailModalOpen(false)}
                  className="bg-mata text-white text-xs"
                >
                  Fechar Ficha
                </Button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Modal: Adicionar Segundo Encarregado */}
      {isAddGuardianModalOpen && selectedStudent && (
        <div className="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-superficie rounded-lg border border-linha shadow-xl w-full max-w-lg my-8 overflow-hidden">
            <div className="px-5 py-4 bg-mata-escura text-white flex items-center justify-between">
              <h3 className="font-serif font-bold text-sm">
                Associar Encarregado a {selectedStudent.full_name}
              </h3>
              <button
                onClick={() => setIsAddGuardianModalOpen(false)}
                className="text-white/70 hover:text-white"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleAddGuardian} className="p-5 space-y-3">
              <div>
                <label className="block text-xs font-medium text-tinta mb-1">
                  Nome Completo *
                </label>
                <Input
                  required
                  placeholder="Ex: Maria António Capitão"
                  value={newGuardianForm.full_name}
                  onChange={(e) =>
                    setNewGuardianForm((prev) => ({ ...prev, full_name: e.target.value }))
                  }
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-tinta mb-1">
                    Parentesco *
                  </label>
                  <select
                    required
                    value={newGuardianForm.relationship_type}
                    onChange={(e) =>
                      setNewGuardianForm((prev) => ({
                        ...prev,
                        relationship_type: e.target.value as RelationshipType,
                      }))
                    }
                    className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                  >
                    {RELATIONSHIPS.map((r) => (
                      <option key={r} value={r}>
                        {r}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-medium text-tinta mb-1">
                    Telefone Principal *
                  </label>
                  <Input
                    required
                    placeholder="Ex: +244912345678"
                    value={newGuardianForm.phone_primary}
                    onChange={(e) =>
                      setNewGuardianForm((prev) => ({ ...prev, phone_primary: e.target.value }))
                    }
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-tinta mb-1">
                    Tipo Documento
                  </label>
                  <select
                    value={newGuardianForm.id_document_type}
                    onChange={(e) =>
                      setNewGuardianForm((prev) => ({
                        ...prev,
                        id_document_type: e.target.value as DocumentType,
                      }))
                    }
                    className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                  >
                    <option value="BI">Bilhete de Identidade (BI)</option>
                    <option value="PASSAPORTE">Passaporte</option>
                    <option value="CEDULA">Cédula</option>
                    <option value="PROVISORIO">Provisório</option>
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-medium text-tinta mb-1">
                    Nº Documento
                  </label>
                  <Input
                    placeholder="Ex: 004812345LA041"
                    value={newGuardianForm.id_document_number || ''}
                    onChange={(e) =>
                      setNewGuardianForm((prev) => ({
                        ...prev,
                        id_document_number: e.target.value,
                      }))
                    }
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-tinta mb-1">Profissão</label>
                  <Input
                    placeholder="Ex: Professora"
                    value={newGuardianForm.profession || ''}
                    onChange={(e) =>
                      setNewGuardianForm((prev) => ({ ...prev, profession: e.target.value }))
                    }
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-tinta mb-1">
                    Local de Trabalho
                  </label>
                  <Input
                    placeholder="Ex: Escola Primária nº 123"
                    value={newGuardianForm.workplace || ''}
                    onChange={(e) =>
                      setNewGuardianForm((prev) => ({ ...prev, workplace: e.target.value }))
                    }
                  />
                </div>
              </div>

              <div className="p-2.5 bg-papel rounded border border-linha space-y-1.5">
                <label className="flex items-center gap-2 cursor-pointer text-xs font-medium text-tinta">
                  <input
                    type="checkbox"
                    checked={newGuardianForm.is_primary_contact}
                    onChange={(e) =>
                      setNewGuardianForm((prev) => ({
                        ...prev,
                        is_primary_contact: e.target.checked,
                      }))
                    }
                    className="rounded border-linha text-mata focus:ring-mata"
                  />
                  <span>Contacto Principal para Avisos da Escola</span>
                </label>
                <label className="flex items-center gap-2 cursor-pointer text-xs font-medium text-tinta">
                  <input
                    type="checkbox"
                    checked={newGuardianForm.is_financially_responsible}
                    onChange={(e) =>
                      setNewGuardianForm((prev) => ({
                        ...prev,
                        is_financially_responsible: e.target.checked,
                      }))
                    }
                    className="rounded border-linha text-mata focus:ring-mata"
                  />
                  <span>Responsável Financeiro</span>
                </label>
              </div>

              <div className="flex items-center justify-end gap-2 pt-3 border-t border-linha">
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => setIsAddGuardianModalOpen(false)}
                  className="text-xs"
                >
                  Cancelar
                </Button>
                <Button
                  type="submit"
                  disabled={submitting}
                  className="bg-mata text-white text-xs flex items-center gap-2"
                >
                  {submitting && (
                    <div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  )}
                  <span>Adicionar Encarregado</span>
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
export default StudentsScreen
