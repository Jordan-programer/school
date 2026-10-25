import React, { useState, useEffect, useCallback } from 'react'
import {
  ClipboardList,
  Plus,
  Search,
  Filter,
  CheckCircle,
  AlertCircle,
  X,
  Printer,
  Building,
  RotateCcw,
  ShieldAlert,
  ArrowRightLeft,
  FileCheck,
} from 'lucide-react'
import { enrollmentService } from '../../services/enrollmentService'
import { studentService } from '../../services/studentService'
import { api } from '../../services/api'
import type {
  Enrollment,
  EnrollmentFilters,
  EnrollmentStats,
  CreateEnrollmentPayload,
  ConfirmEnrollmentPayload,
  AssignClassPayload,
  TransferPayload,
} from '../../types/enrollment'
import type { Student } from '../../types/student'
import type { AcademicYear, Grade, ClassGroup } from '../../types/academic'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'

export const EnrollmentsScreen: React.FC = () => {
  const [enrollments, setEnrollments] = useState<Enrollment[]>([])
  const [loading, setLoading] = useState<boolean>(true)
  const [totalCount, setTotalCount] = useState<number>(0)
  const [currentPage, setCurrentPage] = useState<number>(1)
  const [lastPage, setLastPage] = useState<number>(1)
  const [stats, setStats] = useState<EnrollmentStats | null>(null)

  // Dados auxiliares para selecções
  const [academicYears, setAcademicYears] = useState<AcademicYear[]>([])
  const [grades, setGrades] = useState<Grade[]>([])
  const [classGroups, setClassGroups] = useState<ClassGroup[]>([])
  const [studentsList, setStudentsList] = useState<Student[]>([])

  // Filtros
  const [filters, setFilters] = useState<EnrollmentFilters>({
    search: '',
    academic_year_id: '',
    grade_id: '',
    class_group_id: '',
    enrollment_type: '',
    status: '',
    has_age_distortion: '',
    unassigned_class: false,
  })

  // Mensagens
  const [successMessage, setSuccessMessage] = useState<string | null>(null)
  const [errorMessage, setErrorMessage] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState<boolean>(false)

  // Modais
  const [isCreateModalOpen, setIsCreateModalOpen] = useState<boolean>(false)
  const [isConfirmModalOpen, setIsConfirmModalOpen] = useState<boolean>(false)
  const [isAssignModalOpen, setIsAssignModalOpen] = useState<boolean>(false)
  const [isTransferModalOpen, setIsTransferModalOpen] = useState<boolean>(false)
  const [isReceiptModalOpen, setIsReceiptModalOpen] = useState<boolean>(false)

  // Matrícula seleccionada para acções
  const [selectedEnrollment, setSelectedEnrollment] = useState<Enrollment | null>(null)

  // Form State: Nova Matrícula
  const [createForm, setCreateForm] = useState<CreateEnrollmentPayload>({
    student_id: '',
    academic_year_id: '',
    grade_id: '',
    class_group_id: '',
    notes: '',
  })

  // Form State: Confirmação / Renovação
  const [confirmForm, setConfirmForm] = useState<ConfirmEnrollmentPayload>({
    student_id: '',
    academic_year_id: '',
    grade_id: '',
    class_group_id: '',
    notes: '',
  })

  // Form State: Atribuição de Turma
  const [assignForm, setAssignForm] = useState<AssignClassPayload>({
    class_group_id: '',
    force_capacity: false,
  })

  // Form State: Transferência
  const [transferForm, setTransferForm] = useState<TransferPayload>({
    destination_school: '',
    transfer_guide_number: '',
    reason: '',
    transfer_date: new Date().toISOString().split('T')[0],
  })

  // Carregar Estrutura Escolar e Alunos para as Dropdowns
  useEffect(() => {
    async function loadAuxiliaryData() {
      try {
        const [structRes, classesRes, studentsRes] = await Promise.all([
          api.get('/academic-structure'),
          api.get('/class-groups'),
          studentService.list({ per_page: 100 }),
        ])
        setAcademicYears(structRes.data.academic_years || [])
        setGrades(structRes.data.grades || [])
        setClassGroups(classesRes.data.data || [])
        setStudentsList(studentsRes.data || [])

        // Seleccionar ano activo por padrão se houver
        const activeYear = structRes.data.academic_years?.find((y: AcademicYear) => y.is_active)
        if (activeYear) {
          setFilters((prev) => ({ ...prev, academic_year_id: activeYear.id }))
          setCreateForm((prev) => ({ ...prev, academic_year_id: activeYear.id }))
          setConfirmForm((prev) => ({ ...prev, academic_year_id: activeYear.id }))
        }
      } catch (err) {
        console.error('Erro ao carregar dados auxiliares:', err)
      }
    }
    loadAuxiliaryData()
  }, [])

  // Carregar Matrículas e Estatísticas
  const loadData = useCallback(async () => {
    setLoading(true)
    try {
      const [enrollmentsRes, statsRes] = await Promise.all([
        enrollmentService.list({
          ...filters,
          page: currentPage,
          per_page: 15,
        }),
        enrollmentService.getStats(filters.academic_year_id),
      ])
      setEnrollments(enrollmentsRes.data)
      setTotalCount(enrollmentsRes.meta.total)
      setLastPage(enrollmentsRes.meta.last_page)
      setStats(statsRes)
    } catch (err: any) {
      setErrorMessage(err.response?.data?.message || 'Erro ao carregar matrículas.')
    } finally {
      setLoading(false)
    }
  }, [filters, currentPage])

  useEffect(() => {
    loadData()
  }, [loadData])

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setCurrentPage(1)
    loadData()
  }

  // Submissão: Nova Matrícula
  const handleCreateSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setSubmitting(true)
    setErrorMessage(null)

    try {
      const response = await enrollmentService.create(createForm)
      setSuccessMessage(response.message)
      setIsCreateModalOpen(false)
      setCreateForm({
        student_id: '',
        academic_year_id: filters.academic_year_id || '',
        grade_id: '',
        class_group_id: '',
        notes: '',
      })
      loadData()
      setTimeout(() => setSuccessMessage(null), 5000)
    } catch (err: any) {
      setErrorMessage(err.response?.data?.message || 'Falha ao realizar matrícula.')
    } finally {
      setSubmitting(false)
    }
  }

  // Submissão: Confirmação de Matrícula
  const handleConfirmSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setSubmitting(true)
    setErrorMessage(null)

    try {
      const response = await enrollmentService.confirm(confirmForm)
      setSuccessMessage(response.message)
      setIsConfirmModalOpen(false)
      setConfirmForm({
        student_id: '',
        academic_year_id: filters.academic_year_id || '',
        grade_id: '',
        class_group_id: '',
        notes: '',
      })
      loadData()
      setTimeout(() => setSuccessMessage(null), 5000)
    } catch (err: any) {
      setErrorMessage(err.response?.data?.message || 'Falha ao confirmar matrícula.')
    } finally {
      setSubmitting(false)
    }
  }

  // Submissão: Atribuição de Turma
  const handleAssignClassSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!selectedEnrollment) return

    setSubmitting(true)
    setErrorMessage(null)

    try {
      const response = await enrollmentService.assignClass(selectedEnrollment.id, assignForm)
      setSuccessMessage(response.message)
      setIsAssignModalOpen(false)
      loadData()
      setTimeout(() => setSuccessMessage(null), 4000)
    } catch (err: any) {
      setErrorMessage(err.response?.data?.message || 'Falha ao atribuir turma.')
    } finally {
      setSubmitting(false)
    }
  }

  // Submissão: Transferência Escolar
  const handleTransferSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!selectedEnrollment) return

    setSubmitting(true)
    setErrorMessage(null)

    try {
      const response = await enrollmentService.transfer(selectedEnrollment.id, transferForm)
      setSuccessMessage(response.message)
      setIsTransferModalOpen(false)
      loadData()
      setTimeout(() => setSuccessMessage(null), 5000)
    } catch (err: any) {
      setErrorMessage(err.response?.data?.message || 'Falha ao registar transferência.')
    } finally {
      setSubmitting(false)
    }
  }

  // Cancelar Matrícula
  const handleCancelEnrollment = async (enrollment: Enrollment) => {
    if (!window.confirm(`Tem a certeza que deseja anular a matrícula ${enrollment.enrollment_code}?`)) return

    try {
      const response = await enrollmentService.cancel(enrollment.id)
      setSuccessMessage(response.message)
      loadData()
      setTimeout(() => setSuccessMessage(null), 4000)
    } catch (err: any) {
      setErrorMessage(err.response?.data?.message || 'Erro ao cancelar matrícula.')
    }
  }

  // Abrir Comprovativo
  const handleOpenReceipt = async (enrollment: Enrollment) => {
    try {
      const full = await enrollmentService.getById(enrollment.id)
      setSelectedEnrollment(full)
      setIsReceiptModalOpen(true)
    } catch {
      setSelectedEnrollment(enrollment)
      setIsReceiptModalOpen(true)
    }
  }

  // Abrir Atribuição de Turma
  const handleOpenAssign = (enrollment: Enrollment) => {
    setSelectedEnrollment(enrollment)
    setAssignForm({
      class_group_id: enrollment.class_group?.id || '',
      force_capacity: false,
    })
    setIsAssignModalOpen(true)
  }

  // Abrir Transferência
  const handleOpenTransfer = (enrollment: Enrollment) => {
    setSelectedEnrollment(enrollment)
    setTransferForm({
      destination_school: '',
      transfer_guide_number: '',
      reason: '',
      transfer_date: new Date().toISOString().split('T')[0],
    })
    setIsTransferModalOpen(true)
  }

  return (
    <div className="space-y-6">
      {/* Cabeçalho da Página */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-linha">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="font-serif text-2xl font-bold text-mata-escura">
              Matrículas & Confirmações de Matrícula
            </h1>
            <Badge variant="folha">Módulo 4</Badge>
          </div>
          <p className="text-xs text-tinta-suave mt-1">
            Novo ingresso (1ª matrícula), renovação anual (confirmação), atribuição de turmas, controlo de distorção idade-classe e comprovativos oficiais.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <Button
            onClick={() => setIsConfirmModalOpen(true)}
            variant="secondary"
            className="flex items-center gap-2 text-xs text-mata border-mata/30 hover:bg-mata/10"
          >
            <RotateCcw className="w-4 h-4" />
            <span>Confirmar Matrícula (Renovação)</span>
          </Button>

          <Button
            onClick={() => setIsCreateModalOpen(true)}
            className="bg-mata hover:bg-mata-escura text-white shadow-sm flex items-center gap-2 text-xs"
          >
            <Plus className="w-4 h-4" />
            <span>Nova Matrícula (Ingresso)</span>
          </Button>
        </div>
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

      {/* Cartões Estatísticos Oficiais de Angola */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="p-4 bg-superficie rounded border border-linha shadow-sm">
          <div className="flex items-center justify-between text-tinta-suave text-xs mb-1">
            <span>Matrículas Confirmadas</span>
            <FileCheck className="w-4 h-4 text-folha" />
          </div>
          <div className="font-serif text-2xl font-bold text-mata-escura">
            {stats?.total_confirmed ?? totalCount}
          </div>
          <div className="text-[10px] text-tinta-suave mt-1">
            {stats?.total_new ?? 0} novas • {stats?.total_renewals ?? 0} renovações
          </div>
        </div>

        <div className="p-4 bg-superficie rounded border border-linha shadow-sm">
          <div className="flex items-center justify-between text-tinta-suave text-xs mb-1">
            <span>Distorção Idade-Classe</span>
            <ShieldAlert className="w-4 h-4 text-girassol" />
          </div>
          <div className="font-serif text-2xl font-bold text-mata-escura">
            {stats?.total_distortion ?? 0}
          </div>
          <div className="text-[10px] text-girassol mt-1">
            {stats?.distortion_percentage ?? 0}% do corpo discente (apoio pedagógico)
          </div>
        </div>

        <div className="p-4 bg-superficie rounded border border-linha shadow-sm">
          <div className="flex items-center justify-between text-tinta-suave text-xs mb-1">
            <span>Pendentes de Turma</span>
            <Building className="w-4 h-4 text-tijolo" />
          </div>
          <div className="font-serif text-2xl font-bold text-tijolo">
            {stats?.unassigned_count ?? 0}
          </div>
          <div className="text-[10px] text-tijolo/80 mt-1">Alunos sem turma atribuída</div>
        </div>

        <div className="p-4 bg-superficie rounded border border-linha shadow-sm">
          <div className="flex items-center justify-between text-tinta-suave text-xs mb-1">
            <span>Transferências</span>
            <ArrowRightLeft className="w-4 h-4 text-mata" />
          </div>
          <div className="font-serif text-2xl font-bold text-mata-escura">
            {stats?.total_transfers ?? 0}
          </div>
          <div className="text-[10px] text-tinta-suave mt-1">Guias de transferência emitidas</div>
        </div>
      </div>

      {/* Barra de Filtros e Pesquisa */}
      <div className="p-4 bg-superficie rounded border border-linha shadow-sm space-y-3">
        <form onSubmit={handleSearchSubmit} className="flex flex-col md:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-tinta-suave" />
            <input
              type="text"
              placeholder="Pesquisar por aluno, nº processo (UID) ou código (MAT/CONF)..."
              value={filters.search || ''}
              onChange={(e) => setFilters((prev) => ({ ...prev, search: e.target.value }))}
              className="w-full pl-9 pr-3 py-2 text-xs rounded border border-linha bg-papel text-tinta placeholder:text-tinta-suave/60 focus:outline-none focus:border-mata"
            />
          </div>

          <div className="flex flex-wrap items-center gap-2">
            <select
              value={filters.academic_year_id || ''}
              onChange={(e) => {
                setFilters((prev) => ({ ...prev, academic_year_id: e.target.value }))
                setCurrentPage(1)
              }}
              className="px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
            >
              <option value="">Ano Lectivo: Todos</option>
              {academicYears.map((ay) => (
                <option key={ay.id} value={ay.id}>
                  {ay.name} {ay.is_active ? '(Activo)' : ''}
                </option>
              ))}
            </select>

            <select
              value={filters.grade_id || ''}
              onChange={(e) => {
                setFilters((prev) => ({ ...prev, grade_id: e.target.value }))
                setCurrentPage(1)
              }}
              className="px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
            >
              <option value="">Classe: Todas</option>
              {grades.map((g) => (
                <option key={g.id} value={g.id}>
                  {g.name}
                </option>
              ))}
            </select>

            <select
              value={filters.enrollment_type || ''}
              onChange={(e) => {
                setFilters((prev) => ({ ...prev, enrollment_type: e.target.value }))
                setCurrentPage(1)
              }}
              className="px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
            >
              <option value="">Tipo: Todos</option>
              <option value="NOVA_MATRICULA">Nova Matrícula</option>
              <option value="CONFIRMACAO">Confirmação (Renovação)</option>
              <option value="TRANSFERENCIA">Transferência</option>
            </select>

            <select
              value={filters.has_age_distortion === '' ? '' : String(filters.has_age_distortion)}
              onChange={(e) => {
                const val = e.target.value === '' ? '' : e.target.value === 'true'
                setFilters((prev) => ({ ...prev, has_age_distortion: val }))
                setCurrentPage(1)
              }}
              className="px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
            >
              <option value="">Idade-Classe: Todas</option>
              <option value="false">Regular</option>
              <option value="true">Com Distorção</option>
            </select>

            <Button type="submit" variant="secondary" className="text-xs flex items-center gap-1.5">
              <Filter className="w-3.5 h-3.5" />
              <span>Filtrar</span>
            </Button>
          </div>
        </form>
      </div>

      {/* Tabela de Matrículas */}
      <div className="bg-superficie rounded border border-linha shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-[#FAF7F2] border-b border-linha text-tinta-suave uppercase text-[10px] tracking-wider">
              <tr>
                <th className="py-3 px-4">Código Matrícula</th>
                <th className="py-3 px-4">Aluno / Processo</th>
                <th className="py-3 px-4">Classe</th>
                <th className="py-3 px-4">Turma & Nº Ordem</th>
                <th className="py-3 px-4">Tipo</th>
                <th className="py-3 px-4">Idade / Distorção</th>
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
                      <span>A carregar livro de matrículas...</span>
                    </div>
                  </td>
                </tr>
              ) : enrollments.length === 0 ? (
                <tr>
                  <td colSpan={8} className="py-12 text-center text-tinta-suave">
                    <ClipboardList className="w-8 h-8 mx-auto mb-2 text-linha" />
                    <p className="font-medium">Nenhuma matrícula encontrada.</p>
                    <p className="text-[11px] mt-0.5">
                      Registe novos ingressos ou confirmações para este ano lectivo.
                    </p>
                  </td>
                </tr>
              ) : (
                enrollments.map((enrollment) => (
                  <tr key={enrollment.id} className="hover:bg-papel/60 transition-colors">
                    {/* Código da Matrícula */}
                    <td className="py-3 px-4 font-mono font-medium">
                      <span
                        className={`px-1.5 py-0.5 rounded text-[11px] border ${
                          enrollment.enrollment_type === 'CONFIRMACAO'
                            ? 'bg-folha/10 text-mata-escura border-folha/30'
                            : 'bg-mata/10 text-mata-escura border-mata/25'
                        }`}
                      >
                        {enrollment.enrollment_code}
                      </span>
                    </td>

                    {/* Aluno e UID */}
                    <td className="py-3 px-4">
                      <div className="font-semibold text-mata-escura">
                        {enrollment.student?.full_name}
                      </div>
                      <div className="font-mono text-[10px] text-tinta-suave">
                        {enrollment.student?.student_uid} • {enrollment.student?.id_document_number || 'Sem BI'}
                      </div>
                    </td>

                    {/* Classe */}
                    <td className="py-3 px-4 font-medium text-mata">
                      {enrollment.grade?.name}
                    </td>

                    {/* Turma & Nº Ordem */}
                    <td className="py-3 px-4">
                      {enrollment.class_group ? (
                        <div>
                          <div className="font-medium text-tinta flex items-center gap-1.5">
                            <span>{enrollment.class_group.name}</span>
                            {enrollment.order_number && (
                              <span className="px-1.5 py-0.2 bg-superficie rounded border border-linha font-mono text-[10px] font-bold text-mata">
                                Nº {enrollment.order_number}
                              </span>
                            )}
                          </div>
                          <div className="text-[10px] text-tinta-suave">
                            Turno: {enrollment.class_group.shift?.name || 'Regular'}
                          </div>
                        </div>
                      ) : (
                        <button
                          onClick={() => handleOpenAssign(enrollment)}
                          className="px-2 py-0.5 rounded text-[10px] bg-girassol/20 text-[#8C6D1F] border border-girassol/40 hover:bg-girassol/30 transition-colors font-medium"
                        >
                          + Atribuir Turma
                        </button>
                      )}
                    </td>

                    {/* Tipo de Matrícula */}
                    <td className="py-3 px-4">
                      <span className="text-[11px] text-tinta">
                        {enrollment.enrollment_type === 'CONFIRMACAO'
                          ? 'Confirmação (Renovação)'
                          : enrollment.enrollment_type === 'TRANSFERENCIA'
                          ? 'Transferência'
                          : 'Nova Matrícula'}
                      </span>
                      <div className="text-[10px] text-tinta-suave">{enrollment.enrolled_at_date}</div>
                    </td>

                    {/* Idade e Distorção */}
                    <td className="py-3 px-4">
                      <div className="text-[11px]">
                        {enrollment.age_at_enrollment} anos
                      </div>
                      {enrollment.has_age_distortion ? (
                        <span
                          title={enrollment.distortion_details || 'Distorção Idade-Classe'}
                          className="inline-flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.2 rounded bg-girassol/20 text-[#8C6D1F]"
                        >
                          <ShieldAlert className="w-2.5 h-2.5" />
                          <span>Distorção</span>
                        </span>
                      ) : (
                        <span className="text-[10px] text-folha">Regular</span>
                      )}
                    </td>

                    {/* Estado */}
                    <td className="py-3 px-4 text-center">
                      <Badge
                        variant={
                          enrollment.status === 'CONFIRMADA'
                            ? 'folha'
                            : enrollment.status === 'TRANSFERIDO'
                            ? 'girassol'
                            : enrollment.status === 'CANCELADA'
                            ? 'tijolo'
                            : 'neutro'
                        }
                      >
                        {enrollment.status_label || enrollment.status}
                      </Badge>
                    </td>

                    {/* Acções */}
                    <td className="py-3 px-4 text-right">
                      <div className="flex items-center justify-end gap-1">
                        <button
                          onClick={() => handleOpenReceipt(enrollment)}
                          title="Comprovativo Oficial de Matrícula (Imprimir)"
                          className="p-1.5 text-tinta-suave hover:text-mata hover:bg-mata/10 rounded transition-colors"
                        >
                          <Printer className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => handleOpenAssign(enrollment)}
                          title="Atribuir ou Alterar Turma"
                          className="p-1.5 text-tinta-suave hover:text-mata hover:bg-mata/10 rounded transition-colors"
                        >
                          <Building className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => handleOpenTransfer(enrollment)}
                          title="Registar Transferência Escolar"
                          className="p-1.5 text-tinta-suave hover:text-girassol hover:bg-girassol/10 rounded transition-colors"
                        >
                          <ArrowRightLeft className="w-4 h-4" />
                        </button>
                        {enrollment.status === 'CONFIRMADA' && (
                          <button
                            onClick={() => handleCancelEnrollment(enrollment)}
                            title="Anular / Cancelar Matrícula"
                            className="p-1.5 text-tinta-suave hover:text-tijolo hover:bg-tijolo/10 rounded transition-colors"
                          >
                            <X className="w-4 h-4" />
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Paginação */}
        {lastPage > 1 && (
          <div className="p-3 bg-[#FAF7F2] border-t border-linha flex items-center justify-between text-xs text-tinta-suave">
            <div>
              A mostrar página {currentPage} de {lastPage} ({totalCount} matrículas no total)
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

      {/* Modal: Nova Matrícula (Ingresso Inicial) */}
      {isCreateModalOpen && (
        <div className="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-superficie rounded-lg border border-linha shadow-xl w-full max-w-xl my-8 overflow-hidden">
            <div className="px-6 py-4 bg-mata-escura text-white flex items-center justify-between">
              <div className="flex items-center gap-2">
                <ClipboardList className="w-5 h-5 text-girassol" />
                <h3 className="font-serif font-bold text-base">Nova Matrícula (Novo Ingresso)</h3>
              </div>
              <button onClick={() => setIsCreateModalOpen(false)} className="text-white/70 hover:text-white">
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleCreateSubmit} className="p-6 space-y-4">
              <div className="p-3 bg-mata/10 border border-mata/20 rounded text-xs text-mata-escura">
                <strong>Regra Angolana:</strong> A matrícula de novo ingresso é efectuada apenas na 1ª inscrição do aluno na escola. Nos anos lectivos subsequentes, utiliza-se a Confirmação de Matrícula.
              </div>

              <div>
                <label className="block text-xs font-medium text-tinta mb-1">
                  Seleccionar Aluno *
                </label>
                <select
                  required
                  value={createForm.student_id}
                  onChange={(e) => setCreateForm((prev) => ({ ...prev, student_id: e.target.value }))}
                  className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                >
                  <option value="">-- Escolha o aluno --</option>
                  {studentsList.map((st) => (
                    <option key={st.id} value={st.id}>
                      {st.full_name} ({st.student_uid}) - {st.birth_date}
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-tinta mb-1">
                    Ano Lectivo *
                  </label>
                  <select
                    required
                    value={createForm.academic_year_id}
                    onChange={(e) => setCreateForm((prev) => ({ ...prev, academic_year_id: e.target.value }))}
                    className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                  >
                    <option value="">-- Ano Lectivo --</option>
                    {academicYears.map((ay) => (
                      <option key={ay.id} value={ay.id}>
                        {ay.name}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-medium text-tinta mb-1">
                    Classe de Ingresso *
                  </label>
                  <select
                    required
                    value={createForm.grade_id}
                    onChange={(e) => setCreateForm((prev) => ({ ...prev, grade_id: e.target.value, class_group_id: '' }))}
                    className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                  >
                    <option value="">-- Classe --</option>
                    {grades.map((g) => (
                      <option key={g.id} value={g.id}>
                        {g.name}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-tinta mb-1">
                  Turma (Opcional - pode enturmar depois)
                </label>
                <select
                  value={createForm.class_group_id || ''}
                  onChange={(e) => setCreateForm((prev) => ({ ...prev, class_group_id: e.target.value }))}
                  className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                >
                  <option value="">-- Deixar sem turma (distribuir posteriormente) --</option>
                  {classGroups
                    .filter(
                      (cg) =>
                        (!createForm.grade_id || (cg.grade_id ?? cg.grade?.id) === createForm.grade_id) &&
                        (!createForm.academic_year_id || (cg.academic_year_id ?? cg.academic_year?.id) === createForm.academic_year_id)
                    )
                    .map((cg) => (
                      <option key={cg.id} value={cg.id}>
                        {cg.name} • Lotação máx: {cg.max_students}
                      </option>
                    ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-medium text-tinta mb-1">
                  Observações da Secretaria
                </label>
                <Input
                  placeholder="Ex: Documentação completa entregue, dispensado de educação física..."
                  value={createForm.notes || ''}
                  onChange={(e) => setCreateForm((prev) => ({ ...prev, notes: e.target.value }))}
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-linha">
                <Button type="button" variant="secondary" onClick={() => setIsCreateModalOpen(false)} className="text-xs">
                  Cancelar
                </Button>
                <Button type="submit" disabled={submitting} className="bg-mata text-white text-xs flex items-center gap-2">
                  {submitting && <div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" />}
                  <span>Concluir Matrícula</span>
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Confirmação de Matrícula (Renovação) */}
      {isConfirmModalOpen && (
        <div className="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-superficie rounded-lg border border-linha shadow-xl w-full max-w-xl my-8 overflow-hidden">
            <div className="px-6 py-4 bg-mata-escura text-white flex items-center justify-between">
              <div className="flex items-center gap-2">
                <RotateCcw className="w-5 h-5 text-girassol" />
                <h3 className="font-serif font-bold text-base">Confirmação de Matrícula (Renovação)</h3>
              </div>
              <button onClick={() => setIsConfirmModalOpen(false)} className="text-white/70 hover:text-white">
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleConfirmSubmit} className="p-6 space-y-4">
              <div className="p-3 bg-folha/10 border border-folha/20 rounded text-xs text-mata-escura">
                Renovação anual da vaga escolar para alunos que transitaram de classe ou que continuam os estudos na mesma instituição.
              </div>

              <div>
                <label className="block text-xs font-medium text-tinta mb-1">
                  Aluno a Renovar *
                </label>
                <select
                  required
                  value={confirmForm.student_id}
                  onChange={(e) => setConfirmForm((prev) => ({ ...prev, student_id: e.target.value }))}
                  className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                >
                  <option value="">-- Escolha o aluno existente --</option>
                  {studentsList.map((st) => (
                    <option key={st.id} value={st.id}>
                      {st.full_name} ({st.student_uid})
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-tinta mb-1">
                    Ano Lectivo de Destino *
                  </label>
                  <select
                    required
                    value={confirmForm.academic_year_id}
                    onChange={(e) => setConfirmForm((prev) => ({ ...prev, academic_year_id: e.target.value }))}
                    className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                  >
                    <option value="">-- Ano Lectivo --</option>
                    {academicYears.map((ay) => (
                      <option key={ay.id} value={ay.id}>
                        {ay.name}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-medium text-tinta mb-1">
                    Classe a Frequentar *
                  </label>
                  <select
                    required
                    value={confirmForm.grade_id}
                    onChange={(e) => setConfirmForm((prev) => ({ ...prev, grade_id: e.target.value, class_group_id: '' }))}
                    className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                  >
                    <option value="">-- Classe --</option>
                    {grades.map((g) => (
                      <option key={g.id} value={g.id}>
                        {g.name}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-tinta mb-1">
                  Turma de Destino
                </label>
                <select
                  value={confirmForm.class_group_id || ''}
                  onChange={(e) => setConfirmForm((prev) => ({ ...prev, class_group_id: e.target.value }))}
                  className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                >
                  <option value="">-- Deixar sem turma (distribuir posteriormente) --</option>
                  {classGroups
                    .filter(
                      (cg) =>
                        (!confirmForm.grade_id || (cg.grade_id ?? cg.grade?.id) === confirmForm.grade_id) &&
                        (!confirmForm.academic_year_id || (cg.academic_year_id ?? cg.academic_year?.id) === confirmForm.academic_year_id)
                    )
                    .map((cg) => (
                      <option key={cg.id} value={cg.id}>
                        {cg.name} • Lotação máx: {cg.max_students}
                      </option>
                    ))}
                </select>
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-linha">
                <Button type="button" variant="secondary" onClick={() => setIsConfirmModalOpen(false)} className="text-xs">
                  Cancelar
                </Button>
                <Button type="submit" disabled={submitting} className="bg-mata text-white text-xs flex items-center gap-2">
                  {submitting && <div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" />}
                  <span>Confirmar Renovação</span>
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Atribuição / Alteração de Turma */}
      {isAssignModalOpen && selectedEnrollment && (
        <div className="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-superficie rounded-lg border border-linha shadow-xl w-full max-w-md my-8 overflow-hidden">
            <div className="px-5 py-4 bg-mata-escura text-white flex items-center justify-between">
              <h3 className="font-serif font-bold text-sm">
                Enturmação de {selectedEnrollment.student?.full_name}
              </h3>
              <button onClick={() => setIsAssignModalOpen(false)} className="text-white/70 hover:text-white">
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleAssignClassSubmit} className="p-5 space-y-4">
              <div className="text-xs text-tinta-suave">
                Classe do Aluno: <strong className="text-mata-escura">{selectedEnrollment.grade?.name}</strong> • Ano Lectivo: <strong className="text-mata-escura">{selectedEnrollment.academic_year?.name}</strong>
              </div>

              <div>
                <label className="block text-xs font-medium text-tinta mb-1">
                  Seleccionar Turma
                </label>
                <select
                  value={assignForm.class_group_id || ''}
                  onChange={(e) => setAssignForm((prev) => ({ ...prev, class_group_id: e.target.value }))}
                  className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                >
                  <option value="">-- Sem Turma (Desassociar) --</option>
                  {classGroups
                    .filter(
                      (cg) =>
                        (cg.grade_id ?? cg.grade?.id) === selectedEnrollment.grade?.id &&
                        (cg.academic_year_id ?? cg.academic_year?.id) === selectedEnrollment.academic_year?.id
                    )
                    .map((cg) => (
                      <option key={cg.id} value={cg.id}>
                        {cg.name} (Lotação Máx: {cg.max_students})
                      </option>
                    ))}
                </select>
              </div>

              <div className="p-3 bg-papel rounded border border-linha">
                <label className="flex items-center gap-2 cursor-pointer text-xs font-medium text-tinta">
                  <input
                    type="checkbox"
                    checked={assignForm.force_capacity}
                    onChange={(e) => setAssignForm((prev) => ({ ...prev, force_capacity: e.target.checked }))}
                    className="rounded border-linha text-mata focus:ring-mata"
                  />
                  <span>Autorização especial da Direcção para sobrelotação</span>
                </label>
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-linha">
                <Button type="button" variant="secondary" onClick={() => setIsAssignModalOpen(false)} className="text-xs">
                  Cancelar
                </Button>
                <Button type="submit" disabled={submitting} className="bg-mata text-white text-xs flex items-center gap-2">
                  {submitting && <div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" />}
                  <span>Gravar Enturmação</span>
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Transferência Escolar */}
      {isTransferModalOpen && selectedEnrollment && (
        <div className="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-superficie rounded-lg border border-linha shadow-xl w-full max-w-md my-8 overflow-hidden">
            <div className="px-5 py-4 bg-mata-escura text-white flex items-center justify-between">
              <h3 className="font-serif font-bold text-sm">
                Transferência de {selectedEnrollment.student?.full_name}
              </h3>
              <button onClick={() => setIsTransferModalOpen(false)} className="text-white/70 hover:text-white">
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleTransferSubmit} className="p-5 space-y-3">
              <div>
                <label className="block text-xs font-medium text-tinta mb-1">
                  Escola de Destino *
                </label>
                <Input
                  required
                  placeholder="Ex: Escola Primária nº 1205"
                  value={transferForm.destination_school}
                  onChange={(e) => setTransferForm((prev) => ({ ...prev, destination_school: e.target.value }))}
                />
              </div>

              <div>
                <label className="block text-xs font-medium text-tinta mb-1">
                  Número da Guia de Transferência
                </label>
                <Input
                  placeholder="Ex: GUIA-2026/041"
                  value={transferForm.transfer_guide_number || ''}
                  onChange={(e) => setTransferForm((prev) => ({ ...prev, transfer_guide_number: e.target.value }))}
                />
              </div>

              <div>
                <label className="block text-xs font-medium text-tinta mb-1">
                  Data da Transferência *
                </label>
                <Input
                  type="date"
                  required
                  value={transferForm.transfer_date}
                  onChange={(e) => setTransferForm((prev) => ({ ...prev, transfer_date: e.target.value }))}
                />
              </div>

              <div>
                <label className="block text-xs font-medium text-tinta mb-1">
                  Motivo da Transferência
                </label>
                <Input
                  placeholder="Ex: Mudança de residência da família para outra província"
                  value={transferForm.reason || ''}
                  onChange={(e) => setTransferForm((prev) => ({ ...prev, reason: e.target.value }))}
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-linha">
                <Button type="button" variant="secondary" onClick={() => setIsTransferModalOpen(false)} className="text-xs">
                  Cancelar
                </Button>
                <Button type="submit" disabled={submitting} className="bg-mata text-white text-xs flex items-center gap-2">
                  {submitting && <div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" />}
                  <span>Emitir Guia e Concluir</span>
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal / Documento Oficial Imprimível: Comprovativo de Matrícula */}
      {isReceiptModalOpen && selectedEnrollment && (
        <div className="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-superficie rounded-lg border border-linha shadow-2xl w-full max-w-2xl my-8 overflow-hidden print:m-0 print:border-none print:shadow-none print:w-full">
            {/* Barra de Acções do Modal (Oculta na impressão) */}
            <div className="px-6 py-3 bg-mata-escura text-white flex items-center justify-between print:hidden">
              <div className="flex items-center gap-2">
                <Printer className="w-4 h-4 text-girassol" />
                <span className="font-serif font-bold text-sm">Visualização de Documento Oficial</span>
              </div>
              <div className="flex items-center gap-2">
                <Button
                  onClick={() => window.print()}
                  className="bg-girassol text-mata-escura hover:bg-girassol/90 text-xs flex items-center gap-1.5 font-bold"
                >
                  <Printer className="w-3.5 h-3.5" />
                  <span>Imprimir Comprovativo</span>
                </Button>
                <button onClick={() => setIsReceiptModalOpen(false)} className="text-white/70 hover:text-white p-1">
                  <X className="w-5 h-5" />
                </button>
              </div>
            </div>

            {/* Conteúdo do Comprovativo Oficial Angolano */}
            <div className="p-8 bg-white text-black space-y-6 print:p-6">
              {/* Cabeçalho Institucional Oficial */}
              <div className="text-center space-y-1 border-b-2 border-black pb-4">
                <div className="w-12 h-12 mx-auto mb-1 border border-black flex items-center justify-center font-serif font-bold text-base">
                  R.A
                </div>
                <div className="text-xs uppercase font-bold tracking-widest">
                  República de Angola
                </div>
                <div className="text-xs uppercase font-semibold">
                  Governo Provincial de {selectedEnrollment.school?.province || 'Luanda'}
                </div>
                <div className="text-xs uppercase">
                  Direcção Municipal de Educação de {selectedEnrollment.school?.municipality || 'Luanda'}
                </div>
                <div className="font-serif font-bold text-sm uppercase mt-1">
                  {selectedEnrollment.school?.name}
                </div>
              </div>

              {/* Título do Documento */}
              <div className="text-center">
                <h2 className="font-serif text-lg font-bold uppercase tracking-wide">
                  {selectedEnrollment.enrollment_type === 'CONFIRMACAO'
                    ? 'Comprovativo de Confirmação de Matrícula'
                    : 'Comprovativo Oficial de Matrícula'}
                </h2>
                <div className="text-xs font-mono font-bold mt-1">
                  {selectedEnrollment.enrollment_code} • {selectedEnrollment.academic_year?.name}
                </div>
              </div>

              {/* Dados do Aluno */}
              <div className="border border-black p-4 space-y-2 text-xs">
                <div className="grid grid-cols-2 gap-2">
                  <div>
                    <span className="font-bold">Nome do Aluno:</span> {selectedEnrollment.student?.full_name}
                  </div>
                  <div>
                    <span className="font-bold">Nº de Processo (UID):</span>{' '}
                    <span className="font-mono">{selectedEnrollment.student?.student_uid}</span>
                  </div>
                </div>

                <div className="grid grid-cols-3 gap-2">
                  <div>
                    <span className="font-bold">Data de Nascimento:</span> {selectedEnrollment.student?.birth_date}
                  </div>
                  <div>
                    <span className="font-bold">Idade no Ingresso:</span> {selectedEnrollment.age_at_enrollment} anos
                  </div>
                  <div>
                    <span className="font-bold">Género:</span> {selectedEnrollment.student?.gender === 'M' ? 'Masculino' : 'Feminino'}
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-2">
                  <div>
                    <span className="font-bold">Documento:</span>{' '}
                    {selectedEnrollment.student?.id_document_type} - {selectedEnrollment.student?.id_document_number || 'Provisório'}
                  </div>
                  <div>
                    <span className="font-bold">Naturalidade:</span> {selectedEnrollment.student?.municipality}, {selectedEnrollment.student?.province}
                  </div>
                </div>
              </div>

              {/* Dados Académicos e Turma */}
              <div className="border border-black p-4 space-y-2 text-xs">
                <div className="grid grid-cols-3 gap-2">
                  <div>
                    <span className="font-bold">Classe:</span> {selectedEnrollment.grade?.name}
                  </div>
                  <div>
                    <span className="font-bold">Turma:</span> {selectedEnrollment.class_group?.name || 'A atribuir'}
                  </div>
                  <div>
                    <span className="font-bold">Nº de Ordem:</span>{' '}
                    <span className="font-mono font-bold">{selectedEnrollment.order_number ? `Nº ${selectedEnrollment.order_number}` : 'Pendente'}</span>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-2">
                  <div>
                    <span className="font-bold">Turno:</span> {selectedEnrollment.class_group?.shift?.name || 'Diurno'}
                  </div>
                  <div>
                    <span className="font-bold">Sala de Aulas:</span> {selectedEnrollment.class_group?.classroom?.name || 'A definir'}
                  </div>
                </div>
              </div>

              {/* Dados de Tutela e Encarregado */}
              {selectedEnrollment.student?.guardians?.[0] && (
                <div className="border border-black p-4 space-y-1 text-xs">
                  <div className="font-bold uppercase text-[10px] mb-1">Encarregado de Educação Responsável:</div>
                  <div className="grid grid-cols-2 gap-2">
                    <div>
                      <span className="font-bold">Nome:</span> {selectedEnrollment.student.guardians[0].full_name} ({selectedEnrollment.student.guardians[0].relationship_type})
                    </div>
                    <div>
                      <span className="font-bold">Contacto:</span> {selectedEnrollment.student.guardians[0].phone_primary}
                    </div>
                  </div>
                </div>
              )}

              {/* Alerta de Distorção ou Apoio se Houver */}
              {selectedEnrollment.has_age_distortion && (
                <div className="border border-black border-dashed p-3 text-[11px] italic">
                  <strong>Nota Pedagógica:</strong> {selectedEnrollment.distortion_details || 'Distorção idade-classe registada para fins de acompanhamento pedagógico escolar.'}
                </div>
              )}

              {/* Espaço para Carimbo e Assinatura */}
              <div className="pt-8 grid grid-cols-2 gap-8 text-center text-xs">
                <div>
                  <div className="border-t border-black pt-1">O Encarregado de Educação</div>
                  <div className="text-[10px] text-gray-500 mt-0.5">(Assinatura legível)</div>
                </div>
                <div>
                  <div className="border-t border-black pt-1">A Secretaria da Escola</div>
                  <div className="text-[10px] text-gray-500 mt-0.5">(Carimbo a óleo e rubrica)</div>
                </div>
              </div>

              {/* Rodapé Oficial */}
              <div className="pt-4 border-t border-gray-300 flex justify-between text-[10px] text-gray-500">
                <span>Emitido em: {selectedEnrollment.enrolled_at}</span>
                <span>SisGestEsc Angola • Sistema Integrado de Gestão Escolar</span>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
export default EnrollmentsScreen
