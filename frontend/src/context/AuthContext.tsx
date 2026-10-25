import React, { createContext, useContext, useState, useEffect } from 'react'
import { api } from '../services/api'
import type { User, Role } from '../types/auth'

interface AuthContextType {
  user: User | null
  token: string | null
  loading: boolean
  login: (token: string, user: User) => void
  logout: () => Promise<void>
  hasRole: (role: Role | Role[]) => boolean
  hasPermission: (permission: string) => boolean
}

const AuthContext = createContext<AuthContextType | undefined>(undefined)

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null)
  const [token, setToken] = useState<string | null>(localStorage.getItem('sisgestesc_token'))
  const [loading, setLoading] = useState<boolean>(true)

  useEffect(() => {
    const fetchUser = async () => {
      const storedToken = localStorage.getItem('sisgestesc_token')
      if (!storedToken) {
        setLoading(false)
        return
      }

      try {
        const response = await api.get('/auth/me')
        setUser(response.data.user)
      } catch {
        localStorage.removeItem('sisgestesc_token')
        localStorage.removeItem('sisgestesc_user')
        setUser(null)
        setToken(null)
      } finally {
        setLoading(false)
      }
    }

    fetchUser()
  }, [])

  const login = (newToken: string, newUser: User) => {
    localStorage.setItem('sisgestesc_token', newToken)
    localStorage.setItem('sisgestesc_user', JSON.stringify(newUser))
    setToken(newToken)
    setUser(newUser)
  }

  const logout = async () => {
    try {
      if (token) {
        await api.post('/auth/logout')
      }
    } catch {
      // Falha silenciosa no logout de rede
    } finally {
      localStorage.removeItem('sisgestesc_token')
      localStorage.removeItem('sisgestesc_user')
      setToken(null)
      setUser(null)
      window.location.href = '/login'
    }
  }

  const hasRole = (roles: Role | Role[]): boolean => {
    if (!user) return false
    const checkRoles = Array.isArray(roles) ? roles : [roles]
    return user.roles.some((r) => checkRoles.includes(r))
  }

  const hasPermission = (permission: string): boolean => {
    if (!user) return false
    return user.permissions.includes(permission)
  }

  return (
    <AuthContext.Provider value={{ user, token, loading, login, logout, hasRole, hasPermission }}>
      {children}
    </AuthContext.Provider>
  )
}

export const useAuth = (): AuthContextType => {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error('useAuth deve ser utilizado dentro de um AuthProvider')
  }
  return context
}
