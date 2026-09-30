import { readdir, readFile } from 'node:fs/promises'
import { relative, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const root = resolve(fileURLToPath(new URL('..', import.meta.url)))
const sourceDirectories = ['src', 'public', 'public_html', 'bin', 'tests', 'scripts', 'sql']
const ignoredDirectories = new Set(['vendor', 'node_modules'])
const sourceExtensions = new Set(['.js', '.mjs', '.cjs', '.ts', '.tsx', '.php', '.css', '.html', '.sql'])
const maximumLines = 350
const violations = []

async function visit(directory) {
  const entries = await readdir(directory, { withFileTypes: true })

  for (const entry of entries) {
    if (entry.isDirectory() && ignoredDirectories.has(entry.name)) continue
    const path = resolve(directory, entry.name)
    if (entry.isDirectory()) {
      await visit(path)
    } else if (sourceExtensions.has(extension(entry.name))) {
      const source = await readFile(path, 'utf8')
      const lines = source.replace(/\r\n/g, '\n').replace(/\n$/, '').split('\n').length
      if (lines > maximumLines) violations.push(`${relative(root, path)}: ${lines} lines`)
    }
  }
}

for (const directory of sourceDirectories) await visit(resolve(root, directory))
for (const file of [
  'eslint.config.js',
  'vite.config.ts',
  'ecosystem.config.cjs',
  'package.json',
  'composer.json',
  'tsconfig.json',
  'wrangler.jsonc',
  'phpstan.neon',
  'phpcs.xml',
  'phpunit.xml',
  'public_html/.htaccess',
]) {
  const path = resolve(root, file)
  const source = await readFile(path, 'utf8')
  const lines = source.replace(/\r\n/g, '\n').replace(/\n$/, '').split('\n').length
  if (lines > maximumLines) violations.push(`${relative(root, path)}: ${lines} lines`)
}

function extension(fileName) {
  const dot = fileName.lastIndexOf('.')
  return dot === -1 ? '' : fileName.slice(dot)
}

if (violations.length) {
  console.error(`Source files must not exceed ${maximumLines} lines:`)
  for (const violation of violations) console.error(`- ${violation}`)
  process.exitCode = 1
} else {
  console.log(`All source files are at or below ${maximumLines} lines.`)
}
