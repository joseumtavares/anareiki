// Gera o site estático para a Vercel em dist-vercel/ (index.html + static/ + favicon).
// A home é só concatenação de strings (src/views/home/*.ts), então não precisa de runtime.
// Importa os .ts direto (type stripping do Node >= 22.18) — sem dependências nativas.
import { cp, mkdir, rm, writeFile } from 'node:fs/promises'
import { pathToFileURL } from 'node:url'
import { resolve, join } from 'node:path'

const out = 'dist-vercel'
const order = ['shell', 'navigation', 'hero', 'signals', 'about', 'services', 'sessions', 'packages', 'availability', 'gallery', 'contact', 'footer', 'whatsapp']

const parts = []
for (const name of order) {
  const mod = await import(pathToFileURL(resolve('src/views/home', `${name}.ts`)).href)
  if (typeof mod[name] !== 'string') throw new Error(`src/views/home/${name}.ts não exporta "${name}" como string`)
  parts.push(mod[name])
}
const html = parts.join('')

await rm(out, { recursive: true, force: true })
await mkdir(out, { recursive: true })
await writeFile(join(out, 'index.html'), html)
await cp('public', out, { recursive: true })
console.log(`Site estático gerado em ${out}/ (${html.length} bytes de HTML)`)
