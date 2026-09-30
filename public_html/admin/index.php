<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout/admin.php';

$admin = requireAdmin();

adminTopo('Painel');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <p class="fs-3 marca mb-0">Reiki Ana</p>
  <form method="post" action="/admin/logout.php">
    <?= csrfCampo() ?>
    <button type="submit" class="btn btn-outline-secondary btn-sm">Sair</button>
  </form>
</div>
<div class="card p-4">
  <h1 class="h4">Olá, <?= e($admin['nome']) ?></h1>
  <p class="mb-0 text-secondary">
    Login com verificação em duas etapas concluído.
    Serviços, profissionais, disponibilidade e agendamentos chegam na Fase 5.
  </p>
</div>
<?php
adminRodape();
