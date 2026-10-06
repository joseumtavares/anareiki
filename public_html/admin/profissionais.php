<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/repositories.php';
require_once __DIR__ . '/../includes/uploads.php';
require_once __DIR__ . '/../includes/layout/admin.php';
requireAdmin();
$pdo = db();
$erros = [];
$valores = [];
$imagensDisponiveis = listarImagensUpload('profissionais');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $erros['_geral'] = 'Sua sessão expirou. Recarregue a página.';
    } else {
        $valores = $_POST;
        $acao = (string) ($_POST['acao'] ?? '');
        if ($acao === 'alternar' || $acao === 'excluir') {
            $id = (string) ($_POST['id'] ?? '');
            if ($acao === 'excluir') {
                try { excluirProfissionalAdmin($pdo, $id); adminFlash('Profissional excluído.'); redirecionar('/admin/profissionais.php'); } catch (DomainException $e) { $erros['_geral'] = $e->getMessage(); }
            } else {
                $stmt = $pdo->prepare('UPDATE profissionais SET ativo = ? WHERE id = ?');
                $stmt->execute([(string) ($_POST['ativo'] ?? '0') === '1' ? 1 : 0, $id]);
                adminFlash('Status do profissional atualizado.'); redirecionar('/admin/profissionais.php');
            }
        }
        if (($valores['imagem_existente'] ?? '') !== '' && in_array($valores['imagem_existente'], $imagensDisponiveis, true)) { $valores['foto_url'] = $valores['imagem_existente']; }
        if (isset($_FILES['foto_upload']) && ($_FILES['foto_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try { $valores['foto_url'] = salvarUploadImagem($_FILES['foto_upload'], 'profissionais'); } catch (InvalidArgumentException|RuntimeException $e) { $erros['foto_upload'] = $e->getMessage(); }
        }
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
<form method="post" action="/admin/profissionais.php" enctype="multipart/form-data" novalidate><?= csrfCampo() ?><?php if (isset($valores['id'])) : ?><input type="hidden" name="id" value="<?= e((string) $valores['id']) ?>"><?php endif; ?>
<div class="row g-3">
<div class="col-md-6"><label class="form-label" for="nome">Nome</label><input class="form-control" id="nome" name="nome" value="<?= e(adminValor($valores, 'nome')) ?>" required></div>
<div class="col-md-6"><label class="form-label" for="especialidade">Especialidade</label><input class="form-control" id="especialidade" name="especialidade" value="<?= e(adminValor($valores, 'especialidade')) ?>"></div>
<div class="col-12"><label class="form-label" for="bio">Biografia</label><textarea class="form-control" id="bio" name="bio" rows="3"><?= e(adminValor($valores, 'bio')) ?></textarea></div>
<div class="col-12"><label class="form-label" for="foto_upload">Foto do profissional</label><input class="form-control" type="file" id="foto_upload" name="foto_upload" accept="image/jpeg,image/png,image/webp"><div class="form-text">JPG, PNG ou WEBP, até 5 MB.</div></div>
<?php if ($imagensDisponiveis !== []) : ?><fieldset class="col-12"><legend class="h6">Miniaturas disponíveis</legend><div class="row g-3"><?php foreach ($imagensDisponiveis as $imagem) : ?><div class="col-6 col-sm-3"><label class="card p-2"><img src="<?= e($imagem) ?>" alt="Miniatura disponível" class="img-fluid rounded" style="aspect-ratio:1;object-fit:cover"><span><input type="radio" name="imagem_existente" value="<?= e($imagem) ?>" <?= adminValor($valores, 'foto_url') === $imagem ? 'checked' : '' ?>> Usar esta</span></label></div><?php endforeach; ?></div></fieldset><?php endif; ?>
<fieldset class="col-12"><legend class="h6">Serviços vinculados</legend><div class="row"><?php foreach ($servicos as $servico) : ?><div class="col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" name="servicos[]" value="<?= e((string) $servico['id']) ?>" <?= in_array((string) $servico['id'], (array) ($valores['servicos'] ?? []), true) ? 'checked' : '' ?>> <?= e((string) $servico['nome']) ?></label></div><?php endforeach; ?></div></fieldset>
</div><button class="btn btn-primary mt-3" type="submit">Salvar profissional</button>
</form></div>
<div class="card p-3 p-md-4"><h2 class="h5">Equipe</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Foto</th><th>Nome</th><th>Especialidade</th><th>Status</th><th>Ação</th></tr></thead><tbody><?php foreach ($profissionais as $profissional) : ?><tr><td><?php if (!empty($profissional['foto_url'])) : ?><img src="<?= e((string) $profissional['foto_url']) ?>" alt="Foto de <?= e((string) $profissional['nome']) ?>" width="48" height="48" class="rounded object-fit-cover"><?php endif; ?></td><td><?= e((string) $profissional['nome']) ?></td><td><?= e((string) $profissional['especialidade']) ?></td><td><?= (int) $profissional['ativo'] === 1 ? 'Ativo' : 'Inativo' ?></td><td><a class="btn btn-sm btn-outline-primary" href="/admin/profissionais.php?editar=<?= e((string) $profissional['id']) ?>">Editar</a> <form class="d-inline" method="post" action="/admin/profissionais.php"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="acao" value="alternar"><input type="hidden" name="id" value="<?= e((string) $profissional['id']) ?>"><input type="hidden" name="ativo" value="<?= (int) $profissional['ativo'] === 1 ? '0' : '1' ?>"><button class="btn btn-sm btn-outline-secondary" type="submit"><?= (int) $profissional['ativo'] === 1 ? 'Desativar' : 'Ativar' ?></button></form> <form class="d-inline" method="post" action="/admin/profissionais.php" onsubmit="return confirm('Excluir este profissional permanentemente?');"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= e((string) $profissional['id']) ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button></form></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php adminRodape();
