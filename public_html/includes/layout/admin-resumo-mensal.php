<?php

declare(strict_types=1);

/** @param array{servicos: list<array<string, mixed>>, quantidade: int, total_centavos: int, sem_preco: int}|null $resumo */
function renderResumoMensalAdmin(string $mes, ?array $resumo, ?string $erroResumo): void
{
    ?>
<link rel="stylesheet" href="/static/admin-resumo-mensal.css">
<section class="card p-4" aria-labelledby="titulo-resumo">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <h2 class="h5 mb-0" id="titulo-resumo">Serviços agendados no mês</h2>
    <form method="get" action="/admin/" class="d-flex flex-wrap align-items-end gap-2">
      <div>
        <label class="form-label" for="mes-resumo">Mês de referência</label>
        <input class="form-control" type="month" id="mes-resumo" name="mes" value="<?= e($mes) ?>" required>
      </div>
      <button class="btn btn-primary" type="submit">Atualizar</button>
    </form>
  </div>
    <?php adminAlerta($erroResumo); ?>
    <?php if ($resumo !== null) : ?>
  <p class="text-secondary">Período: <?= e($mes) ?>. Quantidades incluem pendentes, confirmados e concluídos;
    cancelados não entram no gráfico.</p>
  <div class="bg-light rounded p-3 mb-3">
    <p class="mb-1">Faturamento estimado dos atendimentos concluídos</p>
    <p class="h3 mb-0">R$ <?= e(number_format($resumo['total_centavos'] / 100, 2, ',', '.')) ?></p>
  </div>
  <p class="small text-secondary">Valores calculados pelo preço atual dos serviços e agrupados pelo mês do atendimento.
    Não representam pagamentos registrados. Alterações no preço do catálogo mudam esta estimativa.</p>
        <?php if ($resumo['sem_preco'] > 0) : ?>
  <p class="alert alert-warning"><?= e((string) $resumo['sem_preco']) ?> atendimento(s) concluído(s)
    sem preço definido não foram somados ao total.</p>
        <?php endif; ?>
        <?php if ($resumo['quantidade'] === 0) : ?>
  <p class="text-secondary mb-0">Nenhum agendamento não cancelado neste mês.</p>
        <?php else : ?>
  <div class="resumo-mensal-grade">
    <figure class="mb-0 text-center">
      <svg class="resumo-mensal-pizza" viewBox="0 0 200 200" role="img" aria-labelledby="titulo-pizza">
        <title id="titulo-pizza">Distribuição dos <?= e((string) $resumo['quantidade']) ?>
          agendamentos por serviço</title>
            <?php $acumulado = 0.0; ?>
            <?php foreach ($resumo['servicos'] as $indice => $servico) : ?>
                <?php
                $cor = 'hsl(' . (int) round($indice * 360 / count($resumo['servicos'])) . ', 55%, 42%)';
                $arco = (int) $servico['quantidade'] / $resumo['quantidade'] * 2 * M_PI * 50;
                ?>
        <circle cx="100" cy="100" r="50" fill="none" stroke="<?= e($cor) ?>" stroke-width="100"
                stroke-dasharray="<?= e((string) $arco) ?> <?= e((string) (2 * M_PI * 50)) ?>"
                stroke-dashoffset="<?= e((string) -$acumulado) ?>" transform="rotate(-90 100 100)">
          <title><?= e((string) $servico['nome']) ?>: <?= e((string) $servico['quantidade']) ?> agendamento(s)</title>
        </circle>
                <?php $acumulado += $arco; ?>
            <?php endforeach; ?>
      </svg>
      <figcaption class="small text-secondary mt-2">Fatias proporcionais à quantidade de agendamentos.</figcaption>
    </figure>
    <ul class="list-group list-group-flush resumo-mensal-legenda">
            <?php foreach ($resumo['servicos'] as $indice => $servico) : ?>
                <?php $cor = 'hsl(' . (int) round($indice * 360 / count($resumo['servicos'])) . ', 55%, 42%)'; ?>
      <li class="list-group-item px-0">
        <div class="d-flex align-items-center gap-2">
          <svg width="16" height="16" class="flex-shrink-0" aria-hidden="true">
            <rect width="16" height="16" rx="3" fill="<?= e($cor) ?>" />
          </svg>
          <strong><?= e((string) $servico['nome']) ?></strong>
        </div>
        <p class="small text-secondary my-1"><?= e((string) $servico['quantidade']) ?> agendamento(s) ·
                <?= e((string) $servico['concluidos']) ?> concluído(s)</p>
        <p class="mb-0">Total dos concluídos:
                <?php if ($servico['preco'] === null) : ?>
          <span>Consultar valor — não incluído no faturamento</span>
                <?php else : ?>
          <strong>R$ <?= e(number_format((int) $servico['total_centavos'] / 100, 2, ',', '.')) ?></strong>
                <?php endif; ?>
        </p>
      </li>
            <?php endforeach; ?>
    </ul>
  </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
    <?php
}
