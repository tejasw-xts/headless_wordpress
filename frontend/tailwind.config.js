/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  corePlugins: {
    preflight: false,
  },
  theme: {
    extend: {
      colors: {
        bg: 'var(--bg)',
        surface: 'var(--surface)',
        'surface-strong': 'var(--surface-strong)',
        text: 'var(--text)',
        muted: 'var(--muted)',
        line: 'var(--line)',
        brand: 'var(--brand)',
        'brand-dark': 'var(--brand-dark)',
        accent: 'var(--accent)',
      },
      boxShadow: {
        app: 'var(--shadow)',
      },
      fontFamily: {
        sans: ['"Segoe UI"', 'sans-serif'],
      },
    },
  },
  plugins: [],
};

