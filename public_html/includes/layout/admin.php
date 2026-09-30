<?php

declare(strict_types=1);

function e(?string $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/** Abre o HTML do painel: Bootstrap 5 com a identidade roxa (DESIGN-SYSTEM §14). */
function adminTopo(string $titulo): void
{
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($titulo) ?> — Painel Reiki Ana</title>
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">
  <style>
    :root { --purple: #7B4F9E; --purple-dark: #5A3270; --cream: #FBF7F2; }
    body { background: var(--cream); color: #4A3555; }
    .marca { font-family: Georgia, 'Times New Roman', serif; font-style: italic; color: var(--purple); }
    .admin-login {
      position: relative; display: grid; place-items: center; min-height: min(720px, calc(100svh - 3rem));
      overflow: hidden; padding: clamp(2.5rem, 7vw, 5rem) 1.25rem; border-radius: 28px;
      background: radial-gradient(ellipse at 82% 18%, rgba(201,168,224,.27), transparent 34%),
        radial-gradient(ellipse at 12% 88%, rgba(232,164,192,.18), transparent 32%),
        linear-gradient(135deg, #2D1B3D 0%, #5A3270 58%, #70478C 100%);
      isolation: isolate;
    }
    .admin-login::before, .admin-login::after {
      position: absolute; z-index: -1; width: 18rem; aspect-ratio: 1; border: 1px solid rgba(255,255,255,.12);
      border-radius: 50%; content: ""; pointer-events: none;
    }
    .admin-login::before {
      top: -9rem; right: -5rem;
      box-shadow: 0 0 0 2.5rem rgba(255,255,255,.025), 0 0 0 5rem rgba(255,255,255,.02);
    }
    .admin-login::after {
      bottom: -13rem; left: -7rem; width: 25rem;
      box-shadow: 0 0 0 2rem rgba(255,255,255,.025);
    }
    .admin-login-content { width: min(100%, 28rem); animation: login-arrive .55s cubic-bezier(.2,.7,.2,1) both; }
    .admin-login-heading { margin-bottom: 1.75rem; color: #fff; text-align: center; }
    .admin-login-kicker {
      display: block; margin-bottom: .65rem; color: #E8A4C0; font-size: .76rem;
      font-weight: 700; letter-spacing: .16em; text-transform: uppercase;
    }
    .admin-login-heading .marca { color: #fff; font-size: 2.5rem; }
    .admin-login-subtitle { color: rgba(255,255,255,.76); }
    .admin-login-card {
      border: 1px solid rgba(255,255,255,.58); background: rgba(255,255,255,.97);
      box-shadow: 0 22px 60px rgba(25,10,37,.28);
    }
    .admin-login-card h1 { color: #2D1B3D; }
    .admin-login-card .form-control, .admin-login-card .password-toggle { min-height: 3rem; }
    .admin-login-card .form-control { border-color: #DED3E5; }
    .admin-login-card .btn-primary {
      min-height: 3rem; box-shadow: 0 7px 18px rgba(90,50,112,.2);
      transition: transform .2s ease, box-shadow .2s ease, background-color .2s ease;
    }
    .admin-login-card .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(90,50,112,.28); }
    @keyframes login-arrive {
      from { opacity: 0; transform: translateY(12px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .btn-primary {
      --bs-btn-bg: var(--purple); --bs-btn-border-color: var(--purple);
      --bs-btn-hover-bg: var(--purple-dark); --bs-btn-hover-border-color: var(--purple-dark);
      --bs-btn-active-bg: var(--purple-dark); --bs-btn-active-border-color: var(--purple-dark);
      --bs-btn-disabled-bg: var(--purple); --bs-btn-disabled-border-color: var(--purple);
      --bs-btn-focus-shadow-rgb: 123, 79, 158;
    }
    .btn-link { --bs-btn-color: var(--purple); --bs-btn-hover-color: var(--purple-dark); }
    .form-control:focus { border-color: #C9A8E0; box-shadow: 0 0 0 .25rem rgba(123, 79, 158, .25); }
    .card { border: 0; box-shadow: 0 8px 32px rgba(123, 79, 158, .12); border-radius: 16px; }
    .password-toggle svg { transition: transform .18s ease, opacity .18s ease; }
    .password-toggle:hover svg { transform: scale(1.06); }
    [data-resend-countdown]:empty { display: none; }
    @media (prefers-reduced-motion: reduce) {
      .password-toggle svg, .admin-login-card .btn-primary { transition: none; }
      .admin-login-content { animation: none; }
      .admin-login-card .btn-primary:hover { transform: none; }
    }
    @media (max-width: 575.98px) {
      main.container { padding-top: .75rem !important; padding-bottom: .75rem !important; }
      .admin-login { min-height: calc(100svh - 1.5rem); border-radius: 20px; }
      .admin-login-card { padding: 1.5rem !important; }
    }
  </style>
</head>
<body>
<main class="container py-5">
    <?php
}

function adminRodape(): void
{
    ?>
</main>
<script src="/static/admin-auth.js" defer></script>
</body>
</html>
    <?php
}

/** Mensagem de erro/aviso anunciada a leitores de tela (RULES §8.1). */
function adminAlerta(?string $mensagem, string $tipo = 'danger'): void
{
    if ($mensagem === null) {
        return;
    }
    ?>
<div class="alert alert-<?= e($tipo) ?>" role="<?= $tipo === 'danger' ? 'alert' : 'status' ?>"><?= e($mensagem) ?></div>
    <?php
}
