<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/repositories.php';
require_once __DIR__ . '/../includes/layout/admin.php';

$admin = requireAdmin();
$flash = consumirAdminFlash();
$categorias = [];
try {
    $categorias = listarCategoriasServicos(db());
} catch (Throwable $erro) {
    error_log('Falha ao carregar categorias do painel: ' . $erro->getMessage());
}

adminTopo('Painel', true);
?>
<?php adminBreadcrumb([['rotulo' => 'Painel', 'url' => null]]); ?>
<div aria-live="polite">
  <?php if ($flash !== null) : ?>
        <?php adminAlerta($flash['mensagem'], $flash['tipo']); ?>
  <?php endif; ?>
</div>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <p class="text-uppercase small fw-bold text-secondary mb-1">Visão geral</p>
    <h1 class="h3 mb-0">Olá, <?= e($admin['nome']) ?></h1>
  </div>
</div>
<div class="row g-4 mb-4">
  <?php foreach (
    [
      ['Serviços', 'Cadastre e organize o catálogo.', '/admin/servicos.php'],
      ['Profissionais', 'Mantenha a equipe e seus vínculos.', '/admin/profissionais.php'],
      ['Disponibilidade', 'Configure os horários recorrentes.', '/admin/disponibilidade.php'],
      ['Agendamentos', 'Acompanhe e atualize a agenda.', '/admin/agendamentos.php'],
    ] as [$titulo, $descricao, $url]
) : ?>
    <div class="col-12 col-md-6">
      <a class="card h-100 p-4 text-decoration-none" href="<?= e($url) ?>">
        <h2 class="h5 text-dark"><?= e($titulo) ?></h2>
        <p class="text-secondary mb-3"><?= e($descricao) ?></p>
        <span class="btn btn-primary align-self-start">Abrir módulo</span>
      </a>
    </div>
  <?php endforeach; ?>
</div>
<div class="card p-4">
  <h2 class="h5">Agenda</h2>
  <p class="mb-0 text-secondary">Os próximos agendamentos aparecerão aqui quando o módulo de agenda for concluído.</p>
</div>
<div class="card p-4 mt-4">
  <div class="d-flex justify-content-between align-items-center"><h2 class="h5 mb-0">Categorias</h2><a class="btn btn-sm btn-outline-primary" href="/admin/servicos.php">Adicionar categoria</a></div>
  <p class="text-secondary mt-2 mb-2">Categorias disponíveis para os serviços:</p>
  <div class="d-flex flex-wrap gap-2"><?php foreach ($categorias as $categoria) : ?><span class="badge rounded-pill text-bg-light border"><?= e($categoria) ?></span><?php endforeach; ?><?php if ($categorias === []) : ?><span class="text-secondary">Nenhuma categoria cadastrada.</span><?php endif; ?></div>
</div>
<?php
adminRodape();
