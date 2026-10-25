import React from 'react'

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost'
  size?: 'sm' | 'md' | 'lg'
  isLoading?: boolean
}

export const Button: React.FC<ButtonProps> = ({
  children,
  variant = 'primary',
  size = 'md',
  isLoading = false,
  className = '',
  disabled,
  ...props
}) => {
  const baseStyles =
    'inline-flex items-center justify-center font-medium rounded transition-colors focus-visible:outline-2 focus-visible:outline-mata disabled:opacity-50 disabled:cursor-not-allowed select-none'

  const sizeStyles = {
    sm: 'text-xs px-2.5 py-1.5 gap-1.5',
    md: 'text-sm px-3.5 py-2 gap-2',
    lg: 'text-base px-5 py-2.5 gap-2.5',
  }

  const variantStyles = {
    primary: 'bg-mata text-white hover:bg-mata-escura border border-mata active:bg-mata-escura',
    secondary: 'bg-superficie text-tinta hover:bg-papel border border-linha active:border-mata',
    danger: 'bg-superficie text-tijolo hover:bg-red-50 border border-linha active:border-tijolo',
    ghost: 'bg-transparent text-tinta-suave hover:text-tinta hover:bg-superficie border border-transparent',
  }

  return (
    <button
      className={`${baseStyles} ${sizeStyles[size]} ${variantStyles[variant]} ${className}`}
      disabled={disabled || isLoading}
      {...props}
    >
      {isLoading ? (
        <>
          <span className="w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full animate-spin" />
          <span>A processar...</span>
        </>
      ) : (
        children
      )}
    </button>
  )
}
