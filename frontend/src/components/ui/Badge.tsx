import React from 'react'

interface BadgeProps {
  children: React.ReactNode
  variant?: 'folha' | 'tijolo' | 'girassol' | 'giz' | 'barro' | 'neutro'
  className?: string
}

export const Badge: React.FC<BadgeProps> = ({ children, variant = 'neutro', className = '' }) => {
  // Ponto de 6px de status conforme exigido pelas directrizes visuais
  const dotColors = {
    folha: 'bg-folha',
    tijolo: 'bg-tijolo',
    girassol: 'bg-girassol',
    giz: 'bg-giz',
    barro: 'bg-barro',
    neutro: 'bg-tinta-suave',
  }

  return (
    <span
      className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium bg-superficie border border-linha text-tinta ${className}`}
    >
      <span className={`w-1.5 h-1.5 rounded-full ${dotColors[variant]}`} />
      <span>{children}</span>
    </span>
  )
}
