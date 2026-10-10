<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout/admin.php';

iniciarSessao();
if (adminLogado() !== null) {
    redirecionar('/admin/');
}

$erro = null;
$aviso = isset($_GET['saiu']) ? 'Você saiu do painel.' : null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $chaveIp = 'login:ip:' . ipCliente();
    $chaveEmail = 'login:email:' . hash('sha256', mb_strtolower($email));

    if (!csrfValido($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $erro = 'Sua sessão expirou. Recarregue a página e tente entrar de novo.';
    } elseif (
        limiteExcedido($chaveIp, LOGIN_MAX_FALHAS, LOGIN_JANELA_SEG)
        || limiteExcedido($chaveEmail, LOGIN_MAX_FALHAS, LOGIN_JANELA_SEG)
    ) {
        http_response_code(429);
        $erro = 'Muitas tentativas de acesso. Aguarde 15 minutos e tente novamente.';
    } else {
        $admin = login($email, $senha);
        if ($admin === null) {
            registrarFalha($chaveIp, LOGIN_JANELA_SEG);
            registrarFalha($chaveEmail, LOGIN_JANELA_SEG);
            $erro = 'E-mail ou senha incorretos. Confira os dados e tente novamente.';
        } else {
            limparLimite($chaveEmail);
            iniciarVerificacao2fa($admin);
            // Respeita o intervalo mínimo; a tela renova o código ao vencer.
            $podeEnviar = podeReenviarCodigo2fa(ultimoEnvioCodigo2fa($admin['id']), new DateTimeImmutable());
            if ($podeEnviar && !enviarCodigo2fa($admin)) {
                $_SESSION['aviso_2fa'] = 'Não foi possível enviar o código agora. '
                    . 'Aguarde 1 minuto e clique em "Reenviar código".';
            }
            redirecionar('/admin/verificar.php');
        }
    }
}

adminTopo('Entrar');
?>
<section class="admin-login" aria-labelledby="login-title">
  <div class="admin-login-content">
    <header class="admin-login-heading">
      <span class="admin-login-kicker">Bem-vinda de volta</span>
      <p class="fs-2 marca mb-1">Reiki Ana</p>
      <p class="admin-login-subtitle mb-0">Painel administrativo</p>
    </header>
    <div class="card admin-login-card p-4 p-sm-5">
      <h1 class="h4 mb-4" id="login-title">Acesse sua conta</h1>
      <div aria-live="polite">
        <?php adminAlerta($aviso, 'success'); ?>
        <?php adminAlerta($erro); ?>
      </div>
      <form method="post" action="/admin/login.php" novalidate>
        <?= csrfCampo() ?>
        <div class="mb-3">
          <label for="email" class="form-label">E-mail</label>
          <input type="email" class="form-control" id="email" name="email" required maxlength="190"
                 autocomplete="username" value="<?= e($email) ?>" placeholder="ana@reikiana.com.br…">
        </div>
        <div class="mb-4">
          <label for="senha" class="form-label">Senha</label>
          <div class="input-group" data-password-field>
            <input type="password" class="form-control" id="senha" name="senha" required
                   autocomplete="current-password">
            <button class="btn btn-outline-secondary password-toggle" type="button"
                    data-password-toggle aria-label="Mostrar senha" aria-controls="senha"
                    aria-pressed="false">
              <svg data-eye-open aria-hidden="true"
                   viewBox="0 0 24 24" width="20" height="20"
                   fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
              <svg data-eye-closed aria-hidden="true"
                   viewBox="0 0 24 24" width="20" height="20"
                   fill="none" stroke="currentColor" stroke-width="1.8" hidden>
                <path
                  d="m3 3 18 18 M10.6 10.6a2 2 0 0 0 2.8 2.8
                     M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7
                     a15.5 15.5 0 0 1-3 3.7 M6.2 6.2C3.5 8 2 12 2 12
                     s3.6 7 10 7a10.7 10.7 0 0 0 3.1-.5" />
              </svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">Continuar</button>
      </form>
    </div>
  </div>
</section>
<?php
adminRodape();
