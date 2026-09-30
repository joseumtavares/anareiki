document.querySelectorAll('[data-password-toggle]').forEach((button) => {
  const input = document.getElementById(button.getAttribute('aria-controls'))
  const openEye = button.querySelector('[data-eye-open]')
  const closedEye = button.querySelector('[data-eye-closed]')

  if (!input || !openEye || !closedEye) return

  button.addEventListener('click', () => {
    const showing = input.type === 'password'
    input.type = showing ? 'text' : 'password'
    button.setAttribute('aria-pressed', String(showing))
    button.setAttribute('aria-label', showing ? 'Ocultar senha' : 'Mostrar senha')
    openEye.hidden = showing
    closedEye.hidden = !showing
  })
})

const countdown = document.querySelector('[data-resend-countdown]')
if (countdown) {
  const resendButton = document.querySelector('[data-resend-button]')
  const autoResendForm = document.querySelector('[data-auto-resend-form]')
  const status = document.querySelector('[data-resend-status]')
  const autoResend = countdown.dataset.autoResend === 'true'
  const deadline = Date.now() + Number(countdown.dataset.seconds || 0) * 1000
  let submitted = false
  let readyAnnounced = false

  const updateCountdown = () => {
    const remaining = Math.max(0, Math.ceil((deadline - Date.now()) / 1000))
    if (resendButton) resendButton.disabled = remaining > 0

    if (remaining === 0) {
      countdown.textContent = ''
      if (autoResend && autoResendForm && !submitted) {
        submitted = true
        readyAnnounced = true
        countdown.textContent = 'Enviando um novo código…'
        if (status) status.textContent = 'O código venceu. Enviando um novo código.'
        autoResendForm.requestSubmit()
      } else if (status && !readyAnnounced) {
        readyAnnounced = true
        status.textContent = 'Agora você pode solicitar um novo código.'
      }
      return
    }

    countdown.textContent = `Você poderá reenviar em ${remaining} s.`
  }

  updateCountdown()
  window.setInterval(updateCountdown, 250)
}
