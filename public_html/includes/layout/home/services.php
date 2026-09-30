<?php

declare(strict_types=1);

$servicos = $servicos ?? [];
?>
<!-- SERVIÇOS -->
<section id="servicos" class="servicos-section">
  <div class="container">
    <div class="section-header">
      <p class="section-eyebrow">O que ofereço</p>
      <h2 class="section-title">Meus <span class="script">Serviços</span></h2>
      <p class="section-desc">Terapias e massagens pensadas para o seu bem-estar completo</p>
    </div>
    <div class="servicos-grid">
      <?php if ($servicos === []) : ?>
        <p class="section-desc" role="status">Os serviços estarão disponíveis em breve.</p>
      <?php else : ?>
          <?php foreach ($servicos as $servico) : ?>
                <?php
                $nome = (string) ($servico['nome'] ?? '');
                $descricao = (string) ($servico['descricao'] ?? '');
                $imagem = urlImagemServico(isset($servico['imagem_url'])
                ? (string) $servico['imagem_url'] : null);
                $icone = trim((string) ($servico['icone'] ?? ''));
                $cor = trim((string) ($servico['cor'] ?? ''));
                $tag = trim((string) ($servico['tag'] ?? ''));
                $preco = $servico['preco'] ?? null;
                $duracao = (int) ($servico['duracao_min'] ?? 0);
                $id = (string) ($servico['id'] ?? '');
                $ancorasLegadas = [
                'Massagem Relaxante' => 'massagem-relaxante',
                'Drenagem Linfática' => 'drenagem',
                'Pedras Quentes' => 'pedras',
                'Ventosaterapia' => 'ventosaterapia',
                'Reiki' => 'reiki',
                ];
                $ancora = $ancorasLegadas[$nome] ?? 'servico-' . $id;
                $classePlaceholder = 'servico-img-placeholder';
                if ($cor !== '') {
                    $classePlaceholder .= ' ' . $cor;
                }
                ?>
          <article id="<?= htmlPublico($ancora) ?>" class="servico-card">
            <div class="servico-img<?= $imagem === null ? ' ' . htmlPublico($classePlaceholder) : '' ?>">
                <?php if ($imagem !== null) : ?>
                <img src="<?= htmlPublico($imagem) ?>" alt="<?= htmlPublico($nome) ?>"
                     width="640" height="440" loading="lazy">
                <?php elseif ($icone !== '') : ?>
                <div class="placeholder-inner">
                  <i class="fas <?= htmlPublico($icone) ?>" aria-hidden="true"></i>
                </div>
                <?php endif; ?>
                <?php if ($tag !== '') : ?>
                <span class="servico-tag"><?= htmlPublico($tag) ?></span>
                <?php endif; ?>
            </div>
            <div class="servico-body">
              <h3><?= htmlPublico($nome) ?></h3>
              <p><?= htmlPublico($descricao) ?></p>
              <div class="servico-footer">
                <span class="servico-preco">
                  <?= $preco === null ? 'Consultar valor' : 'R$ ' . number_format((float) $preco, 2, ',', '.') ?>
                </span>
                <span class="servico-duracao">
                  <i class="fas fa-clock" aria-hidden="true"></i> <?= $duracao ?> min
                </span>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>
