import React from 'react'
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider, useAuth } from './context/AuthContext'
import { LoginScreen } from './features/auth/LoginScreen'
import { AppLayout } from './components/layout/AppLayout'
import { UsersScreen } from './features/users/UsersScreen'
import { AcademicScreen } from './features/academic/AcademicScreen'
import { StudentsScreen } from './features/students/StudentsScreen'
import { EnrollmentsScreen } from './features/enrollments/EnrollmentsScreen'
import { AttendanceScreen } from './features/attendance/AttendanceScreen'

const ProtectedRoute: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { token, loading } = useAuth()

  if (loading) {
    return (
      <div className="min-h-screen bg-papel flex items-center justify-center">
        <div className="flex flex-col items-center gap-2 text-xs text-tinta-suave">
          <div className="w-6 h-6 border-2 border-mata border-t-transparent rounded-full animate-spin" />
          <span>A carregar sessão escolar...</span>
        </div>
      </div>
    )
  }

  if (!token) {
    return <Navigate to="/login" replace />
  }

  return <>{children}</>
}

const AppRoutes: React.FC = () => {
  const { token } = useAuth()

  return (
    <Routes>
      <Route
        path="/login"
        element={token ? <Navigate to="/dashboard" replace /> : <LoginScreen />}
      />

      <Route
        path="/dashboard"
        element={
          <ProtectedRoute>
            <AppLayout>
              <UsersScreen />
            </AppLayout>
          </ProtectedRoute>
        }
      />

      <Route
        path="/estrutura"
        element={
          <ProtectedRoute>
            <AppLayout>
              <AcademicScreen />
            </AppLayout>
          </ProtectedRoute>
        }
      />

      <Route
        path="/alunos"
        element={
          <ProtectedRoute>
            <AppLayout>
              <StudentsScreen />
            </AppLayout>
          </ProtectedRoute>
        }
      />

      <Route
        path="/matriculas"
        element={
          <ProtectedRoute>
            <AppLayout>
              <EnrollmentsScreen />
            </AppLayout>
          </ProtectedRoute>
        }
      />

      <Route
        path="/chamada"
        element={
          <ProtectedRoute>
            <AppLayout>
              <AttendanceScreen />
            </AppLayout>
          </ProtectedRoute>
        }
      />

      {/* Placeholders informativos para os próximos módulos da Fase 1 */}
      <Route
        path="/:section"
        element={
          <ProtectedRoute>
            <AppLayout>
              <UsersScreen />
            </AppLayout>
          </ProtectedRoute>
        }
      />

      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  )
}

export function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <AppRoutes />
      </AuthProvider>
    </BrowserRouter>
  )
}

export default App
