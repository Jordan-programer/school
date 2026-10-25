import React from 'react'
import { Link, useLocation } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import { Badge } from '../ui/Badge'
import {
  Users,
  LogOut,
  Building2,
  GraduationCap,
  ClipboardList,
  CalendarCheck,
  Award,
} from 'lucide-react'

export const AppLayout: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { user, logout } = useAuth()
  const location = useLocation()

  const navItems = [
    { label: 'Utilizadores e Perfis', path: '/dashboard', icon: Users, badge: 'Fase 1' },
    { label: 'Estrutura Escolar & Turmas', path: '/estrutura', icon: Building2, badge: 'Módulo 2' },
    { label: 'Alunos & Encarregados', path: '/alunos', icon: GraduationCap, badge: 'Módulo 3' },
    { label: 'Matrículas & Confirmações', path: '/matriculas', icon: ClipboardList, badge: 'Módulo 4' },
    { label: 'Chamada Diária', path: '/chamada', icon: CalendarCheck, badge: 'Módulo 5' },
    { label: 'Notas, Mini-Pautas & Pautas', path: '/pautas', icon: Award, badge: 'Módulo 6' },
  ]

  const userRole = user?.roles[0] || 'Utilizador'

  return (
    <div className="min-h-screen bg-papel flex">
      {/* Barra Lateral em --mata-escura */}
      <aside className="w-64 bg-mata-escura text-[#EDE7DA] flex flex-col shrink-0 border-r border-[#162620]">
        {/* Cabeçalho da Barra Lateral */}
        <div className="p-4 border-b border-[#2A5245] flex items-center gap-3">
          <div className="w-9 h-9 rounded bg-mata flex items-center justify-center font-serif font-bold text-white text-base border border-linha/20">
            SG
          </div>
          <div>
            <div className="font-serif font-bold text-sm tracking-tight text-white leading-none">
              SisGestEsc
            </div>
            <div className="text-[10px] text-[#A39B8B] uppercase tracking-wider mt-1">
              República de Angola
            </div>
          </div>
        </div>

        {/* Navegação Principal */}
        <nav className="flex-1 py-4 px-2 space-y-1">
          <div className="px-3 pb-2 text-[10px] font-semibold text-[#8B9890] uppercase tracking-wider">
            Navegação do Sistema
          </div>
          {navItems.map((item) => {
            const Icon = item.icon
            const isActive = location.pathname === item.path

            return (
              <Link
                key={item.path}
                to={item.path}
                className={`relative flex items-center justify-between px-3 py-2 rounded text-xs transition-colors ${
                  isActive
                    ? 'bg-[#274F43] text-white font-medium'
                    : 'text-[#D0C8B8] hover:bg-[#274F43]/50 hover:text-white'
                }`}
              >
                {/* Indicador activo de 3px em --girassol */}
                {isActive && (
                  <span className="absolute left-0 top-1 bottom-1 w-[3px] bg-girassol rounded-r" />
                )}
                <div className="flex items-center gap-2.5">
                  <Icon className={`w-4 h-4 ${isActive ? 'text-girassol' : 'text-[#A39B8B]'}`} />
                  <span>{item.label}</span>
                </div>
                {item.badge && (
                  <span className="text-[9px] px-1.5 py-0.5 rounded bg-[#1A382F] text-[#EDE7DA] font-mono">
                    {item.badge}
                  </span>
                )}
              </Link>
            )
          })}
        </nav>

        {/* Rodapé da Barra Lateral com Perfil */}
        <div className="p-3 border-t border-[#2A5245] bg-[#1A382F]/60">
          <div className="flex items-center justify-between">
            <div className="truncate pr-2">
              <div className="text-xs font-semibold text-white truncate">{user?.name}</div>
              <div className="text-[10px] text-girassol truncate">{userRole}</div>
            </div>
            <button
              onClick={() => logout()}
              title="Encerrar Sessão"
              className="p-1.5 text-[#A39B8B] hover:text-tijolo hover:bg-black/20 rounded transition-colors"
            >
              <LogOut className="w-4 h-4" />
            </button>
          </div>
        </div>
      </aside>

      {/* Conteúdo Principal */}
      <div className="flex-1 flex flex-col min-w-0">
        {/* Barra Superior */}
        <header className="h-14 bg-superficie border-b border-linha px-6 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <span className="text-xs text-tinta-suave uppercase tracking-wider font-semibold">
              Subsistema de Ensino:
            </span>
            <span className="text-xs font-medium text-mata-escura">
              Iniciação • Primário (1ª–6ª) • 1º Ciclo Secundário (7ª–9ª)
            </span>
          </div>

          <div className="flex items-center gap-4 text-xs">
            <div className="text-tinta-suave">
              <span>Luanda: </span>
              <span className="font-mono tabular-nums text-tinta font-medium">
                {new Date().toLocaleDateString('pt-AO')}
              </span>
            </div>
            <div className="h-4 w-px bg-linha" />
            <Badge variant="folha">Servidor Conectado (PostgreSQL)</Badge>
          </div>
        </header>

        {/* Área de Ecrã */}
        <main className="flex-1 p-6 overflow-y-auto">{children}</main>
      </div>
    </div>
  )
}
