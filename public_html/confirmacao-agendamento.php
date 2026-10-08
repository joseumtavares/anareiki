<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/repositories.php';
require_once __DIR__ . '/includes/public-view.php';

$id = trim((string) ($_GET['id'] ?? ''));

if (!uuidValido($id)) {
    http_response_code(400);
    echo 'Agendamento inválido.';
    exit;
}

$agendamento = null;
try {
    $agendamento = obterAgendamento(db(), $id);
} catch (Throwable $e) {
    registrarErroAplicacao($e, gerarRequestId());
}

if ($agendamento === null) {
    http_response_code(404);
    echo 'Agendamento não encontrado.';
    exit;
}

$cfg = config();
$whatsappPhone = $cfg['whatsapp_phone'] ?? '';

$dataFormatada = date('d/m/Y', strtotime($agendamento['data']));
$preco = $agendamento['preco'] !== null
    ? 'R$ ' . number_format((float) $agendamento['preco'], 2, ',', '.')
    : 'Consultar valor';

$mensagem = "**** \u{1F4C6} MEU AGENDAMENTO ****\n"
    . "\u{1F464} CLIENTE: *" . $agendamento['cliente_nome'] . "*\n"
    . "\u{1F4DE} TELEFONE: " . $agendamento['cliente_telefone'] . "\n"
    . "=-=-=-=-=-=-=-=-=-=-=-=-=-=\n"
    . "\u{1F4CC} DIA " . $dataFormatada . "\n"
    . "\u{231A} HORARIO " . $agendamento['hora_inicio'] . "\n\n"
    . "\u{1F486} PROFISSIONAL\n"
    . $agendamento['profissional_nome'] . "\n\n"
    . "\u{2728} SERVICO\n"
    . "*" . $agendamento['servico_nome'] . "* - " . $preco . "\n\n"
    . "Ola!\n\n"
    . "Agendamento realizado com sucesso.\n"
    . "Favor chegar com 10 minutos de antecedencia.\n"
    . "=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n"
    . "COMPROVANTE DE AGENDAMENTO";

$linkWhatsapp = 'https://wa.me/' . urlencode($whatsappPhone)
    . '?text=' . urlencode($mensagem);

$fontesUrl = 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400'
    . '&family=Playfair+Display:ital,wght@0,400;0,600;1,400'
    . '&family=Lato:wght@300;400;700&display=swap';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Agendamento Confirmado — Reiki Ana</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="<?= htmlPublico($fontesUrl) ?>" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/static/style-01-foundation.css">
  <link rel="stylesheet" href="/static/style-06-footer-responsive.css">
  <link rel="stylesheet" href="/static/confirmacao.css">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
</head>
<body>
<nav id="navbar">
  <div class="nav-container">
    <a href="/" class="nav-logo">
      <span class="logo-script">Reiki Ana</span>
      <span class="logo-sub">Massoterapeuta</span>
    </a>
  </div>
</nav>

<div class="conf-page">
  <div class="conf-icon"><i class="fas fa-check-circle"></i></div>
  <h1 class="conf-title">Agendamento registrado!</h1>
  <p class="conf-sub">Envie a confirmação pelo WhatsApp para finalizar.</p>

  <div class="conf-card"><?= htmlPublico($mensagem) ?></div>

  <a href="<?= htmlPublico($linkWhatsapp) ?>"
     target="_blank" rel="noopener"
     class="btn-whatsapp">
    <i class="fab fa-whatsapp"></i> Enviar para WhatsApp
  </a>

  <br>
  <a href="/" class="btn-voltar">
    <i class="fas fa-arrow-left"></i> Voltar ao início
  </a>
</div>
<a href="https://wa.me/5548996137757" target="_blank" rel="noopener"
   class="whatsapp-float" aria-label="WhatsApp">
  <i class="fab fa-whatsapp"></i>
  <span class="wpp-tooltip">Tire suas dúvidas!</span>
</a>
</body>
</html>
