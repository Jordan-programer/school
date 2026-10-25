import type { Config } from 'tailwindcss'

export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        papel: 'var(--papel)',
        superficie: 'var(--superficie)',
        linha: 'var(--linha)',
        tinta: {
          DEFAULT: 'var(--tinta)',
          suave: 'var(--tinta-suave)',
        },
        mata: {
          DEFAULT: 'var(--mata)',
          escura: 'var(--mata-escura)',
        },
        barro: 'var(--barro)',
        girassol: 'var(--girassol)',
        giz: 'var(--giz)',
        folha: 'var(--folha)',
        tijolo: 'var(--tijolo)',
      },
      fontFamily: {
        serif: ['"Source Serif 4"', 'Georgia', 'serif'],
        sans: ['"Public Sans"', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
      },
      borderRadius: {
        DEFAULT: '4px',
        md: '6px',
        lg: '8px',
      },
      boxShadow: {
        modal: '0 4px 12px rgba(31, 42, 36, 0.12)',
        card: 'none',
      },
    },
  },
  plugins: [],
} satisfies Config
