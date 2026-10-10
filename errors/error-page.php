<?php

declare(strict_types=1);

/**
 * @param array{
 *     status: int,
 *     codigo: string,
 *     titulo: string,
 *     mensagem: string,
 *     acao_primaria: array{rotulo: string, url: string},
 *     acao_secundaria: array{rotulo: string, url: string}|null
 * } $pagina
 */
function imprimirPaginaErro(array $pagina, ?string $requestId): void
{
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= htmlspecialchars($pagina['codigo'] . ' — Reiki Ana', ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="/static/errors.css">
</head>
<body>
  <main class="error-page" aria-labelledby="error-title">
    <section class="error-card">
      <p class="error-brand" aria-label="Reiki Ana Massoterapeuta">Reiki Ana <span>Massoterapeuta</span></p>
      <p class="error-code" aria-hidden="true"><?= htmlspecialchars($pagina['codigo'], ENT_QUOTES, 'UTF-8') ?></p>
      <h1 id="error-title"><?= htmlspecialchars($pagina['titulo'], ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="error-message"><?= htmlspecialchars($pagina['mensagem'], ENT_QUOTES, 'UTF-8') ?></p>
      <?php if ($requestId !== null) : ?>
        <p class="error-request-id">Código de atendimento: <code><?= $requestId ?></code></p>
      <?php endif; ?>
      <div class="error-actions">
        <a class="error-action error-action-primary"
           href="<?= htmlspecialchars($pagina['acao_primaria']['url'], ENT_QUOTES, 'UTF-8') ?>">
          <?= htmlspecialchars($pagina['acao_primaria']['rotulo'], ENT_QUOTES, 'UTF-8') ?>
        </a>
        <?php if ($pagina['acao_secundaria'] !== null) : ?>
          <a class="error-action error-action-secondary"
             href="<?= htmlspecialchars($pagina['acao_secundaria']['url'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($pagina['acao_secundaria']['rotulo'], ENT_QUOTES, 'UTF-8') ?>
          </a>
        <?php endif; ?>
      </div>
    </section>
  </main>
</body>
</html>
    <?php
}
