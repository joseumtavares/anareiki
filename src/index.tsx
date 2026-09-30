import { shell } from './views/home/shell.js'
import { navigation } from './views/home/navigation.js'
import { hero } from './views/home/hero.js'
import { signals } from './views/home/signals.js'
import { about } from './views/home/about.js'
import { services } from './views/home/services.js'
import { sessions } from './views/home/sessions.js'
import { packages } from './views/home/packages.js'
import { availability } from './views/home/availability.js'
import { gallery } from './views/home/gallery.js'
import { contact } from './views/home/contact.js'
import { footer } from './views/home/footer.js'
import { whatsapp } from './views/home/whatsapp.js'
import { Hono } from 'hono'
import { serveStatic } from 'hono/cloudflare-workers'

const app = new Hono()

app.use('/static/*', serveStatic({ root: './' }))

app.get('/', (c) => c.html([shell, navigation, hero, signals, about, services, sessions, packages, availability, gallery, contact, footer, whatsapp].join('')))

export default app
