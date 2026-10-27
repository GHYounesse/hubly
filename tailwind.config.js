const colors = require('tailwindcss/colors')

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./templates/**/*.html.twig'],
  // `dark:` utilities follow the .dark-theme class set on <html> (see assets/styles/theme.css)
  darkMode: ['class', '.dark-theme'],
  theme: {
    extend: {
      colors: {
        // Existing templates use blue/gray: point them at the Hubly palette (indigo brand)
        blue: colors.indigo,
        gray: colors.slate,

        // Semantic colors, driven by the CSS variables of the theme
        primary: {
          DEFAULT: 'var(--color-primary)',
          hover: 'var(--color-primary-hover)',
          subtle: 'var(--color-primary-subtle)',
        },
        secondary: {
          DEFAULT: 'var(--color-secondary)',
          subtle: 'var(--color-secondary-subtle)',
        },
        background: 'var(--color-background)',
        surface: {
          DEFAULT: 'var(--color-surface)',
          elevated: 'var(--color-surface-elevated)',
        },
        content: {
          DEFAULT: 'var(--color-text)',
          secondary: 'var(--color-text-secondary)',
        },
        line: 'var(--color-border)',
        success: 'var(--color-success)',
        warning: 'var(--color-warning)',
        error: 'var(--color-error)',
      },
      keyframes: {
        'bounce-x': {
          '0%, 100%': {
            transform: 'translateX(0)',
            'animation-timing-function': 'cubic-bezier(0.8, 0, 1, 1)',
          },
          '50%': {
            transform: 'translateX(25%)', // Adjust this value for desired bounce distance
            'animation-timing-function': 'cubic-bezier(0, 0, 0.2, 1)',
          },
        },
      },
      animation: {
        'bounce-x': 'bounce-x 1s infinite', // Define the animation using the keyframes
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}
