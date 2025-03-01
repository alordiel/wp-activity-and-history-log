/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        'wp-admin': {
          'primary': '#2271b1',
          'secondary': '#f0f0f1',
          'success': '#00a32a',
          'danger': '#d63638',
          'warning': '#dba617',
          'info': '#72aee6',
          'gray': '#f6f7f7',
        },
      },
    },
  },
  plugins: [],
}
