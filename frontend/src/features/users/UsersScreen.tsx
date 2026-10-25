import React, { useState, useEffect } from 'react'
import { api } from '../../services/api'
import { useAuth } from '../../context/AuthContext'
import type { User, Role } from '../../types/auth'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Badge } from '../../components/ui/Badge'
import {
  Users as UsersIcon,
  UserPlus,
  Search,
  CheckCircle2,
  XCircle,
  Eye,
  RefreshCw,
  AlertTriangle,
} from 'lucide-react'

export const UsersScreen: React.FC = () => {
  const { user: currentUser, hasRole } = useAuth()
  const [users, setUsers] = useState<User[]>([])
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [selectedRole, setSelectedRole] = useState<string>('')
  const [selectedStatus, setSelectedStatus] = useState<string>('')

  // Modal de criação
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false)
  const [createLoading, setCreateLoading] = useState(false)
  const [formError, setFormError] = useState<string | null>(null)
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    password: 'Senha12345!',
    role: 'Professor' as Role,
    staff_code: '',
    position: 'Professor',
    specialty: '',
  })

  // Utilizador selecionado para visualização
  const [viewingUser, setViewingUser] = useState<User | null>(null)

  const canManageUsers = hasRole([
    'Director',
    'Subdirector Administrativo',
    'Secretaria',
  ])

  const isProvincialInspector = hasRole('Gestor Municipal/Provincial')

  const fetchUsers = async () => {
    setLoading(true)
    try {
      const params: any = {}
      if (search) params.search = search
      if (selectedRole) params.role = selectedRole
      if (selectedStatus !== '') params.is_active = selectedStatus

      const response = await api.get('/users', { params })
      setUsers(response.data.data)
    } catch (err) {
      console.error('Erro ao carregar utilizadores:', err)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    fetchUsers()
  }, [selectedRole, selectedStatus])

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    fetchUsers()
  }

  const handleToggleStatus = async (userToToggle: User) => {
    if (userToToggle.id === currentUser?.id) {
      alert('Não é permitido desactivar a sua própria conta de acesso.')
      return
    }

    try {
      const response = await api.patch(`/users/${userToToggle.id}/toggle-status`)
      setUsers((prev) =>
        prev.map((u) =>
          u.id === userToToggle.id ? { ...u, is_active: response.data.is_active } : u
        )
      )
    } catch (err: any) {
      alert(err.response?.data?.message || 'Erro ao alterar estado do utilizador.')
    }
  }

  const handleCreateUser = async (e: React.FormEvent) => {
    e.preventDefault()
    setCreateLoading(true)
    setFormError(null)

    try {
      const payload: any = {
        name: formData.name,
        email: formData.email,
        phone: formData.phone || null,
        password: formData.password,
        role: formData.role,
        is_active: true,
      }

      // Se tiver dados de staff
      if (formData.staff_code) {
        payload.staff = {
          staff_code: formData.staff_code,
          position: formData.position,
          specialty: formData.specialty || null,
        }
      }

      await api.post('/users', payload)
      setIsCreateModalOpen(false)
      setFormData({
        name: '',
        email: '',
        phone: '',
        password: 'Senha12345!',
        role: 'Professor',
        staff_code: '',
        position: 'Professor',
        specialty: '',
      })
      fetchUsers()
    } catch (err: any) {
      setFormError(
        err.response?.data?.message || 'Erro ao criar utilizador. Verifique os dados inseridos.'
      )
    } finally {
      setCreateLoading(false)
    }
  }

  const rolesList: Role[] = [
    'Director',
    'Subdirector Pedagógico',
    'Subdirector Administrativo',
    'Secretaria',
    'Professor',
    'Director de Turma',
    'Coordenador',
    'Tesoureiro',
    'Encarregado',
    'Aluno',
    'Gestor Municipal/Provincial',
  ]

  return (
    <div className="space-y-6">
      {/* Cabeçalho da Página */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-linha">
        <div>
          <div className="flex items-center gap-2">
            <UsersIcon className="w-5 h-5 text-mata" />
            <h1 className="text-xl font-bold text-mata-escura font-serif">
              Utilizadores e Perfis Funcionais
            </h1>
          </div>
          <p className="text-xs text-tinta-suave mt-1">
            Gestão de acessos aos 11 perfis escolares, credenciais e auditoria de pessoal docente e administrativo.
          </p>
        </div>

        {canManageUsers && !isProvincialInspector && (
          <Button
            variant="primary"
            onClick={() => setIsCreateModalOpen(true)}
            className="flex items-center gap-2"
          >
            <UserPlus className="w-4 h-4" />
            <span>Novo Utilizador</span>
          </Button>
        )}
      </div>

      {/* Aviso de Modo Só Leitura para Gestor Municipal/Provincial */}
      {isProvincialInspector && (
        <div className="p-3 bg-amber-50 border-l-4 border-girassol rounded-r text-xs text-tinta flex items-center gap-2">
          <AlertTriangle className="w-4 h-4 text-girassol shrink-0" />
          <span>
            <strong>Modo de Consulta Provincial:</strong> O seu perfil possui privilégios de auditoria e leitura em conformidade com o regulamento do Ministério da Educação.
          </span>
        </div>
      )}

      {/* Barra de Filtros e Pesquisa */}
      <div className="bg-superficie border border-linha rounded p-4 flex flex-col md:flex-row items-center gap-3 justify-between">
        <form onSubmit={handleSearchSubmit} className="flex items-center gap-2 w-full md:w-80">
          <Input
            placeholder="Pesquisar por nome, e-mail ou telefone..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="text-xs"
          />
          <Button type="submit" variant="secondary" size="sm">
            <Search className="w-3.5 h-3.5" />
          </Button>
        </form>

        <div className="flex items-center gap-3 w-full md:w-auto">
          {/* Filtro por Papel */}
          <select
            value={selectedRole}
            onChange={(e) => setSelectedRole(e.target.value)}
            className="px-2.5 py-1.5 text-xs bg-papel text-tinta border border-linha rounded focus:border-mata focus:outline-none"
          >
            <option value="">Todos os 11 Perfis</option>
            {rolesList.map((r) => (
              <option key={r} value={r}>
                {r}
              </option>
            ))}
          </select>

          {/* Filtro por Estado */}
          <select
            value={selectedStatus}
            onChange={(e) => setSelectedStatus(e.target.value)}
            className="px-2.5 py-1.5 text-xs bg-papel text-tinta border border-linha rounded focus:border-mata focus:outline-none"
          >
            <option value="">Todos os Estados</option>
            <option value="true">Activos</option>
            <option value="false">Desactivados</option>
          </select>

          <Button variant="ghost" size="sm" onClick={fetchUsers} title="Actualizar lista">
            <RefreshCw className="w-3.5 h-3.5" />
          </Button>
        </div>
      </div>

      {/* Tabela de Utilizadores Estilo Caderno Escolar */}
      <div className="bg-superficie border border-linha rounded overflow-hidden">
        {loading ? (
          <div className="p-12 text-center text-xs text-tinta-suave flex flex-col items-center gap-2">
            <div className="w-5 h-5 border-2 border-mata border-t-transparent rounded-full animate-spin" />
            <span>A carregar utilizadores do servidor...</span>
          </div>
        ) : users.length === 0 ? (
          <div className="p-12 text-center text-xs text-tinta-suave">
            Nenhum utilizador encontrado para os filtros selecionados.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left escola-table border-collapse">
              <thead>
                <tr>
                  <th>Nome Completo</th>
                  <th>Contacto / Telefone</th>
                  <th>Perfil Funcional</th>
                  <th>Cód. Staff</th>
                  <th>Último Login</th>
                  <th>Estado</th>
                  <th className="text-right">Acções</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-linha text-xs">
                {users.map((item) => {
                  const roleName = item.roles[0] || 'Sem perfil'

                  return (
                    <tr key={item.id} className="hover:bg-papel/40 transition-colors">
                      <td className="font-semibold text-tinta">
                        <div className="flex items-center gap-2">
                          <span className="w-7 h-7 rounded bg-papel border border-linha flex items-center justify-center font-serif text-mata font-bold text-xs">
                            {item.name.charAt(0)}
                          </span>
                          <div>
                            <div>{item.name}</div>
                            <div className="text-[11px] text-tinta-suave font-mono font-normal">
                              {item.email}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="font-mono tabular-nums text-tinta-suave">
                        {item.phone || '—'}
                      </td>
                      <td>
                        <Badge
                          variant={
                            roleName === 'Director' || roleName === 'Subdirector Pedagógico'
                              ? 'folha'
                              : roleName === 'Professor'
                              ? 'giz'
                              : roleName === 'Gestor Municipal/Provincial'
                              ? 'barro'
                              : 'neutro'
                          }
                        >
                          {roleName}
                        </Badge>
                      </td>
                      <td className="font-mono tabular-nums text-tinta-suave">
                        {item.staff?.staff_code || '—'}
                      </td>
                      <td className="text-tinta-suave tabular-nums text-[11px]">
                        {item.last_login_at || 'Nunca acedeu'}
                      </td>
                      <td>
                        <Badge variant={item.is_active ? 'folha' : 'tijolo'}>
                          {item.is_active ? 'Activo' : 'Desactivado'}
                        </Badge>
                      </td>
                      <td className="text-right">
                        <div className="inline-flex items-center gap-1.5">
                          <button
                            onClick={() => setViewingUser(item)}
                            title="Ver detalhes"
                            className="p-1 rounded text-tinta-suave hover:text-mata hover:bg-papel transition-colors"
                          >
                            <Eye className="w-4 h-4" />
                          </button>

                          {canManageUsers && !isProvincialInspector && item.id !== currentUser?.id && (
                            <button
                              onClick={() => handleToggleStatus(item)}
                              title={item.is_active ? 'Desactivar conta' : 'Activar conta'}
                              className={`p-1 rounded transition-colors ${
                                item.is_active
                                  ? 'text-tinta-suave hover:text-tijolo hover:bg-red-50'
                                  : 'text-tinta-suave hover:text-folha hover:bg-green-50'
                              }`}
                            >
                              {item.is_active ? (
                                <XCircle className="w-4 h-4" />
                              ) : (
                                <CheckCircle2 className="w-4 h-4" />
                              )}
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Modal de Criação de Utilizador */}
      {isCreateModalOpen && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
          <div className="bg-superficie border border-linha rounded w-full max-w-lg p-6 shadow-modal">
            <div className="flex items-center justify-between pb-3 border-b border-linha mb-4">
              <h2 className="text-base font-bold text-mata-escura font-serif">
                Cadastrar Novo Utilizador
              </h2>
              <button
                onClick={() => setIsCreateModalOpen(false)}
                className="text-tinta-suave hover:text-tinta text-lg font-bold"
              >
                ✕
              </button>
            </div>

            {formError && (
              <div className="mb-4 p-3 bg-red-50 border-l-4 border-tijolo rounded-r text-xs text-tijolo">
                {formError}
              </div>
            )}

            <form onSubmit={handleCreateUser} className="space-y-3.5">
              <Input
                label="Nome Completo *"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                placeholder="ex: João Manuel Baptista"
                required
              />

              <div className="grid grid-cols-2 gap-3">
                <Input
                  label="Endereço de E-mail *"
                  type="email"
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  placeholder="utilizador@escola.ao"
                  required
                />
                <Input
                  label="Telefone (+244)"
                  type="tel"
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                  placeholder="+244923000000"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div className="flex flex-col gap-1">
                  <label className="text-xs font-semibold text-tinta uppercase tracking-wide">
                    Perfil Funcional *
                  </label>
                  <select
                    value={formData.role}
                    onChange={(e) =>
                      setFormData({ ...formData, role: e.target.value as Role })
                    }
                    className="w-full px-3 py-2 text-sm bg-superficie text-tinta border border-linha rounded focus:border-mata focus:outline-none"
                    required
                  >
                    {rolesList.map((r) => (
                      <option key={r} value={r}>
                        {r}
                      </option>
                    ))}
                  </select>
                </div>

                <Input
                  label="Palavra-passe *"
                  type="password"
                  value={formData.password}
                  onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                  required
                />
              </div>

              {/* Se for Professor, Director ou Secretaria, dados de Staff */}
              <div className="pt-3 border-t border-linha">
                <div className="text-xs font-semibold text-mata-escura mb-2">
                  Dados de Pessoal / Staff (Docente ou Administrativo)
                </div>
                <div className="grid grid-cols-3 gap-2">
                  <Input
                    label="Código Staff"
                    value={formData.staff_code}
                    onChange={(e) =>
                      setFormData({ ...formData, staff_code: e.target.value })
                    }
                    placeholder="ex: DOC-102"
                  />
                  <Input
                    label="Cargo"
                    value={formData.position}
                    onChange={(e) =>
                      setFormData({ ...formData, position: e.target.value })
                    }
                    placeholder="ex: Professor"
                  />
                  <Input
                    label="Especialidade"
                    value={formData.specialty}
                    onChange={(e) =>
                      setFormData({ ...formData, specialty: e.target.value })
                    }
                    placeholder="ex: Física"
                  />
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-4 border-t border-linha">
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => setIsCreateModalOpen(false)}
                >
                  Cancelar
                </Button>
                <Button type="submit" variant="primary" isLoading={createLoading}>
                  Gravar Utilizador
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal de Detalhes do Utilizador */}
      {viewingUser && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
          <div className="bg-superficie border border-linha rounded w-full max-w-md p-6 shadow-modal">
            <div className="flex items-center justify-between pb-3 border-b border-linha mb-4">
              <h2 className="text-base font-bold text-mata-escura font-serif">
                Ficha do Utilizador
              </h2>
              <button
                onClick={() => setViewingUser(null)}
                className="text-tinta-suave hover:text-tinta text-lg font-bold"
              >
                ✕
              </button>
            </div>

            <div className="space-y-3 text-xs">
              <div>
                <span className="text-tinta-suave block text-[10px] uppercase font-semibold">
                  Nome Completo
                </span>
                <span className="text-sm font-semibold text-tinta">{viewingUser.name}</span>
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div>
                  <span className="text-tinta-suave block text-[10px] uppercase font-semibold">
                    E-mail
                  </span>
                  <span className="font-mono text-tinta">{viewingUser.email}</span>
                </div>
                <div>
                  <span className="text-tinta-suave block text-[10px] uppercase font-semibold">
                    Telefone
                  </span>
                  <span className="font-mono text-tinta">{viewingUser.phone || '—'}</span>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div>
                  <span className="text-tinta-suave block text-[10px] uppercase font-semibold">
                    Perfil no Sistema
                  </span>
                  <Badge variant="folha">{viewingUser.roles[0] || 'Sem perfil'}</Badge>
                </div>
                <div>
                  <span className="text-tinta-suave block text-[10px] uppercase font-semibold">
                    Estado da Conta
                  </span>
                  <Badge variant={viewingUser.is_active ? 'folha' : 'tijolo'}>
                    {viewingUser.is_active ? 'Activa' : 'Desactivada'}
                  </Badge>
                </div>
              </div>

              {viewingUser.staff && (
                <div className="p-3 bg-papel border border-linha rounded space-y-2 mt-2">
                  <div className="font-semibold text-mata-escura text-[11px]">
                    Vínculo Docente / Administrativo
                  </div>
                  <div className="grid grid-cols-2 gap-2">
                    <div>
                      <span className="text-tinta-suave block text-[10px]">Código Staff</span>
                      <span className="font-mono font-medium">{viewingUser.staff.staff_code}</span>
                    </div>
                    <div>
                      <span className="text-tinta-suave block text-[10px]">Cargo</span>
                      <span>{viewingUser.staff.position}</span>
                    </div>
                  </div>
                  {viewingUser.staff.specialty && (
                    <div>
                      <span className="text-tinta-suave block text-[10px]">Especialidade</span>
                      <span>{viewingUser.staff.specialty}</span>
                    </div>
                  )}
                  {viewingUser.staff.academic_degree && (
                    <div>
                      <span className="text-tinta-suave block text-[10px]">Grau Académico</span>
                      <span>{viewingUser.staff.academic_degree}</span>
                    </div>
                  )}
                </div>
              )}

              <div className="text-[10px] text-tinta-suave pt-2 border-t border-linha">
                Registo criado em {viewingUser.created_at || 'Data não disponível'}.
              </div>
            </div>

            <div className="flex justify-end pt-4 border-t border-linha mt-4">
              <Button variant="secondary" size="sm" onClick={() => setViewingUser(null)}>
                Fechar
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
