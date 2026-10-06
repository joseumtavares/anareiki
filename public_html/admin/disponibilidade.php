<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/repositories.php';
require_once __DIR__ . '/../includes/layout/admin.php';
requireAdmin();
$pdo = db();
$profissionalId = (string) ($_GET['profissional'] ?? $_POST['profissional_id'] ?? '');
$profissional = obterProfissionalAdmin($pdo, $profissionalId);
$erros = [];
$valores = ['profissional_id' => $profissionalId];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['csrf_token'] ?? null)) { http_response_code(403); $erros['_geral'] = 'Sua sessão expirou.'; }
    elseif (($_POST['acao'] ?? '') === 'excluir') { excluirDisponibilidadeAdmin($pdo, (string) $_POST['id']); adminFlash('Intervalo removido.'); redirecionar('/admin/disponibilidade.php?profissional=' . urlencode($profissionalId)); }
    else { $valores = $_POST; $erros = validarDadosDisponibilidadeAdmin($pdo, $valores, $profissionalId, (string) ($valores['id'] ?? '') ?: null); if ($erros === []) { salvarDisponibilidadeAdmin($pdo, $valores); adminFlash('Disponibilidade salva.'); redirecionar('/admin/disponibilidade.php?profissional=' . urlencode($profissionalId)); } }
}
$profissionais = listarProfissionaisAdmin($pdo);
$intervalos = $profissional ? listarDisponibilidadeAdmin($pdo, $profissionalId) : [];
$flash = consumirAdminFlash();
$dias = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
adminTopo('Disponibilidade', true);
adminBreadcrumb([['rotulo' => 'Painel', 'url' => '/admin/'], ['rotulo' => 'Disponibilidade', 'url' => null]]);
?>
<div aria-live="polite"><?php if ($flash !== null) : ?><?php adminAlerta($flash['mensagem'], $flash['tipo']); ?><?php endif; ?><?php adminAlerta(adminErro($erros, '_geral')); ?></div>
<h1 class="h3 mb-4">Disponibilidade</h1>
<div class="card p-3 p-md-4 mb-4"><form method="get" action="/admin/disponibilidade.php"><label class="form-label" for="profissional">Profissional</label><select class="form-select" id="profissional" name="profissional" onchange="this.form.submit()"><option value="">Selecione</option><?php foreach ($profissionais as $item) : ?><option value="<?= e((string) $item['id']) ?>" <?= $profissionalId === $item['id'] ? 'selected' : '' ?>><?= e((string) $item['nome']) ?></option><?php endforeach; ?></select></form></div>
<?php if ($profissional) : ?><div class="card p-3 p-md-4 mb-4"><h2 class="h5">Novo intervalo — <?= e((string) $profissional['nome']) ?></h2><form method="post" action="/admin/disponibilidade.php"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="profissional_id" value="<?= e($profissionalId) ?>"><div class="row g-3"><div class="col-md-4"><label class="form-label">Dia</label><select class="form-select" name="dia_semana"><?php foreach ($dias as $numero => $dia) : ?><option value="<?= $numero ?>"><?= e($dia) ?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label">Início</label><input class="form-control" type="time" name="hora_inicio" required></div><div class="col-md-4"><label class="form-label">Fim</label><input class="form-control" type="time" name="hora_fim" required></div></div><button class="btn btn-primary mt-3">Salvar intervalo</button></form></div><div class="card p-3 p-md-4"><h2 class="h5">Intervalos cadastrados</h2><table class="table"><tbody><?php foreach ($intervalos as $intervalo) : ?><tr><td><?= e($dias[(int) $intervalo['dia_semana']]) ?></td><td><?= e((string) $intervalo['hora_inicio']) ?> — <?= e((string) $intervalo['hora_fim']) ?></td><td class="text-end"><form method="post" onsubmit="return confirm('Remover este intervalo?');"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="profissional_id" value="<?= e($profissionalId) ?>"><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= e((string) $intervalo['id']) ?>"><button class="btn btn-sm btn-outline-danger">Remover</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
<?php adminRodape();
