import React, { useState, useEffect } from 'react'
import { api } from '../../services/api'
import { useAuth } from '../../context/AuthContext'
import type {
  ClassGroup,
  School,
  AcademicYear,
  Grade,
  Shift,
  Classroom,
  Subject,
} from '../../types/academic'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Badge } from '../../components/ui/Badge'
import {
  Building2,
  Plus,
  Search,
  Calendar,
  Layers,
  DoorOpen,
  UserCheck,
  Clock,
  BookOpen,
} from 'lucide-react'

export const AcademicScreen: React.FC = () => {
  const { hasRole } = useAuth()
  const [activeTab, setActiveTab] = useState<'turmas' | 'ciclos' | 'trimestres'>('turmas')

  const [school, setSchool] = useState<School | null>(null)
  const [academicYears, setAcademicYears] = useState<AcademicYear[]>([])
  const [grades, setGrades] = useState<Grade[]>([])
  const [shifts, setShifts] = useState<Shift[]>([])
  const [classrooms, setClassrooms] = useState<Classroom[]>([])
  const [subjects, setSubjects] = useState<Subject[]>([])
  const [staffList, setStaffList] = useState<any[]>([])

  const [classGroups, setClassGroups] = useState<ClassGroup[]>([])
  const [loading, setLoading] = useState(true)

  // Filtros
  const [search, setSearch] = useState('')
  const [filterGrade, setFilterGrade] = useState('')
  const [filterShift, setFilterShift] = useState('')

  // Modal de Criação de Turma
  const [isClassModalOpen, setIsClassModalOpen] = useState(false)
  const [classFormData, setClassFormData] = useState({
    academic_year_id: '',
    grade_id: '',
    shift_id: '',
    classroom_id: '',
    name: '',
    max_students: 45,
  })
  const [createClassLoading, setCreateClassLoading] = useState(false)

  // Modal de Atribuição Docente
  const [assigningClass, setAssigningClass] = useState<ClassGroup | null>(null)
  const [assignFormData, setAssignFormData] = useState({
    subject_id: '',
    staff_id: '',
  })
  const [assignLoading, setAssignLoading] = useState(false)

  const canManage = hasRole(['Director', 'Subdirector Pedagógico', 'Secretaria'])

  const loadStructure = async () => {
    try {
      const res = await api.get('/academic-structure')
      setSchool(res.data.school)
      setAcademicYears(res.data.academic_years)
      setGrades(res.data.grades)
      setShifts(res.data.shifts)
      setClassrooms(res.data.classrooms)
      setSubjects(res.data.subjects)

      // Definir ano activo por defeito no formulário
      const activeYear = res.data.academic_years.find((y: AcademicYear) => y.is_active)
      if (activeYear) {
        setClassFormData((prev) => ({ ...prev, academic_year_id: activeYear.id }))
      }
    } catch (err) {
      console.error('Erro ao carregar estrutura académica:', err)
    }
  }

  const loadStaff = async () => {
    try {
      const res = await api.get('/users?per_page=100')
      const staffs = res.data.data
        .filter((u: any) => !!u.staff)
        .map((u: any) => u.staff)
      setStaffList(staffs)
    } catch (err) {
      console.error('Erro ao carregar corpo docente:', err)
    }
  }

  const loadClassGroups = async () => {
    setLoading(true)
    try {
      const params: any = {}
      if (search) params.search = search
      if (filterGrade) params.grade_id = filterGrade
      if (filterShift) params.shift_id = filterShift

      const res = await api.get('/class-groups', { params })
      setClassGroups(res.data.data)
    } catch (err) {
      console.error('Erro ao carregar turmas:', err)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    loadStructure()
    loadStaff()
  }, [])

  useEffect(() => {
    if (activeTab === 'turmas') {
      loadClassGroups()
    }
  }, [filterGrade, filterShift, activeTab])

  const handleCreateClass = async (e: React.FormEvent) => {
    e.preventDefault()
    setCreateClassLoading(true)
    try {
      await api.post('/class-groups', classFormData)
      setIsClassModalOpen(false)
      setClassFormData({
        academic_year_id: academicYears.find((y) => y.is_active)?.id || '',
        grade_id: '',
        shift_id: '',
        classroom_id: '',
        name: '',
        max_students: 45,
      })
      loadClassGroups()
    } catch (err: any) {
      alert(err.response?.data?.message || 'Erro ao criar turma escolar.')
    } finally {
      setCreateClassLoading(false)
    }
  }

  const handleAssignTeacher = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!assigningClass) return
    setAssignLoading(true)
    try {
      await api.post(`/class-groups/${assigningClass.id}/assign-teacher`, assignFormData)
      setAssigningClass(null)
      setAssignFormData({ subject_id: '', staff_id: '' })
      loadClassGroups()
    } catch (err: any) {
      alert(err.response?.data?.message || 'Erro ao atribuir professor.')
    } finally {
      setAssignLoading(false)
    }
  }

  return (
    <div className="space-y-6">
      {/* Cabeçalho da Escola */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-linha">
        <div>
          <div className="flex items-center gap-2">
            <Building2 className="w-5 h-5 text-mata" />
            <h1 className="text-xl font-bold text-mata-escura font-serif">
              {school?.name || 'Estrutura Escolar e Turmas'}
            </h1>
          </div>
          <p className="text-xs text-tinta-suave mt-1">
            Código: <span className="font-mono text-tinta font-medium">{school?.code || '—'}</span> • Província: {school?.province || 'Luanda'} ({school?.municipality || 'Luanda'}) • NIF: {school?.tax_id || '—'}
          </p>
        </div>

        {activeTab === 'turmas' && canManage && (
          <Button
            variant="primary"
            onClick={() => setIsClassModalOpen(true)}
            className="flex items-center gap-2"
          >
            <Plus className="w-4 h-4" />
            <span>Criar Nova Turma</span>
          </Button>
        )}
      </div>

      {/* Navegação por Abas Estilo Caderno */}
      <div className="flex gap-2 border-b border-linha">
        <button
          onClick={() => setActiveTab('turmas')}
          className={`flex items-center gap-2 px-4 py-2.5 text-xs font-semibold uppercase tracking-wider transition-colors border-b-2 ${
            activeTab === 'turmas'
              ? 'border-mata text-mata-escura bg-superficie'
              : 'border-transparent text-tinta-suave hover:text-tinta'
          }`}
        >
          <Layers className="w-3.5 h-3.5" />
          <span>Turmas Escolares ({classGroups.length})</span>
        </button>

        <button
          onClick={() => setActiveTab('ciclos')}
          className={`flex items-center gap-2 px-4 py-2.5 text-xs font-semibold uppercase tracking-wider transition-colors border-b-2 ${
            activeTab === 'ciclos'
              ? 'border-mata text-mata-escura bg-superficie'
              : 'border-transparent text-tinta-suave hover:text-tinta'
          }`}
        >
          <BookOpen className="w-3.5 h-3.5" />
          <span>Ciclos de Aprendizagem & Disciplinas</span>
        </button>

        <button
          onClick={() => setActiveTab('trimestres')}
          className={`flex items-center gap-2 px-4 py-2.5 text-xs font-semibold uppercase tracking-wider transition-colors border-b-2 ${
            activeTab === 'trimestres'
              ? 'border-mata text-mata-escura bg-superficie'
              : 'border-transparent text-tinta-suave hover:text-tinta'
          }`}
        >
          <Calendar className="w-3.5 h-3.5" />
          <span>Ano Lectivo & Prazos de Conselho</span>
        </button>
      </div>

      {/* ABA 1: TURMAS ESCOLARES */}
      {activeTab === 'turmas' && (
        <div className="space-y-4">
          {/* Filtros */}
          <div className="bg-superficie border border-linha rounded p-3 flex flex-col md:flex-row items-center gap-3 justify-between">
            <div className="flex items-center gap-2 w-full md:w-72">
              <Input
                placeholder="Filtrar turma (ex: 7ª A)..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="text-xs"
              />
              <Button variant="secondary" size="sm" onClick={loadClassGroups}>
                <Search className="w-3.5 h-3.5" />
              </Button>
            </div>

            <div className="flex items-center gap-2 w-full md:w-auto">
              <select
                value={filterGrade}
                onChange={(e) => setFilterGrade(e.target.value)}
                className="px-2.5 py-1.5 text-xs bg-papel text-tinta border border-linha rounded focus:border-mata focus:outline-none"
              >
                <option value="">Todas as Classes</option>
                {grades.map((g) => (
                  <option key={g.id} value={g.id}>
                    {g.name}
                  </option>
                ))}
              </select>

              <select
                value={filterShift}
                onChange={(e) => setFilterShift(e.target.value)}
                className="px-2.5 py-1.5 text-xs bg-papel text-tinta border border-linha rounded focus:border-mata focus:outline-none"
              >
                <option value="">Todos os Turnos</option>
                {shifts.map((s) => (
                  <option key={s.id} value={s.id}>
                    {s.name}
                  </option>
                ))}
              </select>
            </div>
          </div>

          {/* Tabela de Turmas */}
          <div className="bg-superficie border border-linha rounded overflow-hidden">
            {loading ? (
              <div className="p-12 text-center text-xs text-tinta-suave flex flex-col items-center gap-2">
                <div className="w-5 h-5 border-2 border-mata border-t-transparent rounded-full animate-spin" />
                <span>A carregar turmas escolares...</span>
              </div>
            ) : classGroups.length === 0 ? (
              <div className="p-12 text-center text-xs text-tinta-suave">
                Nenhuma turma escolar cadastrada para os filtros selecionados.
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-left escola-table border-collapse">
                  <thead>
                    <tr>
                      <th>Turma</th>
                      <th>Classe / Nível</th>
                      <th>Turno</th>
                      <th>Sala Física</th>
                      <th>Lotação Máx.</th>
                      <th>Docentes Vinculados</th>
                      <th className="text-right">Acções</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-linha text-xs">
                    {classGroups.map((cg) => (
                      <tr key={cg.id} className="hover:bg-papel/40 transition-colors">
                        <td className="font-semibold text-mata-escura font-serif text-sm">
                          {cg.name}
                        </td>
                        <td>
                          <div className="flex items-center gap-1.5">
                            <span className="font-medium text-tinta">{cg.grade.name}</span>
                            {cg.grade.is_cycle_ending && (
                              <Badge variant="barro" className="text-[10px]">
                                Fim de Ciclo
                              </Badge>
                            )}
                          </div>
                          <div className="text-[11px] text-tinta-suave">
                            {cg.grade.cycle?.name || 'Ciclo Geral'}
                          </div>
                        </td>
                        <td>
                          <div className="flex items-center gap-1.5">
                            <Clock className="w-3.5 h-3.5 text-tinta-suave" />
                            <span>{cg.shift.name}</span>
                          </div>
                          <div className="text-[10px] text-tinta-suave font-mono">
                            {cg.shift.start_time} às {cg.shift.end_time}
                          </div>
                        </td>
                        <td>
                          <div className="flex items-center gap-1.5">
                            <DoorOpen className="w-3.5 h-3.5 text-tinta-suave" />
                            <span className="font-medium">{cg.classroom?.name || 'Não atribuída'}</span>
                          </div>
                          {cg.classroom && (
                            <div className="text-[10px] text-tinta-suave">
                              Capacidade: {cg.classroom.capacity} lugares ({cg.classroom.location_block})
                            </div>
                          )}
                        </td>
                        <td className="font-mono tabular-nums text-tinta">
                          {cg.max_students} alunos
                        </td>
                        <td>
                          {cg.teaching_assignments && cg.teaching_assignments.length > 0 ? (
                            <div className="space-y-1">
                              {cg.teaching_assignments.map((ta) => (
                                <div key={ta.id} className="text-[11px] flex items-center gap-1">
                                  <span className="font-semibold text-mata">{ta.subject.code}:</span>
                                  <span className="text-tinta-suave truncate max-w-[140px]">{ta.teacher.full_name}</span>
                                </div>
                              ))}
                            </div>
                          ) : (
                            <span className="text-tinta-suave italic text-[11px]">Sem professores</span>
                          )}
                        </td>
                        <td className="text-right">
                          {canManage && (
                            <Button
                              variant="secondary"
                              size="sm"
                              onClick={() => {
                                setAssigningClass(cg)
                                setAssignFormData({ subject_id: '', staff_id: '' })
                              }}
                              className="text-xs"
                              title="Atribuir Professor a Disciplina"
                            >
                              <UserCheck className="w-3.5 h-3.5 mr-1 text-mata" />
                              <span>Atribuir Docente</span>
                            </Button>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      )}

      {/* ABA 2: CICLOS PEDAGÓGICOS ANGOLANOS */}
      {activeTab === 'ciclos' && (
        <div className="space-y-6">
          <div className="p-4 bg-superficie border border-linha rounded text-xs text-tinta space-y-2">
            <h3 className="font-serif font-bold text-sm text-mata-escura">
              Estrutura dos Ciclos de Aprendizagem de Angola
            </h3>
            <p className="text-tinta-suave">
              Conforme as directrizes curriculares do Ministério da Educação, o Ensino Primário articula-se em três ciclos de duas classes cada. A passagem de ciclo (2ª, 4ª e 6ª classes) compreende avaliação pedagógica e exames de fim de ciclo.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="bg-superficie border border-linha rounded p-4 space-y-3">
              <div className="border-b border-linha pb-2">
                <span className="text-[10px] uppercase font-semibold text-tinta-suave">Pré-Escolar</span>
                <h4 className="font-serif font-bold text-base text-mata">Iniciação</h4>
              </div>
              <p className="text-xs text-tinta-suave">
                Adaptação social e pré-alfabetização. Avaliação exclusivamente qualitativa e descritiva.
              </p>
              <div className="p-2 bg-papel rounded text-xs font-mono">
                Classe: Iniciação (INI)
              </div>
            </div>

            <div className="bg-superficie border border-linha rounded p-4 space-y-3">
              <div className="border-b border-linha pb-2">
                <span className="text-[10px] uppercase font-semibold text-tinta-suave">Ensino Primário (6 Classes)</span>
                <h4 className="font-serif font-bold text-base text-mata">3 Ciclos Pedagógicos</h4>
              </div>
              <ul className="text-xs space-y-2 text-tinta">
                <li className="p-2 bg-papel rounded flex justify-between items-center">
                  <span>1º Ciclo: 1ª e 2ª Classes</span>
                  <Badge variant="barro">Exame 2ª</Badge>
                </li>
                <li className="p-2 bg-papel rounded flex justify-between items-center">
                  <span>2º Ciclo: 3ª e 4ª Classes</span>
                  <Badge variant="barro">Exame 4ª</Badge>
                </li>
                <li className="p-2 bg-papel rounded flex justify-between items-center">
                  <span>3º Ciclo: 5ª e 6ª Classes</span>
                  <Badge variant="barro">Exame 6ª</Badge>
                </li>
              </ul>
            </div>

            <div className="bg-superficie border border-linha rounded p-4 space-y-3">
              <div className="border-b border-linha pb-2">
                <span className="text-[10px] uppercase font-semibold text-tinta-suave">Ensino Secundário Geral</span>
                <h4 className="font-serif font-bold text-base text-mata">1º Ciclo (7ª a 9ª)</h4>
              </div>
              <p className="text-xs text-tinta-suave">
                Avaliação quantitativa com escala de 0 a 20 valores. Disciplinas nucleares: Língua Portuguesa e Matemática.
              </p>
              <ul className="text-xs space-y-1.5 text-tinta font-mono">
                <li className="p-1.5 bg-papel rounded">7ª Classe (Transição)</li>
                <li className="p-1.5 bg-papel rounded">8ª Classe (Transição)</li>
                <li className="p-1.5 bg-papel rounded flex justify-between items-center">
                  <span>9ª Classe</span>
                  <Badge variant="barro">Exame Conclusão</Badge>
                </li>
              </ul>
            </div>
          </div>

          {/* Matriz de Disciplinas */}
          <div className="bg-superficie border border-linha rounded p-4 space-y-3">
            <h4 className="font-serif font-bold text-sm text-mata-escura">
              Disciplinas Cadastradas no Plano de Ensino ({subjects.length})
            </h4>
            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
              {subjects.map((sub) => (
                <div key={sub.id} className="p-2.5 bg-papel border border-linha rounded text-xs flex items-center justify-between">
                  <div>
                    <div className="font-semibold text-tinta">{sub.name}</div>
                    <div className="text-[10px] text-tinta-suave font-mono">{sub.code}</div>
                  </div>
                  {sub.code === 'PORT' || sub.code === 'MAT' ? (
                    <Badge variant="folha">Nuclear</Badge>
                  ) : null}
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* ABA 3: ANO LECTIVO & TRIMESTRES */}
      {activeTab === 'trimestres' && (
        <div className="space-y-4">
          {academicYears.map((year) => (
            <div key={year.id} className="bg-superficie border border-linha rounded p-5 space-y-4">
              <div className="flex items-center justify-between border-b border-linha pb-3">
                <div className="flex items-center gap-3">
                  <h3 className="font-serif font-bold text-lg text-mata-escura">
                    {year.name}
                  </h3>
                  {year.is_active && <Badge variant="folha">Ano Lectivo Activo</Badge>}
                  {year.is_closed && <Badge variant="tijolo">Encerrado</Badge>}
                </div>
                <div className="text-xs text-tinta-suave font-mono tabular-nums">
                  {new Date(year.start_date).toLocaleDateString('pt-AO')} a {new Date(year.end_date).toLocaleDateString('pt-AO')}
                </div>
              </div>

              {/* 3 Trimestres */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                {year.terms?.map((term) => (
                  <div key={term.id} className="p-4 bg-papel border border-linha rounded space-y-2">
                    <div className="flex items-center justify-between">
                      <span className="font-serif font-bold text-sm text-mata">
                        {term.name}
                      </span>
                      <Badge variant={term.is_closed ? 'tijolo' : 'folha'}>
                        {term.is_closed ? 'Encerrado' : 'Aberto'}
                      </Badge>
                    </div>

                    <div className="text-xs space-y-1 text-tinta-suave pt-1">
                      <div>
                        Início: <span className="font-mono text-tinta">{new Date(term.start_date).toLocaleDateString('pt-AO')}</span>
                      </div>
                      <div>
                        Término: <span className="font-mono text-tinta">{new Date(term.end_date).toLocaleDateString('pt-AO')}</span>
                      </div>
                      <div className="pt-2 border-t border-linha">
                        <span className="font-semibold text-barro block">Prazo do Conselho de Notas:</span>
                        <span className="font-mono text-tinta">
                          {term.grades_deadline
                            ? new Date(term.grades_deadline).toLocaleDateString('pt-AO')
                            : 'A definir'}
                        </span>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Modal de Criação de Turma */}
      {isClassModalOpen && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
          <div className="bg-superficie border border-linha rounded w-full max-w-lg p-6 shadow-modal">
            <div className="flex items-center justify-between pb-3 border-b border-linha mb-4">
              <h2 className="text-base font-bold text-mata-escura font-serif">
                Cadastrar Nova Turma Escolar
              </h2>
              <button
                onClick={() => setIsClassModalOpen(false)}
                className="text-tinta-suave hover:text-tinta text-lg font-bold"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleCreateClass} className="space-y-3.5">
              <div className="grid grid-cols-2 gap-3">
                <div className="flex flex-col gap-1">
                  <label className="text-xs font-semibold text-tinta uppercase tracking-wide">
                    Classe Escolar *
                  </label>
                  <select
                    value={classFormData.grade_id}
                    onChange={(e) =>
                      setClassFormData({ ...classFormData, grade_id: e.target.value })
                    }
                    className="w-full px-3 py-2 text-sm bg-superficie text-tinta border border-linha rounded focus:border-mata focus:outline-none"
                    required
                  >
                    <option value="">Selecione a Classe...</option>
                    {grades.map((g) => (
                      <option key={g.id} value={g.id}>
                        {g.name}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="flex flex-col gap-1">
                  <label className="text-xs font-semibold text-tinta uppercase tracking-wide">
                    Turno *
                  </label>
                  <select
                    value={classFormData.shift_id}
                    onChange={(e) =>
                      setClassFormData({ ...classFormData, shift_id: e.target.value })
                    }
                    className="w-full px-3 py-2 text-sm bg-superficie text-tinta border border-linha rounded focus:border-mata focus:outline-none"
                    required
                  >
                    <option value="">Selecione o Turno...</option>
                    {shifts.map((s) => (
                      <option key={s.id} value={s.id}>
                        {s.name} ({s.start_time.substring(0, 5)} - {s.end_time.substring(0, 5)})
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <Input
                  label="Designação da Turma *"
                  value={classFormData.name}
                  onChange={(e) =>
                    setClassFormData({ ...classFormData, name: e.target.value })
                  }
                  placeholder="ex: 7ª Classe C ou 1ª B"
                  required
                />

                <div className="flex flex-col gap-1">
                  <label className="text-xs font-semibold text-tinta uppercase tracking-wide">
                    Sala de Aula Física
                  </label>
                  <select
                    value={classFormData.classroom_id}
                    onChange={(e) =>
                      setClassFormData({ ...classFormData, classroom_id: e.target.value })
                    }
                    className="w-full px-3 py-2 text-sm bg-superficie text-tinta border border-linha rounded focus:border-mata focus:outline-none"
                  >
                    <option value="">Sem sala atribuída</option>
                    {classrooms.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.name} ({c.capacity} lugares) - {c.location_block}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <Input
                label="Lotação Máxima (Alunos) *"
                type="number"
                min="10"
                max="60"
                value={classFormData.max_students}
                onChange={(e) =>
                  setClassFormData({
                    ...classFormData,
                    max_students: parseInt(e.target.value) || 45,
                  })
                }
                required
              />

              <div className="flex justify-end gap-2 pt-4 border-t border-linha">
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => setIsClassModalOpen(false)}
                >
                  Cancelar
                </Button>
                <Button type="submit" variant="primary" isLoading={createClassLoading}>
                  Criar Turma
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal de Atribuição Docente */}
      {assigningClass && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
          <div className="bg-superficie border border-linha rounded w-full max-w-md p-6 shadow-modal">
            <div className="flex items-center justify-between pb-3 border-b border-linha mb-4">
              <div>
                <h2 className="text-base font-bold text-mata-escura font-serif">
                  Atribuição de Serviço Docente
                </h2>
                <div className="text-xs text-tinta-suave">
                  Turma: <strong className="text-tinta">{assigningClass.name}</strong> ({assigningClass.grade.name})
                </div>
              </div>
              <button
                onClick={() => setAssigningClass(null)}
                className="text-tinta-suave hover:text-tinta text-lg font-bold"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleAssignTeacher} className="space-y-4">
              <div className="flex flex-col gap-1">
                <label className="text-xs font-semibold text-tinta uppercase tracking-wide">
                  Disciplina Curricular *
                </label>
                <select
                  value={assignFormData.subject_id}
                  onChange={(e) =>
                    setAssignFormData({ ...assignFormData, subject_id: e.target.value })
                  }
                  className="w-full px-3 py-2 text-sm bg-superficie text-tinta border border-linha rounded focus:border-mata focus:outline-none"
                  required
                >
                  <option value="">Selecione a Disciplina...</option>
                  {subjects.map((sub) => (
                    <option key={sub.id} value={sub.id}>
                      {sub.name} ({sub.code})
                    </option>
                  ))}
                </select>
              </div>

              <div className="flex flex-col gap-1">
                <label className="text-xs font-semibold text-tinta uppercase tracking-wide">
                  Professor / Docente *
                </label>
                <select
                  value={assignFormData.staff_id}
                  onChange={(e) =>
                    setAssignFormData({ ...assignFormData, staff_id: e.target.value })
                  }
                  className="w-full px-3 py-2 text-sm bg-superficie text-tinta border border-linha rounded focus:border-mata focus:outline-none"
                  required
                >
                  <option value="">Selecione o Professor...</option>
                  {staffList.map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.full_name} ({s.staff_code} - {s.specialty || s.position})
                    </option>
                  ))}
                </select>
              </div>

              <div className="flex justify-end gap-2 pt-4 border-t border-linha">
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => setAssigningClass(null)}
                >
                  Cancelar
                </Button>
                <Button type="submit" variant="primary" isLoading={assignLoading}>
                  Vincular Professor
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
