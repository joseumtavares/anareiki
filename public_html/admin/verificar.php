<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout/admin.php';

iniciarSessao();
$pendente = $_SESSION['2fa_pendente'] ?? null;
if ($pendente === null) {
    redirecionar('/admin/login.php');
}

$erro = $_SESSION['aviso_2fa'] ?? null;
unset($_SESSION['aviso_2fa']);
$info = $_SESSION['sucesso_2fa'] ?? null;
unset($_SESSION['sucesso_2fa']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_GET['action'] ?? 'verificar';
    $chave = '2fa:' . $pendente['id'];

    if (!csrfValido($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $erro = 'Sua sessão expirou. Recarregue a página e tente de novo.';
    } elseif ($acao === 'reenviar' || $acao === 'auto_reenviar') {
        $automatico = $acao === 'auto_reenviar';
        if ($automatico && !empty($_SESSION['2fa_auto_reenvio_feito'])) {
            redirecionar('/admin/verificar.php');
        }
        if ($automatico) {
            $_SESSION['2fa_auto_reenvio_feito'] = true;
        }
        if (!podeReenviarCodigo2fa(ultimoEnvioCodigo2fa($pendente['id']), new DateTimeImmutable())) {
            $erro = 'Aguarde 1 minuto antes de pedir um novo código.';
        } elseif (!enviarCodigo2fa($pendente)) {
            $erro = 'Não foi possível enviar o código agora. Tente novamente em alguns minutos.';
        } else {
            $_SESSION['sucesso_2fa'] = 'Enviamos um novo código. Ele vale por 1 minuto.';
            redirecionar('/admin/verificar.php');
        }
    } elseif (limiteExcedido($chave, VERIFICACAO_MAX_FALHAS, LOGIN_JANELA_SEG)) {
        http_response_code(429);
        $erro = 'Muitas tentativas de verificação. Aguarde 15 minutos e entre novamente.';
    } else {
        $codigo = preg_replace('/\s+/', '', (string) ($_POST['codigo'] ?? ''));
        if (preg_match('/^\d{6}$/D', $codigo) !== 1) {
            $erro = 'Digite os 6 números do código que enviamos por e-mail.';
        } else {
            $resultado = validarCodigo2fa($pendente['id'], $codigo);
            if ($resultado === 'ok') {
                limparLimite($chave);
                concluirLogin($pendente);
                redirecionar('/admin/');
            }
            if ($resultado === 'incorreto') {
                registrarFalha($chave, LOGIN_JANELA_SEG);
            }
            $erro = match ($resultado) {
                'incorreto' => 'Código incorreto. Confira o e-mail e digite os 6 números novamente.',
                'expirado'  => 'Este código expirou. Clique em "Reenviar código" para receber um novo.',
                'bloqueado' => 'Muitas tentativas com este código. Clique em "Reenviar código" para receber um novo.',
                default     => 'Não há código válido. Clique em "Reenviar código" para receber um novo.',
            };
        }
    }
}

$ultimoEnvio = ultimoEnvioCodigo2fa($pendente['id']);
$segundosRestantes = $ultimoEnvio === null
    ? 0
    : max(0, CODIGO_2FA_REENVIO_SEG - (time() - strtotime($ultimoEnvio)));
$autoReenvioDisponivel = empty($_SESSION['2fa_auto_reenvio_feito']);

adminTopo('Verificação');
?>
<div class="row justify-content-center">
  <div class="col-12 col-sm-10 col-md-6 col-lg-4">
    <p class="text-center fs-2 marca mb-1">Reiki Ana</p>
    <p class="text-center text-secondary mb-4">Painel administrativo</p>
    <div class="card p-4">
      <h1 class="h5 mb-2">Verificação em duas etapas</h1>
      <p class="text-secondary small">
        Enviamos um código de 6 números para <strong><?= e(mascararEmail($pendente['email'])) ?></strong>.
        Ele vale por 1 minuto.
      </p>
      <div aria-live="polite">
        <?php adminAlerta($info, 'success'); ?>
        <?php adminAlerta($erro); ?>
      </div>
      <form method="post" action="/admin/verificar.php?action=verificar" novalidate>
        <?= csrfCampo() ?>
        <div class="mb-4">
          <label for="codigo" class="form-label">Código de verificação</label>
          <input type="text" class="form-control form-control-lg text-center" id="codigo" name="codigo"
                 required inputmode="numeric" pattern="\d{6}" maxlength="6"
                 autocomplete="one-time-code" autofocus>
        </div>
        <button type="submit" class="btn btn-primary w-100">Verificar e entrar</button>
      </form>
      <form method="post" action="/admin/verificar.php?action=reenviar" class="text-center mt-3">
        <?= csrfCampo() ?>
        <button type="submit" class="btn btn-link btn-sm" data-resend-button
                <?= $segundosRestantes > 0 ? 'disabled' : '' ?>>Reenviar código</button>
      </form>
      <?php if ($autoReenvioDisponivel) : ?>
        <form method="post" action="/admin/verificar.php?action=auto_reenviar" data-auto-resend-form hidden>
            <?= csrfCampo() ?>
        </form>
      <?php endif; ?>
      <p class="text-center small text-secondary mt-2 mb-0" data-resend-countdown
         data-seconds="<?= e((string) $segundosRestantes) ?>"
         data-auto-resend="<?= $autoReenvioDisponivel ? 'true' : 'false' ?>" aria-live="off"></p>
      <span class="visually-hidden" data-resend-status aria-live="polite"></span>
      <p class="text-center small mt-2 mb-0"><a href="/admin/login.php" class="link-secondary">Voltar ao login</a></p>
    </div>
  </div>
</div>
<?php
adminRodape();
