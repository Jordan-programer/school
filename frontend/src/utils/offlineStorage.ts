import type { OfflineMutation, SaveAttendancePayload, AttendanceSheetResponse } from '../types/attendance'

const MUTATIONS_KEY = 'sisgestesc_offline_attendance_mutations'
const CACHED_SHEETS_PREFIX = 'sisgestesc_cached_sheet_'

export const offlineStorage = {
  getPendingMutations(): OfflineMutation[] {
    try {
      const data = localStorage.getItem(MUTATIONS_KEY)
      return data ? JSON.parse(data) : []
    } catch {
      return []
    }
  },

  addPendingMutation(
    payload: SaveAttendancePayload,
    meta: { class_name: string; date_str: string; lesson_number: number }
  ): OfflineMutation {
    const mutations = this.getPendingMutations()
    const client_mutation_id = payload.client_mutation_id || crypto.randomUUID()

    const newMutation: OfflineMutation = {
      client_mutation_id,
      created_at: new Date().toISOString(),
      class_name: meta.class_name,
      date_str: meta.date_str,
      lesson_number: meta.lesson_number,
      payload: {
        ...payload,
        client_mutation_id,
      },
    }

    // Remover mutação anterior se já existia para o mesmo tempo/turma/data
    const filtered = mutations.filter(
      (m) =>
        !(
          m.payload.class_group_id === payload.class_group_id &&
          m.payload.date === payload.date &&
          m.payload.lesson_number === payload.lesson_number &&
          (m.payload.subject_id ?? '') === (payload.subject_id ?? '')
        )
    )

    filtered.push(newMutation)
    localStorage.setItem(MUTATIONS_KEY, JSON.stringify(filtered))
    return newMutation
  },

  removePendingMutations(processedIds: string[]): void {
    const mutations = this.getPendingMutations()
    const remaining = mutations.filter((m) => !processedIds.includes(m.client_mutation_id))
    localStorage.setItem(MUTATIONS_KEY, JSON.stringify(remaining))
  },

  clearPendingMutations(): void {
    localStorage.removeItem(MUTATIONS_KEY)
  },

  cacheSheet(key: string, data: AttendanceSheetResponse): void {
    try {
      localStorage.setItem(`${CACHED_SHEETS_PREFIX}${key}`, JSON.stringify(data))
    } catch (e) {
      console.warn('Não foi possível armazenar a folha de chamada em cache local', e)
    }
  },

  getCachedSheet(key: string): AttendanceSheetResponse | null {
    try {
      const data = localStorage.getItem(`${CACHED_SHEETS_PREFIX}${key}`)
      return data ? JSON.parse(data) : null
    } catch {
      return null
    }
  },
}
