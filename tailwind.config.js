/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './**/*.php',
    './**/*.html',
    '!./vendor/**',
    '!./node_modules/**',
    '!./storage/**',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: '#1e40af',
        secondary: '#3b82f6',
        accent: '#f59e0b',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
      },
    },
  },
  plugins: [require('daisyui')],
  daisyui: {
    themes: [
      {
        inventaris: {
          'primary': '#1e40af',
          'primary-content': '#ffffff',
          'secondary': '#3b82f6',
          'secondary-content': '#ffffff',
          'accent': '#f59e0b',
          'accent-content': '#000000',
          'neutral': '#1f2937',
          'neutral-content': '#ffffff',
          'base-100': '#ffffff',
          'base-200': '#f9fafb',
          'base-300': '#e5e7eb',
          'base-content': '#1f2937',
          'info': '#3b82f6',
          'success': '#22c55e',
          'warning': '#f59e0b',
          'error': '#ef4444',
        },
      },
    ],
  },
};
