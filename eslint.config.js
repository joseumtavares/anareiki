export default [
  {
    ignores: ['.agents/**', 'node_modules/**', 'public_html/vendor/**', 'dist/**'],
  },
  {
    files: ['**/*.{js,mjs,cjs,ts,tsx}'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      parserOptions: { ecmaFeatures: { jsx: true } },
      globals: {
        IntersectionObserver: 'readonly',
        URL: 'readonly',
        clearInterval: 'readonly',
        module: 'readonly',
        process: 'readonly',
        console: 'readonly',
        document: 'readonly',
        requestAnimationFrame: 'readonly',
        setTimeout: 'readonly',
        setInterval: 'readonly',
        window: 'readonly',
      },
    },
    rules: {
      'max-lines': ['error', { max: 350 }],
      'no-constant-condition': 'error',
      'no-undef': 'error',
      'no-unused-vars': ['error', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
    },
  },
]
