import React, { forwardRef } from 'react'

interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string
  error?: string
  hint?: string
}

export const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ label, error, hint, className = '', id, ...props }, ref) => {
    const inputId = id || props.name || Math.random().toString(36).substring(2, 9)

    return (
      <div className="flex flex-col gap-1 w-full">
        {label && (
          <label htmlFor={inputId} className="text-xs font-semibold text-tinta uppercase tracking-wide">
            {label}
          </label>
        )}
        <input
          id={inputId}
          ref={ref}
          className={`w-full px-3 py-2 text-sm bg-superficie text-tinta border rounded transition-colors focus-visible:outline-none focus:border-mata focus:ring-1 focus:ring-mata ${
            error ? 'border-tijolo' : 'border-linha'
          } ${className}`}
          {...props}
        />
        {error && <span className="text-xs text-tijolo font-medium">{error}</span>}
        {!error && hint && <span className="text-xs text-tinta-suave">{hint}</span>}
      </div>
    )
  }
)

Input.displayName = 'Input'
