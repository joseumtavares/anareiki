import { jsxRenderer } from 'hono/jsx-renderer'

export const renderer = jsxRenderer(({ children }) => {
  return (
    <html>
      <head>
        <link href="/static/style-01-foundation.css" rel="stylesheet" />
        <link href="/static/style-02-hero-about.css" rel="stylesheet" />
        <link href="/static/style-03-services-sessions.css" rel="stylesheet" />
        <link href="/static/style-04-packages-hours.css" rel="stylesheet" />
        <link href="/static/style-05-gallery-contact.css" rel="stylesheet" />
        <link href="/static/style-06-footer-responsive.css" rel="stylesheet" />
      </head>
      <body>{children}</body>
    </html>
  )
})
