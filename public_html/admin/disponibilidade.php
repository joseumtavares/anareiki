<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/repositories.php';
require_once __DIR__ . '/../includes/disponibilidade-data.php';
require_once __DIR__ . '/../includes/layout/admin.php';

requireAdmin();
$pdo = db();
$entrada = $_POST['profissional_id'] ?? $_GET['profissional'] ?? '';
$profissionalId = is_string($entrada) ? $entrada : '';
$profissional = obterProfissionalAdmin($pdo, $profissionalId);
$erro = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!csrfValido($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            throw new InvalidArgumentException('Sua sessão expirou. Recarregue a página.');
        }
        $data = $_POST['data'] ?? null;
        $horarios = $_POST['horarios'] ?? [];
        if (
            !is_string($data) || !is_array($horarios)
            || count(array_filter($horarios, 'is_string')) !== count($horarios)
        ) {
            throw new InvalidArgumentException('Selecione uma data e horários válidos.');
        }
        $intervalo = filter_var($_POST['intervalo'] ?? 30, FILTER_VALIDATE_INT);
        if ($intervalo === false) {
            throw new InvalidArgumentException('Escolha um intervalo válido.');
        }
        salvarDisponibilidadeData($pdo, $profissionalId, $data, array_values($horarios), $intervalo);
        adminFlash($horarios === [] ? 'Dia bloqueado para novos agendamentos.' : 'Horários do dia publicados.');
        redirecionar('/admin/disponibilidade.php?profissional=' . urlencode($profissionalId));
    } catch (InvalidArgumentException $excecao) {
        $erro = $excecao->getMessage();
    }
}
$profissionais = listarProfissionaisAdmin($pdo);
$datas = [];
$migracaoPendente = false;
if ($profissional !== null) {
    try {
        $stmt = $pdo->prepare('SELECT data, horarios FROM disponibilidade_datas
            WHERE profissional_id = ? AND data >= ? ORDER BY data');
        $stmt->execute([$profissionalId, date('Y-m-d')]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $datas[$item['data']] = decodificarDisponibilidadeData($item['horarios']);
        }
    } catch (PDOException $excecao) {
        if ((string) $excecao->getCode() !== '42S02') {
            throw $excecao;
        }
        $migracaoPendente = true;
        $erro = 'O calendário precisa da migração 003_disponibilidade_datas antes de ser utilizado.';
    }
}
$flash = consumirAdminFlash();
adminTopo('Disponibilidade', true);
adminBreadcrumb([['rotulo' => 'Painel', 'url' => '/admin/'], ['rotulo' => 'Disponibilidade', 'url' => null]]);
adminAlerta($erro);
if ($flash !== null) {
    adminAlerta($flash['mensagem'], $flash['tipo']);
}
?>
<link rel="stylesheet" href="/static/admin-disponibilidade.css">
<h1 class="h3 mb-4">Disponibilidade por data</h1>
<form method="get" class="card p-4 mb-4">
  <label class="form-label" for="profissional">Profissional</label>
  <select class="form-select" id="profissional" name="profissional">
    <option value="">Selecione</option>
    <?php foreach ($profissionais as $item) : ?>
    <option value="<?= e((string) $item['id']) ?>" <?= $profissionalId === $item['id'] ? 'selected' : '' ?>>
        <?= e((string) $item['nome']) ?>
    </option>
    <?php endforeach; ?>
  </select>
  <div class="mt-3"><button class="btn btn-primary" type="submit">Abrir calendário</button></div>
</form>
<?php if ($profissional !== null && !$migracaoPendente) : ?>
<div data-availability-calendar data-today="<?= e(date('Y-m-d')) ?>"
     data-days="<?= e(json_encode($datas, JSON_THROW_ON_ERROR)) ?>" class="row g-4">
  <section class="col-12 col-lg-7">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <button type="button" class="btn btn-outline-secondary" data-month-prev aria-label="Mês anterior">‹</button>
        <h2 class="h5 mb-0" data-month-title></h2>
        <button type="button" class="btn btn-outline-secondary" data-month-next aria-label="Próximo mês">›</button>
      </div>
      <p class="small">Um clique: selecionar dia disponível. Dois cliques: bloquear o dia.</p>
      <div class="availability-grid mb-2" aria-hidden="true">
        <span>Dom</span><span>Seg</span><span>Ter</span><span>Qua</span>
        <span>Qui</span><span>Sex</span><span>Sáb</span>
      </div>
      <div class="availability-grid" data-calendar-days></div>
      <p class="small text-secondary mt-3 mb-0">
        Verde: horários configurados. Vermelho: bloqueado.
        Datas neutras seguem a disponibilidade semanal existente.
      </p>
    </div>
  </section>
  <section class="col-12 col-lg-5">
    <form method="post" class="card p-4">
      <?= csrfCampo() ?>
      <input type="hidden" name="profissional_id" value="<?= e($profissionalId) ?>">
      <input type="hidden" name="data" data-selected-date>
      <h2 class="h5" data-day-title>Selecione um dia</h2>
      <label class="form-label" for="intervalo">Intervalo dos agendamentos</label>
      <select id="intervalo" name="intervalo" class="form-select mb-3" data-slot-interval disabled>
        <option value="30">30 minutos</option>
        <option value="60">60 minutos</option>
      </select>
      <p class="small">Marque blocos consecutivos. A duração reservada será arredondada para cobrir o serviço.</p>
      <p data-day-message role="status"></p>
      <div class="availability-hours" data-day-hours></div>
      <button type="button" class="btn btn-outline-danger mt-3" data-block-day disabled>Bloquear dia inteiro</button>
      <button class="btn btn-primary mt-3" type="submit" data-save-day disabled>Salvar dia</button>
      <p class="small text-secondary mt-3 mb-0">As alterações são publicadas ao salvar. Reservas existentes são preservadas.</p>
    </form>
  </section>
</div>
<script src="/static/admin-disponibilidade.js" defer></script>
<?php endif; ?>
<?php adminRodape();
