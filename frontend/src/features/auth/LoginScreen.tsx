import React, { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import { api } from '../../services/api'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { ShieldCheck, UserCheck, AlertCircle } from 'lucide-react'

export const LoginScreen: React.FC = () => {
  const navigate = useNavigate()
  const { login } = useAuth()

  const [loginInput, setLoginInput] = useState('director@sisgestesc.ao')
  const [password, setPassword] = useState('Senha12345!')
  const [loading, setLoading] = useState(false)
  const [errorMessage, setErrorMessage] = useState<string | null>(null)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setErrorMessage(null)

    try {
      const response = await api.post('/auth/login', {
        login: loginInput,
        password: password,
        device_name: 'Terminal Web Secretaria',
      })

      login(response.data.access_token, response.data.user)
      navigate('/dashboard')
    } catch (err: any) {
      if (err.response?.data?.message) {
        setErrorMessage(err.response.data.message)
      } else if (err.response?.data?.errors?.login) {
        setErrorMessage(err.response.data.errors.login[0])
      } else {
        setErrorMessage('Ocorreu um erro ao comunicar com o servidor escolar. Verifique a sua ligação.')
      }
    } finally {
      setLoading(false)
    }
  }

  // Perfis pré-definidos para facilitar a validação e demonstração dos papéis
  const quickProfiles = [
    { label: 'Director Geral', email: 'director@sisgestesc.ao', role: 'Director' },
    { label: 'Subdir. Pedagógico', email: 'pedagogico@sisgestesc.ao', role: 'Pedagógico' },
    { label: 'Secretaria', email: 'secretaria@sisgestesc.ao', role: 'Secretaria' },
    { label: 'Professor Matemática', email: 'professor.matematica@sisgestesc.ao', role: 'Docente' },
    { label: 'Gestor Provincial', email: 'fiscal.provincial@sisgestesc.ao', role: 'Leitura Provincial' },
  ]

  return (
    <div className="min-h-screen bg-papel flex flex-col justify-center items-center p-4">
      {/* Cabeçalho Institucional Angolano */}
      <div className="w-full max-w-md text-center mb-6">
        <div className="inline-flex items-center justify-center w-12 h-12 rounded bg-mata text-white mb-3 border border-mata-escura">
          <ShieldCheck className="w-6 h-6" />
        </div>
        <div className="text-xs font-semibold tracking-widest uppercase text-tinta-suave mb-1">
          República de Angola • Ministério da Educação
        </div>
        <h1 className="text-2xl font-bold text-mata-escura tracking-tight font-serif">
          Sistema de Gestão Escolar
        </h1>
        <p className="text-xs text-tinta-suave mt-1">
          Iniciação, Ensino Primário e 1º Ciclo do Secundário Geral
        </p>
      </div>

      {/* Cartão de Entrada */}
      <div className="w-full max-w-md bg-superficie border border-linha rounded p-6 shadow-sm">
        <div className="border-b border-linha pb-3 mb-5">
          <h2 className="text-base font-semibold text-tinta font-serif">Autenticação no Sistema</h2>
          <p className="text-xs text-tinta-suave mt-0.5">
            Insira o seu endereço de e-mail ou número de telefone registado (+244...)
          </p>
        </div>

        {errorMessage && (
          <div className="mb-4 p-3 bg-red-50 border-l-4 border-tijolo rounded-r text-xs text-tijolo flex items-start gap-2">
            <AlertCircle className="w-4 h-4 shrink-0 mt-0.5" />
            <span>{errorMessage}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <Input
            label="Identificador de Acesso"
            type="text"
            value={loginInput}
            onChange={(e) => setLoginInput(e.target.value)}
            placeholder="ex: director@sisgestesc.ao ou +244923112233"
            required
          />

          <Input
            label="Palavra-passe"
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="••••••••"
            required
          />

          <Button type="submit" variant="primary" className="w-full mt-2" isLoading={loading}>
            Entrar no Sistema
          </Button>
        </form>

        {/* Perfis rápidos para avaliação imediata dos 11 perfis */}
        <div className="mt-6 pt-5 border-t border-linha">
          <div className="flex items-center gap-1.5 text-xs font-semibold text-tinta-suave mb-2.5">
            <UserCheck className="w-3.5 h-3.5 text-mata" />
            <span>Acesso Rápido por Perfil (Ambiente de Testes)</span>
          </div>
          <div className="grid grid-cols-2 gap-1.5">
            {quickProfiles.map((p) => (
              <button
                key={p.email}
                type="button"
                onClick={() => {
                  setLoginInput(p.email)
                  setPassword('Senha12345!')
                }}
                className={`text-left p-2 rounded border text-xs transition-colors ${
                  loginInput === p.email
                    ? 'border-mata bg-papel font-medium text-mata-escura'
                    : 'border-linha hover:bg-papel text-tinta'
                }`}
              >
                <div className="font-semibold">{p.label}</div>
                <div className="text-[10px] text-tinta-suave truncate">{p.email}</div>
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Rodapé Sóbrio */}
      <div className="mt-8 text-center text-xs text-tinta-suave">
        <span>Fuso Horário: Africa/Luanda • Formato Monetário: Kz (AOA)</span>
      </div>
    </div>
  )
}
