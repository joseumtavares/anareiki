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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $erros['_geral'] = 'Sua sessão expirou. Recarregue a página e tente novamente.';
    } elseif (($_POST['acao'] ?? '') === 'alternar' || ($_POST['acao'] ?? '') === 'excluir') {
        $id = (string) ($_POST['id'] ?? '');
        if (!uuidValido($id) || obterServicoAdmin($pdo, $id) === null) {
            $erros['_geral'] = 'Serviço não encontrado.';
        } elseif (($_POST['acao'] ?? '') === 'excluir') {
            try {
                excluirServicoAdmin($pdo, $id);
                adminFlash('Serviço excluído.');
                redirecionar('/admin/servicos.php');
            } catch (DomainException $e) {
                $erros['_geral'] = $e->getMessage();
            }
        } else {
            alterarAtivoServico($pdo, $id, (string) ($_POST['ativo'] ?? '0') === '1');
            adminFlash('Status do serviço atualizado.');
            redirecionar('/admin/servicos.php');
        }
    } else {
        $valores = $_POST;
        if (isset($_FILES['imagem_upload']) && ($_FILES['imagem_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $valores['imagem_url'] = salvarUploadImagem($_FILES['imagem_upload'], 'servicos');
            } catch (InvalidArgumentException | RuntimeException $e) {
                $erros['imagem_upload'] = $e->getMessage();
            }
        }
        $erros = array_merge($erros, validarDadosServicoAdmin($valores));
        if ($erros === []) {
            salvarServicoAdmin($pdo, $valores);
            adminFlash(isset($valores['id']) && $valores['id'] !== '' ? 'Serviço atualizado.' : 'Serviço criado.');
            redirecionar('/admin/servicos.php');
        }
    }
}

$editarId = isset($_GET['editar']) ? (string) $_GET['editar'] : '';
if ($valores === [] && $editarId !== '') {
    if (!uuidValido($editarId) || ($valores = obterServicoAdmin($pdo, $editarId)) === null) {
        $erros['_geral'] = 'Serviço não encontrado.';
        $valores = [];
    }
}
$flash = consumirAdminFlash();
$servicos = listarServicosAdmin($pdo);

adminTopo('Serviços', true);
adminBreadcrumb([
    ['rotulo' => 'Painel', 'url' => '/admin/'],
    ['rotulo' => 'Serviços', 'url' => null],
]);
?>
<div aria-live="polite">
  <?php if ($flash !== null) :
        ?><?php adminAlerta($flash['mensagem'], $flash['tipo']); ?><?php
  endif; ?>
  <?php adminAlerta(adminErro($erros, '_geral')); ?>
</div>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">Serviços</h1>
  <a class="btn btn-primary" href="/admin/servicos.php">Novo serviço</a>
</div>
<div class="card p-3 p-md-4 mb-4">
  <h2 class="h5"><?= isset($valores['id']) && $valores['id'] !== '' ? 'Editar serviço' : 'Cadastrar serviço' ?></h2>
  <form method="post" action="/admin/servicos.php" enctype="multipart/form-data" novalidate>
    <?= csrfCampo() ?>
    <?php if (!empty($valores['id'])) :
        ?><input type="hidden" name="id" value="<?= e((string) $valores['id']) ?>"><?php
    endif; ?>
    <div class="row g-3">
      <?php foreach ([['nome', 'Nome', 'text'], ['categoria', 'Categoria', 'text'], ['duracao_min', 'Duração (minutos)', 'number'], ['preco', 'Preço (opcional)', 'number'], ['ordem', 'Ordem', 'number'], ['icone', 'Ícone', 'text'], ['cor', 'Cor', 'text'], ['tag', 'Tag', 'text']] as [$campo, $rotulo, $tipo]) : ?>
        <div class="col-12 col-md-<?= in_array($campo, ['nome', 'categoria'], true) ? '6' : '4' ?>">
          <label class="form-label" for="<?= e($campo) ?>"><?= e($rotulo) ?></label>
          <input class="form-control<?= adminErro($erros, $campo) !== null ? ' is-invalid' : '' ?>" type="<?= e($tipo) ?>" id="<?= e($campo) ?>" name="<?= e($campo) ?>" value="<?= e(adminValor($valores, $campo)) ?>">
            <?php if (adminErro($erros, $campo) !== null) :
                ?><div class="invalid-feedback"><?= e((string) adminErro($erros, $campo)) ?></div><?php
            endif; ?>
        </div>
      <?php endforeach; ?>
      <div class="col-12 col-md-6">
        <label class="form-label" for="imagem_upload">Imagem do serviço</label>
        <input class="form-control<?= adminErro($erros, 'imagem_upload') !== null ? ' is-invalid' : '' ?>" type="file" id="imagem_upload" name="imagem_upload" accept="image/jpeg,image/png,image/webp">
        <div class="form-text">JPG, PNG ou WEBP, até 5 MB.</div>
        <?php if (adminErro($erros, 'imagem_upload') !== null) :
            ?><div class="invalid-feedback"><?= e((string) adminErro($erros, 'imagem_upload')) ?></div><?php
        endif; ?>
      </div>
      <div class="col-12">
        <label class="form-label" for="descricao">Descrição</label>
        <textarea class="form-control<?= adminErro($erros, 'descricao') !== null ? ' is-invalid' : '' ?>" id="descricao" name="descricao" rows="3"><?= e(adminValor($valores, 'descricao')) ?></textarea>
        <?php if (adminErro($erros, 'descricao') !== null) :
            ?><div class="invalid-feedback"><?= e((string) adminErro($erros, 'descricao')) ?></div><?php
        endif; ?>
      </div>
    </div>
    <button class="btn btn-primary mt-3" type="submit">Salvar serviço</button>
  </form>
</div>
<div class="card p-3 p-md-4">
  <h2 class="h5">Catálogo</h2>
  <?php if ($servicos === []) : ?>
    <p class="text-secondary mb-0">Nenhum serviço cadastrado.</p>
  <?php else : ?>
    <div class="table-responsive">
      <table class="table align-middle"><thead><tr><th>Nome</th><th>Categoria</th><th>Duração</th><th>Status</th><th class="text-end">Ações</th></tr></thead><tbody>
      <?php foreach ($servicos as $servico) : ?>
        <tr><td><?= e((string) $servico['nome']) ?></td><td><?= e((string) $servico['categoria']) ?></td><td><?= e((string) $servico['duracao_min']) ?> min</td><td><?= (int) $servico['ativo'] === 1 ? 'Ativo' : 'Inativo' ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/admin/servicos.php?editar=<?= e((string) $servico['id']) ?>">Editar</a> <form class="d-inline" method="post" action="/admin/servicos.php"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="acao" value="alternar"><input type="hidden" name="id" value="<?= e((string) $servico['id']) ?>"><input type="hidden" name="ativo" value="<?= (int) $servico['ativo'] === 1 ? '0' : '1' ?>"><button class="btn btn-sm btn-outline-secondary" type="submit"><?= (int) $servico['ativo'] === 1 ? 'Desativar' : 'Ativar' ?></button></form> <form class="d-inline" method="post" action="/admin/servicos.php" onsubmit="return confirm('Excluir este serviço permanentemente?');"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= e((string) $servico['id']) ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button></form></td></tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
  <?php endif; ?>
</div>
<?php adminRodape();
