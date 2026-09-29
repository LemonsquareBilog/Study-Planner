/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        // Brand colors from design system
        brand: {
          background: '#FFC0CB',
          'navbar-cards': '#FFF9FA',
          'navbar-cards-alt': '#FFF5F7',
          borders: '#F3D2DA',
          'text-primary': '#33222A',
          'text-secondary': '#7A5C66',
          'primary-button': '#B33F62',
          'primary-button-hover': '#96334F',
          'secondary-links': '#3F7D8C',
          urgent: '#C6485F',
          warning: '#D9A24C',
          success: '#5B9C82',
          info: '#8C7AB3',
        },
        // Semantic color aliases for components
        primary: {
          DEFAULT: '#B33F62',
          hover: '#96334F',
          light: '#FFF5F7',
          dark: '#7A2D48',
        },
        secondary: {
          DEFAULT: '#3F7D8C',
          hover: '#326672',
          light: '#E8F4F6',
          dark: '#2D5D6B',
        },
        surface: {
          DEFAULT: '#FFF9FA',
          alt: '#FFF5F7',
          border: '#F3D2DA',
        },
        text: {
          primary: '#33222A',
          secondary: '#7A5C66',
          muted: '#A8949C',
          inverse: '#FFFFFF',
        },
        status: {
          urgent: '#C6485F',
          'urgent-hover': '#A83D4F',
          'urgent-light': '#E87A8C',
          warning: '#D9A24C',
          'warning-hover': '#C4923A',
          'warning-light': '#E8C47A',
          success: '#5B9C82',
          'success-hover': '#4A8C72',
          'success-light': '#7BC9A8',
          info: '#8C7AB3',
          'info-hover': '#7A68A0',
          'info-light': '#B8A8D8',
        },
        // Keep pink for backward compatibility
        pink: {
          50: '#FFF5F7',
          100: '#FFF0F5',
          200: '#FCE7F3',
          300: '#F9A8D4',
          400: '#F472B6',
          500: '#EC4899',
          600: '#B33F62',
          700: '#96334F',
          800: '#7A2D48',
          900: '#5C2236',
          950: '#3D1523',
          DEFAULT: '#FFC0CB',
          bg: '#FFC0CB',
        },
        aurora: {
          pink: '#FFC0CB',
          purple: '#D8B4FE',
          indigo: '#C7D2FE',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
        display: ['Cal Sans', 'Inter', 'system-ui', 'sans-serif'],
      },
      animation: {
        'aurora': 'aurora 15s ease-in-out infinite alternate',
        'aurora-fast': 'aurora 8s ease-in-out infinite alternate',
        'blob': 'blob 7s infinite',
        'float': 'float 6s ease-in-out infinite',
        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'slide-down': 'slideDown 0.3s ease-out',
        'slide-up': 'slideUp 0.3s ease-out',
        'fade-in': 'fadeIn 0.3s ease-out',
        'scale-in': 'scaleIn 0.2s ease-out',
      },
      keyframes: {
        aurora: {
          '0%': { backgroundPosition: '0% 50%' },
          '100%': { backgroundPosition: '100% 50%' },
        },
        blob: {
          '0%, 100%': { transform: 'translate(0, 0) scale(1)' },
          '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
          '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
        },
        float: {
          '0%, 100%': { transform: 'translateY(0) translateX(0)', opacity: '0.3' },
          '25%': { transform: 'translateY(-20px) translateX(10px)', opacity: '0.6' },
          '50%': { transform: 'translateY(-40px) translateX(-5px)', opacity: '0.4' },
          '75%': { transform: 'translateY(-20px) translateX(-10px)', opacity: '0.6' },
        },
        slideDown: {
          '0%': { opacity: '0', transform: 'translateY(-10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        slideUp: {
          '0%': { opacity: '0', transform: 'translateY(10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        scaleIn: {
          '0%': { opacity: '0', transform: 'scale(0.95)' },
          '100%': { opacity: '1', transform: 'scale(1)' },
        },
      },
      backgroundSize: {
        'aurora': '200% 200%',
      },
      backgroundImage: {
        'gradient-brand': 'linear-gradient(135deg, #FFC0CB 0%, #FFF5F7 25%, #FFF9FA 50%, #FFF5F7 75%, #FFC0CB 100%)',
        'gradient-brand-radial': 'radial-gradient(ellipse at center, #FFC0CB 0%, #FFF5F7 50%, #FFF9FA 100%)',
        'gradient-primary': 'linear-gradient(135deg, #B33F62 0%, #96334F 100%)',
        'gradient-secondary': 'linear-gradient(135deg, #3F7D8C 0%, #326672 100%)',
        'gradient-aurora': 'linear-gradient(135deg, #FFC0CB 0%, #FFF0F5 25%, #FFF9FA 50%, #E8F4F6 75%, #DBEAFE 100%)',
        'gradient-aurora-dark': 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)',
        'gradient-mesh': 'linear-gradient(135deg, #FFC0CB 0%, #F3D2DA 25%, #FFF5F7 50%, #FFF9FA 75%, #B33F62 100%)',
      },
      boxShadow: {
        'glass': '0 8px 32px 0 rgba(51, 34, 42, 0.08)',
        'glass-lg': '0 16px 48px 0 rgba(51, 34, 42, 0.12)',
        'glow-primary': '0 0 20px rgba(179, 63, 98, 0.4)',
        'glow-secondary': '0 0 20px rgba(63, 125, 140, 0.4)',
        'glow-urgent': '0 0 20px rgba(198, 72, 95, 0.4)',
        'glow-success': '0 0 20px rgba(91, 156, 130, 0.4)',
      },
      backdropBlur: {
        'xs': '2px',
        '4xl': '72px',
      },
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
        '128': '32rem',
      },
      borderRadius: {
        '4xl': '2rem',
      },
      transitionDuration: {
        '400': '400ms',
      },
      transitionTimingFunction: {
        'bounce-in': 'cubic-bezier(0.68, -0.55, 0.265, 1.55)',
      },
    },
  },
  plugins: [],
}