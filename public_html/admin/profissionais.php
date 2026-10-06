<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/repositories.php';
require_once __DIR__ . '/../includes/layout/admin.php';
requireAdmin();
$pdo = db();
$erros = [];
$valores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $erros['_geral'] = 'Sua sessão expirou. Recarregue a página.';
    } else {
        $valores = $_POST;
        $erros = validarDadosProfissionalAdmin($pdo, $valores);
        if ($erros === []) {
            salvarProfissionalAdmin($pdo, $valores);
            adminFlash(isset($valores['id']) ? 'Profissional atualizado.' : 'Profissional criado.');
            redirecionar('/admin/profissionais.php');
        }
    }
}
$editarId = (string) ($_GET['editar'] ?? '');
if ($valores === [] && $editarId !== '') {
    $valores = obterProfissionalAdmin($pdo, $editarId) ?? [];
    $valores['servicos'] = listarServicosDoProfissional($pdo, $editarId);
}
$servicos = listarServicosAdmin($pdo);
$profissionais = listarProfissionaisAdmin($pdo);
$flash = consumirAdminFlash();
adminTopo('Profissionais', true);
adminBreadcrumb([['rotulo' => 'Painel', 'url' => '/admin/'], ['rotulo' => 'Profissionais', 'url' => null]]);
?>
<div aria-live="polite"><?php if ($flash !== null) : ?><?php adminAlerta($flash['mensagem'], $flash['tipo']); ?><?php endif; ?><?php adminAlerta(adminErro($erros, '_geral')); ?></div>
<div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 mb-0">Profissionais</h1><a class="btn btn-primary" href="/admin/profissionais.php">Novo profissional</a></div>
<div class="card p-3 p-md-4 mb-4">
<h2 class="h5"><?= isset($valores['id']) ? 'Editar profissional' : 'Cadastrar profissional' ?></h2>
<form method="post" action="/admin/profissionais.php" novalidate><?= csrfCampo() ?><?php if (isset($valores['id'])) : ?><input type="hidden" name="id" value="<?= e((string) $valores['id']) ?>"><?php endif; ?>
<div class="row g-3">
<div class="col-md-6"><label class="form-label" for="nome">Nome</label><input class="form-control" id="nome" name="nome" value="<?= e(adminValor($valores, 'nome')) ?>" required></div>
<div class="col-md-6"><label class="form-label" for="especialidade">Especialidade</label><input class="form-control" id="especialidade" name="especialidade" value="<?= e(adminValor($valores, 'especialidade')) ?>"></div>
<div class="col-12"><label class="form-label" for="bio">Biografia</label><textarea class="form-control" id="bio" name="bio" rows="3"><?= e(adminValor($valores, 'bio')) ?></textarea></div>
<div class="col-12"><label class="form-label" for="foto_url">Foto (caminho local)</label><input class="form-control" id="foto_url" name="foto_url" value="<?= e(adminValor($valores, 'foto_url')) ?>" placeholder="/assets/img/..."></div>
<fieldset class="col-12"><legend class="h6">Serviços vinculados</legend><div class="row"><?php foreach ($servicos as $servico) : ?><div class="col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" name="servicos[]" value="<?= e((string) $servico['id']) ?>" <?= in_array((string) $servico['id'], (array) ($valores['servicos'] ?? []), true) ? 'checked' : '' ?>> <?= e((string) $servico['nome']) ?></label></div><?php endforeach; ?></div></fieldset>
</div><button class="btn btn-primary mt-3" type="submit">Salvar profissional</button>
</form></div>
<div class="card p-3 p-md-4"><h2 class="h5">Equipe</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nome</th><th>Especialidade</th><th>Status</th><th>Ação</th></tr></thead><tbody><?php foreach ($profissionais as $profissional) : ?><tr><td><?= e((string) $profissional['nome']) ?></td><td><?= e((string) $profissional['especialidade']) ?></td><td><?= (int) $profissional['ativo'] === 1 ? 'Ativo' : 'Inativo' ?></td><td><a class="btn btn-sm btn-outline-primary" href="/admin/profissionais.php?editar=<?= e((string) $profissional['id']) ?>">Editar</a></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php adminRodape();
