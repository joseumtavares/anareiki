<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/repositories.php';
require_once __DIR__ . '/../includes/agendamentos-admin.php';
require_once __DIR__ . '/../includes/layout/admin.php';

requireAdmin();
$pdo = db();
$erro = null;
$filtros = [];
foreach (['inicio', 'fim', 'status', 'profissional'] as $campo) {
    $filtros[$campo] = is_string($_GET[$campo] ?? null) ? trim($_GET[$campo]) : '';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $erro = 'Sua sessão expirou. Recarregue a página e tente novamente.';
    } else {
        try {
            foreach (['id', 'origem', 'destino'] as $campo) {
                if (!is_string($_POST[$campo] ?? null)) {
                    throw new DomainException('Ação inválida.');
                }
            }
            if (!uuidValido($_POST['id'])) {
                throw new DomainException('Agendamento inválido.');
            }
            alterarStatusAgendamentoAdmin($pdo, $_POST['id'], $_POST['origem'], $_POST['destino']);
            adminFlash('Status do agendamento atualizado.');
            redirecionar('/admin/agendamentos.php?' . http_build_query($filtros));
        } catch (DomainException $excecao) {
            http_response_code(409);
            $erro = $excecao->getMessage();
        }
    }
}
$agendamentos = [];
try {
    $agendamentos = listarAgendamentosAdmin($pdo, $filtros);
} catch (InvalidArgumentException $excecao) {
    http_response_code(422);
    $erro = $excecao->getMessage();
}
$profissionais = listarProfissionaisAdmin($pdo);
$flash = consumirAdminFlash();
$rotulos = [
    'pendente' => 'Pendente', 'confirmado' => 'Confirmado',
    'cancelado' => 'Cancelado', 'concluido' => 'Concluído',
];
adminTopo('Agendamentos', true);
adminBreadcrumb([['rotulo' => 'Painel', 'url' => '/admin/'], ['rotulo' => 'Agendamentos', 'url' => null]]);
adminAlerta($erro);
if ($flash !== null) {
    adminAlerta($flash['mensagem'], $flash['tipo']);
}
?>
<h1 class="h3 mb-4">Agendamentos</h1>
<form method="get" action="/admin/agendamentos.php" class="card p-4 mb-4">
  <div class="row g-3">
    <?php foreach (['inicio' => 'Data inicial', 'fim' => 'Data final'] as $campo => $rotulo) : ?>
    <div class="col-12 col-md-3">
      <label class="form-label" for="<?= e($campo) ?>"><?= e($rotulo) ?></label>
      <input class="form-control" type="date" id="<?= e($campo) ?>"
             name="<?= e($campo) ?>" value="<?= e($filtros[$campo]) ?>">
    </div>
    <?php endforeach; ?>
    <div class="col-12 col-md-3">
      <label for="status" class="form-label">Status</label>
      <select id="status" name="status" class="form-select">
        <option value="">Todos</option>
        <?php foreach ($rotulos as $valor => $rotulo) : ?>
        <option value="<?= e($valor) ?>" <?= $filtros['status'] === $valor ? 'selected' : '' ?>>
            <?= e($rotulo) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-md-3">
      <label for="profissional" class="form-label">Profissional</label>
      <select id="profissional" name="profissional" class="form-select">
        <option value="">Todos</option>
        <?php foreach ($profissionais as $item) : ?>
        <option value="<?= e((string) $item['id']) ?>"
                <?= $filtros['profissional'] === $item['id'] ? 'selected' : '' ?>>
            <?= e((string) $item['nome']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="mt-3"><button class="btn btn-primary" type="submit">Filtrar</button>
    <a href="/admin/agendamentos.php" class="btn btn-outline-secondary">Limpar filtros</a>
  </div>
</form>
<div class="card p-4">
  <?php if ($agendamentos === []) : ?>
  <p class="text-secondary mb-0">Nenhum agendamento encontrado para estes filtros.</p>
  <?php else : ?>
  <div class="table-responsive">
    <table class="table align-middle">
      <caption>Agendamentos encontrados</caption>
      <thead><tr>
        <th scope="col">Identificador</th><th scope="col">Data e horário</th>
        <th scope="col">Serviço</th><th scope="col">Profissional</th>
        <th scope="col">Status</th><th scope="col">Ações</th>
      </tr></thead>
      <tbody>
        <?php foreach ($agendamentos as $item) : ?>
        <tr>
          <td><small><?= e((string) $item['id']) ?></small></td>
          <td><?= e((string) $item['data']) ?> <?= e(substr((string) $item['hora_inicio'], 0, 5)) ?></td>
          <td><?= e((string) $item['servico_nome']) ?></td>
          <td><?= e((string) $item['profissional_nome']) ?></td>
          <td><?= e($rotulos[$item['status']] ?? (string) $item['status']) ?></td>
          <td>
            <?php foreach (destinosStatusAgendamento((string) $item['status']) as $destino) : ?>
            <form method="post" class="d-inline">
                <?= csrfCampo() ?>
              <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
              <input type="hidden" name="origem" value="<?= e((string) $item['status']) ?>">
              <button class="btn btn-sm btn-primary mb-1" type="submit"
                      name="destino" value="<?= e($destino) ?>"><?= e($rotulos[$destino]) ?></button>
            </form>
            <?php endforeach; ?>
            <?php if (destinosStatusAgendamento((string) $item['status']) === []) : ?>
            <span class="text-secondary">Finalizado</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php adminRodape();
