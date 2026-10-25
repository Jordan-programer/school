import React, { useState, useEffect, useCallback } from 'react'
import {
  CalendarCheck,
  Wifi,
  WifiOff,
  RefreshCw,
  CheckCircle,
  AlertCircle,
  Check,
  X,
  FileText,
  Save,
  HelpCircle,
  History,
  AlertTriangle,
} from 'lucide-react'
import { attendanceService } from '../../services/attendanceService'
import { offlineStorage } from '../../utils/offlineStorage'
import { api } from '../../services/api'
import type {
  AttendanceStatus,
  AttendanceSheetItem,
  AttendanceSheetResponse,
  SaveAttendancePayload,
  OfflineMutation,
  StudentAttendanceSummary,
} from '../../types/attendance'
import type { ClassGroup, Subject } from '../../types/academic'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'

export const AttendanceScreen: React.FC = () => {
  // Estado de Conectividade
  const [isOnline, setIsOnline] = useState<boolean>(navigator.onLine)
  const [pendingMutations, setPendingMutations] = useState<OfflineMutation[]>([])
  const [syncingOffline, setSyncingOffline] = useState<boolean>(false)

  // Dados auxiliares para dropdowns
  const [classGroups, setClassGroups] = useState<ClassGroup[]>([])
  const [subjects, setSubjects] = useState<Subject[]>([])

  // Selecção de Sessão de Aula
  const [selectedClassId, setSelectedClassId] = useState<string>('')
  const [selectedSubjectId, setSelectedSubjectId] = useState<string>('')
  const [selectedDate, setSelectedDate] = useState<string>(new Date().toISOString().split('T')[0])
  const [selectedLesson, setSelectedLesson] = useState<number>(1)
  const [lessonTopic, setLessonTopic] = useState<string>('')
  const [sessionNotes, setSessionNotes] = useState<string>('')

  // Folha de Chamada
  const [sheetData, setSheetData] = useState<AttendanceSheetResponse | null>(null)
  const [students, setStudents] = useState<AttendanceSheetItem[]>([])
  const [loadingSheet, setLoadingSheet] = useState<boolean>(false)
  const [savingAttendance, setSavingAttendance] = useState<boolean>(false)

  // Notificações
  const [successMessage, setSuccessMessage] = useState<string | null>(null)
  const [errorMessage, setErrorMessage] = useState<string | null>(null)

  // Modal: Justificar Falta
  const [isJustifyModalOpen, setIsJustifyModalOpen] = useState<boolean>(false)
  const [targetStudentForJustify, setTargetStudentForJustify] = useState<AttendanceSheetItem | null>(null)
  const [justificationReason, setJustificationReason] = useState<string>('')
  const [justifying, setJustifying] = useState<boolean>(false)

  // Modal: Resumo do Aluno
  const [isSummaryModalOpen, setIsSummaryModalOpen] = useState<boolean>(false)
  const [studentSummary, setStudentSummary] = useState<StudentAttendanceSummary | null>(null)
  const [loadingSummary, setLoadingSummary] = useState<boolean>(false)

  // Monitorar Conectividade de Rede
  useEffect(() => {
    const handleOnline = () => setIsOnline(true)
    const handleOffline = () => setIsOnline(false)

    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)

    // Carregar fila de mutações offline
    setPendingMutations(offlineStorage.getPendingMutations())

    return () => {
      window.removeEventListener('online', handleOnline)
      window.removeEventListener('offline', handleOffline)
    }
  }, [])

  // Carregar turmas e disciplinas disponíveis
  useEffect(() => {
    async function loadAcademicOptions() {
      try {
        const [classesRes, structRes] = await Promise.all([
          api.get('/class-groups'),
          api.get('/academic-structure'),
        ])
        setClassGroups(classesRes.data.data || [])
        setSubjects(structRes.data.subjects || [])

        if (classesRes.data.data?.length > 0) {
          setSelectedClassId(classesRes.data.data[0].id)
        }
      } catch (err) {
        console.error('Erro ao carregar opções académicas:', err)
      }
    }
    loadAcademicOptions()
  }, [])

  // Carregar Folha de Chamada (com suporte a Cache Local)
  const loadSheet = useCallback(async () => {
    if (!selectedClassId) return

    setLoadingSheet(true)
    setErrorMessage(null)
    const cacheKey = `${selectedClassId}_${selectedDate}_${selectedLesson}_${selectedSubjectId}`

    try {
      if (navigator.onLine) {
        const res = await attendanceService.getSheet({
          class_group_id: selectedClassId,
          date: selectedDate,
          lesson_number: selectedLesson,
          subject_id: selectedSubjectId || undefined,
        })
        setSheetData(res)
        setStudents(res.sheet)
        setLessonTopic(res.session.topic || '')
        setSessionNotes(res.session.notes || '')
        offlineStorage.cacheSheet(cacheKey, res)
      } else {
        // Tentar obter da cache offline
        const cached = offlineStorage.getCachedSheet(cacheKey)
        if (cached) {
          setSheetData(cached)
          setStudents(cached.sheet)
          setLessonTopic(cached.session.topic || '')
          setSessionNotes(cached.session.notes || '')
          setSuccessMessage('Folha de chamada carregada da memória offline local.')
        } else {
          setErrorMessage('Sem ligação à rede e sem cópia local em cache para esta aula. Ligue-se à rede pelo menos uma vez para guardar a turma.')
        }
      }
    } catch (err: any) {
      // Se falhar a requisição, tentar fallback na cache
      const cached = offlineStorage.getCachedSheet(cacheKey)
      if (cached) {
        setSheetData(cached)
        setStudents(cached.sheet)
        setLessonTopic(cached.session.topic || '')
        setSessionNotes(cached.session.notes || '')
        setSuccessMessage('Rede instável: a carregar a folha de chamada a partir do armazenamento local.')
      } else {
        setErrorMessage(err.response?.data?.message || 'Falha ao carregar lista de chamada da turma.')
      }
    } finally {
      setLoadingSheet(false)
    }
  }, [selectedClassId, selectedDate, selectedLesson, selectedSubjectId])

  useEffect(() => {
    if (selectedClassId) {
      loadSheet()
    }
  }, [selectedClassId, selectedDate, selectedLesson, selectedSubjectId, loadSheet])

  // Alterar Status de Presença de um Aluno
  const handleStatusChange = (studentId: string, newStatus: AttendanceStatus) => {
    setStudents((prev) =>
      prev.map((s) => {
        if (s.student_id === studentId) {
          const abbrevMap: Record<AttendanceStatus, string> = {
            PRESENTE: 'P',
            FALTA_INJUSTIFICADA: 'F',
            FALTA_JUSTIFICADA: 'FJ',
            ATRASO: 'A',
          }
          return {
            ...s,
            current_status: newStatus,
            current_abbreviation: abbrevMap[newStatus],
          }
        }
        return s
      })
    )
  }

  // Marcar Todos como Presentes (Atalho rápido para professores)
  const handleMarkAllPresent = () => {
    setStudents((prev) =>
      prev.map((s) => ({
        ...s,
        current_status: 'PRESENTE',
        current_abbreviation: 'P',
      }))
    )
    setSuccessMessage('Todos os alunos foram marcados como Presentes.')
    setTimeout(() => setSuccessMessage(null), 3000)
  }

  // Guardar Chamada (Online directo ou Enfileirar Offline)
  const handleSaveAttendance = async () => {
    if (!selectedClassId || students.length === 0) return

    setSavingAttendance(true)
    setErrorMessage(null)

    const payload: SaveAttendancePayload = {
      class_group_id: selectedClassId,
      subject_id: selectedSubjectId || null,
      date: selectedDate,
      lesson_number: selectedLesson,
      topic: lessonTopic,
      notes: sessionNotes,
      records: students.map((s) => ({
        student_id: s.student_id,
        enrollment_id: s.enrollment_id,
        status: s.current_status,
        justification_reason: s.justification_reason,
      })),
    }

    const currentClass = classGroups.find((cg) => cg.id === selectedClassId)
    const className = currentClass ? currentClass.name : 'Turma'

    if (navigator.onLine) {
      try {
        const response = await attendanceService.save(payload)
        setSuccessMessage(response.message)
        // Atualizar sheet cache
        if (sheetData) {
          const updatedSheet: AttendanceSheetResponse = {
            ...sheetData,
            session: {
              ...sheetData.session,
              is_already_recorded: true,
              topic: lessonTopic,
              notes: sessionNotes,
            },
            sheet: students,
          }
          const cacheKey = `${selectedClassId}_${selectedDate}_${selectedLesson}_${selectedSubjectId}`
          offlineStorage.cacheSheet(cacheKey, updatedSheet)
        }
        setTimeout(() => setSuccessMessage(null), 5000)
      } catch (err: any) {
        // Falha de rede durante o save: fallback automático para fila offline!
        offlineStorage.addPendingMutation(payload, {
          class_name: className,
          date_str: selectedDate,
          lesson_number: selectedLesson,
        })
        setPendingMutations(offlineStorage.getPendingMutations())
        setSuccessMessage('Falha temporária de rede: Chamada guardada localmente com segurança no dispositivo. Será sincronizada assim que a ligação estabilizar.')
      } finally {
        setSavingAttendance(false)
      }
    } else {
      // Modo Offline Explícito
      offlineStorage.addPendingMutation(payload, {
        class_name: className,
        date_str: selectedDate,
        lesson_number: selectedLesson,
      })
      setPendingMutations(offlineStorage.getPendingMutations())
      setSuccessMessage(`Chamada guardada em modo offline (${className} - ${selectedLesson}º tempo). Sincronize quando tiver energia ou rede.`)
      setSavingAttendance(false)
      setTimeout(() => setSuccessMessage(null), 5000)
    }
  }

  // Sincronizar Fila Offline
  const handleSyncOffline = async () => {
    if (pendingMutations.length === 0) return

    setSyncingOffline(true)
    setErrorMessage(null)

    try {
      const payloads = pendingMutations.map((m) => m.payload)
      const res = await attendanceService.syncOffline(payloads)

      setSuccessMessage(res.message)
      offlineStorage.removePendingMutations(res.processed_ids)
      setPendingMutations(offlineStorage.getPendingMutations())
      loadSheet()
      setTimeout(() => setSuccessMessage(null), 5000)
    } catch (err: any) {
      setErrorMessage(err.response?.data?.message || 'Falha ao sincronizar chamadas com o servidor.')
    } finally {
      setSyncingOffline(false)
    }
  }

  // Submeter Justificação de Falta
  const handleJustifySubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!targetStudentForJustify) return

    setJustifying(true)
    try {
      if (targetStudentForJustify.record_id) {
        const res = await attendanceService.justify(
          targetStudentForJustify.record_id,
          justificationReason
        )
        setSuccessMessage(res.message)
      }
      handleStatusChange(targetStudentForJustify.student_id, 'FALTA_JUSTIFICADA')
      setIsJustifyModalOpen(false)
      setJustificationReason('')
      setTimeout(() => setSuccessMessage(null), 4000)
    } catch (err: any) {
      setErrorMessage(err.response?.data?.message || 'Erro ao justificar falta do aluno.')
    } finally {
      setJustifying(false)
    }
  }

  // Abrir Resumo de Assiduidade do Aluno
  const handleOpenStudentSummary = async (studentId: string) => {
    setIsSummaryModalOpen(true)
    setLoadingSummary(true)
    try {
      const res = await attendanceService.getStudentSummary(studentId)
      setStudentSummary(res)
    } catch (err) {
      console.error('Erro ao buscar resumo de assiduidade:', err)
    } finally {
      setLoadingSummary(false)
    }
  }

  // Métricas da Sessão Actual
  const totalStudents = students.length
  const presentCount = students.filter((s) => s.current_status === 'PRESENTE').length
  const unjustifiedCount = students.filter((s) => s.current_status === 'FALTA_INJUSTIFICADA').length
  const justifiedCount = students.filter((s) => s.current_status === 'FALTA_JUSTIFICADA').length
  const lateCount = students.filter((s) => s.current_status === 'ATRASO').length
  const attendancePercentage = totalStudents > 0 ? Math.round(((presentCount + justifiedCount) / totalStudents) * 100) : 0

  return (
    <div className="space-y-6">
      {/* Cabeçalho da Página com Estado de Conectividade */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-linha">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="font-serif text-2xl font-bold text-mata-escura">
              Chamada Diária & Assiduidade
            </h1>
            <Badge variant="folha">Módulo 5</Badge>
          </div>
          <p className="text-xs text-tinta-suave mt-1">
            Lançamento de presenças por tempo lectivo, tolerância para quebras de rede/energia (modo offline) e justificações pedagógicas.
          </p>
        </div>

        {/* Indicador de Conectividade e Sincronização */}
        <div className="flex items-center gap-3">
          <div
            className={`px-3 py-1.5 rounded-full border text-xs font-medium flex items-center gap-2 ${
              isOnline
                ? 'bg-folha/10 border-folha/30 text-mata-escura'
                : 'bg-girassol/20 border-girassol/40 text-[#8C6D1F]'
            }`}
          >
            {isOnline ? (
              <>
                <Wifi className="w-3.5 h-3.5 text-folha" />
                <span>Online (Conectado)</span>
              </>
            ) : (
              <>
                <WifiOff className="w-3.5 h-3.5 text-girassol" />
                <span>Modo Offline (Cache Local)</span>
              </>
            )}
          </div>

          {pendingMutations.length > 0 && (
            <Button
              onClick={handleSyncOffline}
              disabled={syncingOffline || !isOnline}
              className="bg-girassol hover:bg-girassol/90 text-mata-escura font-bold text-xs flex items-center gap-2 border border-[#8C6D1F]/30 shadow-sm"
            >
              <RefreshCw className={`w-3.5 h-3.5 ${syncingOffline ? 'animate-spin' : ''}`} />
              <span>Sincronizar ({pendingMutations.length})</span>
            </Button>
          )}
        </div>
      </div>

      {/* Alertas */}
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

      {/* Barra de Selecção de Aula */}
      <div className="p-4 bg-superficie rounded border border-linha shadow-sm space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
          {/* Selecção de Turma */}
          <div>
            <label className="block text-xs font-medium text-tinta mb-1">
              Turma Escolar *
            </label>
            <select
              value={selectedClassId}
              onChange={(e) => setSelectedClassId(e.target.value)}
              className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
            >
              {classGroups.map((cg) => (
                <option key={cg.id} value={cg.id}>
                  {cg.name} ({cg.shift?.name || 'Diurno'})
                </option>
              ))}
            </select>
          </div>

          {/* Selecção de Disciplina */}
          <div>
            <label className="block text-xs font-medium text-tinta mb-1">
              Disciplina (se aplicável)
            </label>
            <select
              value={selectedSubjectId}
              onChange={(e) => setSelectedSubjectId(e.target.value)}
              className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
            >
              <option value="">-- Aula Geral / Mono-docência --</option>
              {subjects.map((sub) => (
                <option key={sub.id} value={sub.id}>
                  {sub.name} ({sub.code})
                </option>
              ))}
            </select>
          </div>

          {/* Data da Aula */}
          <div>
            <div className="flex items-center justify-between mb-1">
              <label className="text-xs font-medium text-tinta">Data da Aula *</label>
              <button
                type="button"
                onClick={() => setSelectedDate(new Date().toISOString().split('T')[0])}
                className="text-[10px] text-mata hover:underline"
              >
                Hoje
              </button>
            </div>
            <Input
              type="date"
              value={selectedDate}
              onChange={(e) => setSelectedDate(e.target.value)}
            />
          </div>

          {/* Tempo Lectivo */}
          <div>
            <label className="block text-xs font-medium text-tinta mb-1">
              Tempo Lectivo *
            </label>
            <select
              value={selectedLesson}
              onChange={(e) => setSelectedLesson(Number(e.target.value))}
              className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
            >
              <option value={1}>1º Tempo</option>
              <option value={2}>2º Tempo</option>
              <option value={3}>3º Tempo</option>
              <option value={4}>4º Tempo</option>
              <option value={5}>5º Tempo</option>
              <option value={6}>6º Tempo</option>
            </select>
          </div>
        </div>

        {/* Sumário da Aula */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-3 pt-2 border-t border-linha/60">
          <div className="md:col-span-2">
            <label className="block text-xs font-medium text-tinta mb-1">
              Sumário da Aula (Tópico leccionado conforme diário de classe)
            </label>
            <Input
              placeholder="Ex: Leitura compreensiva do texto 'O Rio Kwanza' e identificação de adjectivos..."
              value={lessonTopic}
              onChange={(e) => setLessonTopic(e.target.value)}
            />
          </div>
          <div>
            <label className="block text-xs font-medium text-tinta mb-1">
              Observações Gerais
            </label>
            <Input
              placeholder="Ex: Aula prática no laboratório, chuva forte..."
              value={sessionNotes}
              onChange={(e) => setSessionNotes(e.target.value)}
            />
          </div>
        </div>
      </div>

      {/* Barra de Resumo e Acção Rápida */}
      <div className="p-4 bg-superficie rounded border border-linha shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        {/* Chips de Resumo Numérico */}
        <div className="flex flex-wrap items-center gap-3 text-xs">
          <div className="px-3 py-1 bg-papel rounded border border-linha font-medium">
            Total Alunos: <strong className="text-mata-escura font-mono">{totalStudents}</strong>
          </div>

          <div className="px-3 py-1 bg-folha/15 text-mata-escura rounded border border-folha/30 font-medium">
            Presentes (P): <strong className="font-mono">{presentCount}</strong>
          </div>

          <div className="px-3 py-1 bg-tijolo/15 text-tijolo rounded border border-tijolo/30 font-medium">
            Faltas Injustificadas (F): <strong className="font-mono">{unjustifiedCount}</strong>
          </div>

          <div className="px-3 py-1 bg-girassol/20 text-[#8C6D1F] rounded border border-girassol/40 font-medium">
            Faltas Justificadas (FJ): <strong className="font-mono">{justifiedCount}</strong>
          </div>

          <div className="px-3 py-1 bg-[#2C5282]/15 text-[#2C5282] rounded border border-[#2C5282]/30 font-medium">
            Atrasos (A): <strong className="font-mono">{lateCount}</strong>
          </div>

          <div className="px-3 py-1 bg-mata/10 text-mata font-bold rounded border border-mata/20">
            Frequência: {attendancePercentage}%
          </div>
        </div>

        {/* Acções Rápidas */}
        <div className="flex items-center gap-2">
          <Button
            type="button"
            variant="secondary"
            onClick={handleMarkAllPresent}
            className="text-xs flex items-center gap-1.5"
          >
            <Check className="w-3.5 h-3.5 text-folha" />
            <span>Todos Presentes</span>
          </Button>

          <Button
            type="button"
            onClick={handleSaveAttendance}
            disabled={savingAttendance || students.length === 0}
            className="bg-mata hover:bg-mata-escura text-white shadow-sm flex items-center gap-2 text-xs font-semibold"
          >
            {savingAttendance ? (
              <div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" />
            ) : (
              <Save className="w-4 h-4" />
            )}
            <span>{isOnline ? 'Gravar Chamada' : 'Gravar em Cache Offline'}</span>
          </Button>
        </div>
      </div>

      {/* Tabela da Folha de Chamada */}
      <div className="bg-superficie rounded border border-linha shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-[#FAF7F2] border-b border-linha text-tinta-suave uppercase text-[10px] tracking-wider">
              <tr>
                <th className="py-3 px-4 w-12 text-center">Nº</th>
                <th className="py-3 px-4">Nome do Aluno</th>
                <th className="py-3 px-4">Nº Processo</th>
                <th className="py-3 px-4">Género</th>
                <th className="py-3 px-4 text-center">Presença na Aula</th>
                <th className="py-3 px-4 text-center">Estado Actual</th>
                <th className="py-3 px-4 text-right">Acções</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-linha text-tinta">
              {loadingSheet ? (
                <tr>
                  <td colSpan={7} className="py-12 text-center text-tinta-suave">
                    <div className="flex flex-col items-center gap-2">
                      <div className="w-6 h-6 border-2 border-mata border-t-transparent rounded-full animate-spin" />
                      <span>A carregar livro de chamada da turma...</span>
                    </div>
                  </td>
                </tr>
              ) : students.length === 0 ? (
                <tr>
                  <td colSpan={7} className="py-12 text-center text-tinta-suave">
                    <CalendarCheck className="w-8 h-8 mx-auto mb-2 text-linha" />
                    <p className="font-medium">Nenhum aluno enturmado nesta turma.</p>
                    <p className="text-[11px] mt-0.5">
                      Verifique o Módulo de Matrículas e confirme a atribuição de turmas aos alunos.
                    </p>
                  </td>
                </tr>
              ) : (
                students.map((student) => (
                  <tr key={student.student_id} className="hover:bg-papel/60 transition-colors">
                    {/* Nº de Ordem */}
                    <td className="py-3 px-4 text-center font-mono font-bold text-mata-escura">
                      <span className="w-6 h-6 rounded bg-papel border border-linha inline-flex items-center justify-center">
                        {student.order_number}
                      </span>
                    </td>

                    {/* Nome do Aluno */}
                    <td className="py-3 px-4 font-semibold text-mata-escura">
                      {student.full_name}
                      {student.justification_reason && (
                        <div className="text-[10px] text-girassol font-normal flex items-center gap-1 mt-0.5">
                          <HelpCircle className="w-3 h-3" />
                          <span>Motivo: {student.justification_reason}</span>
                        </div>
                      )}
                    </td>

                    {/* UID */}
                    <td className="py-3 px-4 font-mono text-tinta-suave">
                      {student.student_uid}
                    </td>

                    {/* Género */}
                    <td className="py-3 px-4 text-tinta-suave">
                      {student.gender === 'M' ? 'Masculino' : 'Feminino'}
                    </td>

                    {/* Botões Rápidos de Alternância (P, F, FJ, A) */}
                    <td className="py-3 px-4">
                      <div className="flex items-center justify-center gap-1">
                        {/* Presente */}
                        <button
                          type="button"
                          onClick={() => handleStatusChange(student.student_id, 'PRESENTE')}
                          title="Marcar Presente (P)"
                          className={`w-7 h-7 rounded text-[11px] font-bold border transition-colors ${
                            student.current_status === 'PRESENTE'
                              ? 'bg-folha text-white border-folha shadow-sm'
                              : 'bg-superficie text-tinta hover:bg-folha/15 border-linha'
                          }`}
                        >
                          P
                        </button>

                        {/* Falta Injustificada */}
                        <button
                          type="button"
                          onClick={() => handleStatusChange(student.student_id, 'FALTA_INJUSTIFICADA')}
                          title="Marcar Falta Injustificada (F)"
                          className={`w-7 h-7 rounded text-[11px] font-bold border transition-colors ${
                            student.current_status === 'FALTA_INJUSTIFICADA'
                              ? 'bg-tijolo text-white border-tijolo shadow-sm'
                              : 'bg-superficie text-tinta hover:bg-tijolo/15 border-linha'
                          }`}
                        >
                          F
                        </button>

                        {/* Falta Justificada */}
                        <button
                          type="button"
                          onClick={() => handleStatusChange(student.student_id, 'FALTA_JUSTIFICADA')}
                          title="Marcar Falta Justificada (FJ)"
                          className={`w-7 h-7 rounded text-[10px] font-bold border transition-colors ${
                            student.current_status === 'FALTA_JUSTIFICADA'
                              ? 'bg-girassol text-mata-escura border-[#8C6D1F] shadow-sm'
                              : 'bg-superficie text-tinta hover:bg-girassol/20 border-linha'
                          }`}
                        >
                          FJ
                        </button>

                        {/* Atraso */}
                        <button
                          type="button"
                          onClick={() => handleStatusChange(student.student_id, 'ATRASO')}
                          title="Marcar Atraso (A)"
                          className={`w-7 h-7 rounded text-[11px] font-bold border transition-colors ${
                            student.current_status === 'ATRASO'
                              ? 'bg-[#2C5282] text-white border-[#2C5282] shadow-sm'
                              : 'bg-superficie text-tinta hover:bg-[#2C5282]/15 border-linha'
                          }`}
                        >
                          A
                        </button>
                      </div>
                    </td>

                    {/* Badge do Estado Actual */}
                    <td className="py-3 px-4 text-center">
                      <Badge
                        variant={
                          student.current_status === 'PRESENTE'
                            ? 'folha'
                            : student.current_status === 'FALTA_JUSTIFICADA'
                            ? 'girassol'
                            : student.current_status === 'FALTA_INJUSTIFICADA'
                            ? 'tijolo'
                            : 'giz'
                        }
                      >
                        {student.current_status === 'PRESENTE'
                          ? 'Presente'
                          : student.current_status === 'FALTA_JUSTIFICADA'
                          ? 'Justificada'
                          : student.current_status === 'FALTA_INJUSTIFICADA'
                          ? 'Injustificada'
                          : 'Atraso'}
                      </Badge>
                    </td>

                    {/* Acções */}
                    <td className="py-3 px-4 text-right">
                      <div className="flex items-center justify-end gap-1">
                        {(student.current_status === 'FALTA_INJUSTIFICADA' ||
                          student.current_status === 'FALTA_JUSTIFICADA') && (
                          <button
                            type="button"
                            onClick={() => {
                              setTargetStudentForJustify(student)
                              setJustificationReason(student.justification_reason || '')
                              setIsJustifyModalOpen(true)
                            }}
                            title="Justificar Falta com Documento"
                            className="p-1.5 text-tinta-suave hover:text-girassol hover:bg-girassol/10 rounded transition-colors"
                          >
                            <FileText className="w-4 h-4" />
                          </button>
                        )}

                        <button
                          type="button"
                          onClick={() => handleOpenStudentSummary(student.student_id)}
                          title="Histórico e Resumo de Assiduidade do Aluno"
                          className="p-1.5 text-tinta-suave hover:text-mata hover:bg-mata/10 rounded transition-colors"
                        >
                          <History className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal: Justificação de Falta */}
      {isJustifyModalOpen && targetStudentForJustify && (
        <div className="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-superficie rounded-lg border border-linha shadow-xl w-full max-w-md my-8 overflow-hidden">
            <div className="px-5 py-4 bg-mata-escura text-white flex items-center justify-between">
              <h3 className="font-serif font-bold text-sm">
                Justificar Falta de {targetStudentForJustify.full_name}
              </h3>
              <button onClick={() => setIsJustifyModalOpen(false)} className="text-white/70 hover:text-white">
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleJustifySubmit} className="p-5 space-y-4">
              <div className="text-xs text-tinta-suave">
                Data da Aula: <strong className="text-mata-escura">{selectedDate}</strong> • Tempo:{' '}
                <strong className="text-mata-escura">{selectedLesson}º Tempo</strong>
              </div>

              <div>
                <label className="block text-xs font-medium text-tinta mb-1">
                  Motivo da Justificação *
                </label>
                <textarea
                  required
                  rows={3}
                  placeholder="Ex: Apresentou atestado médico passado pelo Centro Materno Infantil ou declaração do encarregado de educação..."
                  value={justificationReason}
                  onChange={(e) => setJustificationReason(e.target.value)}
                  className="w-full px-3 py-2 text-xs rounded border border-linha bg-papel text-tinta focus:outline-none focus:border-mata"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-linha">
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => setIsJustifyModalOpen(false)}
                  className="text-xs"
                >
                  Cancelar
                </Button>
                <Button
                  type="submit"
                  disabled={justifying}
                  className="bg-mata text-white text-xs flex items-center gap-2"
                >
                  {justifying && (
                    <div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  )}
                  <span>Gravar Justificação</span>
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Resumo de Assiduidade e Alerta Angolano de Faltas */}
      {isSummaryModalOpen && (
        <div className="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-superficie rounded-lg border border-linha shadow-xl w-full max-w-md my-8 overflow-hidden">
            <div className="px-5 py-4 bg-mata-escura text-white flex items-center justify-between">
              <h3 className="font-serif font-bold text-sm">
                Ficha de Assiduidade do Aluno
              </h3>
              <button onClick={() => setIsSummaryModalOpen(false)} className="text-white/70 hover:text-white">
                <X className="w-4 h-4" />
              </button>
            </div>

            <div className="p-5 space-y-4">
              {loadingSummary ? (
                <div className="py-8 text-center text-xs text-tinta-suave">
                  <div className="w-6 h-6 border-2 border-mata border-t-transparent rounded-full animate-spin mx-auto mb-2" />
                  <span>A calcular taxa de assiduidade...</span>
                </div>
              ) : studentSummary ? (
                <div className="space-y-4 text-xs">
                  <div>
                    <h4 className="font-serif font-bold text-base text-mata-escura">
                      {studentSummary.student.full_name}
                    </h4>
                    <span className="font-mono text-tinta-suave text-[11px]">
                      Processo: {studentSummary.student.student_uid}
                    </span>
                  </div>

                  {/* Alerta de Risco de Exclusão */}
                  {studentSummary.summary.at_risk_of_exclusion && (
                    <div className="p-3 bg-tijolo/15 border border-tijolo/30 rounded text-tijolo space-y-1">
                      <div className="flex items-center gap-1.5 font-bold">
                        <AlertTriangle className="w-4 h-4 text-tijolo" />
                        <span>Alerta Regulamentar de Faltas</span>
                      </div>
                      <p className="text-[11px]">
                        {studentSummary.summary.risk_message || 'Aluno em risco de exclusão por faltas excessivas.'}
                      </p>
                    </div>
                  )}

                  {/* Estatísticas Numéricas */}
                  <div className="grid grid-cols-2 gap-3">
                    <div className="p-3 bg-papel rounded border border-linha">
                      <span className="text-[10px] text-tinta-suave block uppercase">Aulas Dadas</span>
                      <strong className="text-lg font-mono text-mata-escura">
                        {studentSummary.summary.total_sessions}
                      </strong>
                    </div>

                    <div className="p-3 bg-papel rounded border border-linha">
                      <span className="text-[10px] text-tinta-suave block uppercase">Taxa de Assiduidade</span>
                      <strong
                        className={`text-lg font-mono ${
                          studentSummary.summary.attendance_rate < 75 ? 'text-tijolo font-bold' : 'text-folha'
                        }`}
                      >
                        {studentSummary.summary.attendance_rate}%
                      </strong>
                    </div>

                    <div className="p-3 bg-folha/10 rounded border border-folha/20">
                      <span className="text-[10px] text-mata block uppercase">Presenças</span>
                      <strong className="text-lg font-mono text-mata">
                        {studentSummary.summary.present_count}
                      </strong>
                    </div>

                    <div className="p-3 bg-tijolo/10 rounded border border-tijolo/20">
                      <span className="text-[10px] text-tijolo block uppercase">Faltas Injustificadas</span>
                      <strong className="text-lg font-mono text-tijolo">
                        {studentSummary.summary.unjustified_count}
                      </strong>
                    </div>
                  </div>

                  <div className="flex justify-end pt-2">
                    <Button
                      type="button"
                      variant="secondary"
                      onClick={() => setIsSummaryModalOpen(false)}
                      className="text-xs"
                    >
                      Fechar
                    </Button>
                  </div>
                </div>
              ) : null}
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
export default AttendanceScreen
