<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/repositories.php';
require_once __DIR__ . '/includes/public-view.php';
require_once __DIR__ . '/includes/slots.php';
require_once __DIR__ . '/includes/csrf.php';

session_start();

$erro = '';
$sucesso = false;
$agendamentoId = '';

// --- POST handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!csrfValido($token)) {
        http_response_code(403);
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        $servicoId = trim((string) ($_POST['servico_id'] ?? ''));
        $profissionalId = trim((string) ($_POST['profissional_id'] ?? ''));
        $data = trim((string) ($_POST['data'] ?? ''));
        $horaInicio = trim((string) ($_POST['hora_inicio'] ?? ''));
        $clienteNome = trim((string) ($_POST['cliente_nome'] ?? ''));
        $clienteTelefone = trim((string) ($_POST['cliente_telefone'] ?? ''));

        if (!uuidValido($servicoId) || !uuidValido($profissionalId)) {
            $erro = 'Selecione um serviço e profissional válidos.';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || $data < date('Y-m-d')) {
            $erro = 'Selecione uma data válida (não pode ser no passado).';
        } elseif (!preg_match('/^\d{2}:\d{2}$/', $horaInicio)) {
            $erro = 'Selecione um horário válido.';
        } elseif ($clienteNome === '' || mb_strlen($clienteNome) < 2 || mb_strlen($clienteNome) > 100) {
            $erro = 'Informe seu nome (entre 2 e 100 caracteres).';
        } elseif (!preg_match('/^\d{10,15}$/', preg_replace('/\D/', '', $clienteTelefone))) {
            $erro = 'Informe um telefone válido (somente números, 10 a 15 dígitos).';
        } else {
            try {
                $pdo = db();
                $erroSlot = validarSlotDisponivel(
                    $pdo,
                    $servicoId,
                    $profissionalId,
                    $data,
                    $horaInicio
                );

                if ($erroSlot !== null) {
                    $erro = $erroSlot;
                } else {
                    $stmtDur = $pdo->prepare(
                        'SELECT duracao_min FROM servicos WHERE id = ?'
                    );
                    $stmtDur->execute([$servicoId]);
                    $duracaoMin = (int) $stmtDur->fetchColumn();

                    $telefoneNumeros = preg_replace('/\D/', '', $clienteTelefone);

                    $agendamentoId = criarAgendamento(
                        $pdo,
                        $servicoId,
                        $profissionalId,
                        $data,
                        $horaInicio,
                        $duracaoMin,
                        $clienteNome,
                        $telefoneNumeros
                    );

                    header(
                        'Location: /confirmacao-agendamento.php?id='
                        . urlencode($agendamentoId),
                        true,
                        303
                    );
                    exit;
                }
            } catch (\PDOException $e) {
                if (
                    str_contains($e->getMessage(), 'uq_agend_slot')
                    || str_contains($e->getMessage(), 'UNIQUE constraint')
                ) {
                    http_response_code(409);
                    $erro = 'Este horário acabou de ser reservado. '
                        . 'Escolha outro horário.';
                } else {
                    error_log('Erro ao criar agendamento: ' . $e->getMessage());
                    http_response_code(500);
                    $erro = 'Erro interno. Tente novamente.';
                }
            }
        }
    }
}

// --- Dados para a página ---
$servicos = [];
try {
    $pdo = db();
    $servicos = listarServicosPublicos($pdo);
} catch (Throwable $e) {
    error_log('Falha ao carregar serviços: ' . $e->getMessage());
}

$fontesUrl = 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400'
    . '&family=Playfair+Display:ital,wght@0,400;0,600;1,400'
    . '&family=Lato:wght@300;400;700&display=swap';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Agendar — Reiki Ana</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="<?= htmlPublico($fontesUrl) ?>" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/static/style-01-foundation.css">
  <link rel="stylesheet" href="/static/style-06-footer-responsive.css">
  <link rel="stylesheet" href="/static/calendar.css">
  <link rel="stylesheet" href="/static/agendar.css">
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

<div class="agendar-page">
  <a href="/" class="back-link"><i class="fas fa-arrow-left"></i> Voltar</a>
  <h1 class="agendar-title"><span class="script">Agendar</span> sua sessão</h1>
  <p class="agendar-sub">Escolha o serviço, profissional, data e horário desejados.</p>

<?php if ($erro !== '') : ?>
  <div class="erro-msg" role="alert"><?= htmlPublico($erro) ?></div>
<?php endif; ?>

  <form id="formAgendar" method="post" action="/agendar.php">
    <?= csrfCampo() ?>

    <div class="step active" id="step1">
      <div class="field">
        <label for="servico_id">Serviço</label>
        <select id="servico_id" name="servico_id" required>
          <option value="">Selecione um serviço</option>
<?php foreach ($servicos as $s) : ?>
          <option value="<?= htmlPublico($s['id']) ?>"
            data-duracao="<?= (int) $s['duracao_min'] ?>"
            data-preco="<?=
              $s['preco'] !== null
                ? htmlPublico(number_format((float) $s['preco'], 2, ',', '.'))
                : 'Consultar'
            ?>">
            <?= htmlPublico($s['nome']) ?>
            (<?= (int) $s['duracao_min'] ?> min)
          </option>
<?php endforeach; ?>
        </select>
      </div>
      <div class="field" id="profField" style="display:none">
        <label for="profissional_id">Profissional</label>
        <select id="profissional_id" name="profissional_id" required>
          <option value="">Carregando...</option>
        </select>
      </div>
    </div>

    <div class="step" id="step2">
      <div class="field">
        <label>Escolha a data</label>
        <div id="calendar-container"></div>
        <input type="hidden" id="data" name="data">
      </div>
    </div>

    <div class="step" id="step3">
      <div class="field">
        <label>Horários disponíveis</label>
        <div id="slots-container" class="slots-grid"></div>
        <input type="hidden" id="hora_inicio" name="hora_inicio">
        <p id="slots-loading" style="display:none;color:var(--text-light)">
          Carregando horários...
        </p>
        <p id="slots-vazio" style="display:none;color:var(--text-light)">
          Nenhum horário disponível nesta data.
        </p>
      </div>
    </div>

    <div class="step" id="step4">
      <div class="resumo" id="resumo"></div>
      <div class="field">
        <label for="cliente_nome">Seu nome</label>
        <input type="text" id="cliente_nome" name="cliente_nome"
          required minlength="2" maxlength="100"
          placeholder="Nome completo">
      </div>
      <div class="field">
        <label for="cliente_telefone">Seu telefone (WhatsApp)</label>
        <input type="tel" id="cliente_telefone" name="cliente_telefone"
          required pattern="\d{10,15}" maxlength="15"
          placeholder="48996249817">
      </div>
      <button type="submit" class="btn-agendar" id="btnConfirmar">
        <i class="fas fa-calendar-check"></i> Confirmar agendamento
      </button>
    </div>
  </form>
</div>

<script src="/static/calendar.js"></script>
<script src="/static/agendar.js"></script>
</body>
</html>
