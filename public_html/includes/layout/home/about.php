<?php $profissionais = $profissionais ?? []; ?>
<section id="sobre" class="sobre-section">
  <div class="container sobre-grid">
    <div class="sobre-image-wrap">
      <div class="sobre-img-placeholder">
        <img src="https://www.genspark.ai/api/files/s/PHPB75Pg"
             alt="Espaço de atendimento Reiki Ana" width="640" height="800" loading="lazy">
      </div>
      <div class="sobre-badge-float">
        <i class="fas fa-spa" aria-hidden="true"></i>
        <span>Ambiente acolhedor</span>
      </div>
    </div>
    <div class="sobre-content">
      <p class="section-eyebrow">Quem somos</p>
      <h2 class="section-title">Conheça nossas <span class="script">profissionais</span></h2>
      <div class="perfis-profissionais">
        <?php if ($profissionais === []) : ?>
          <p class="sobre-text" role="status">As informações das profissionais estarão disponíveis em breve.</p>
        <?php else : ?>
            <?php foreach ($profissionais as $profissional) : ?>
                <?php
                $nome = (string) ($profissional['nome'] ?? '');
                $especialidade = trim((string) ($profissional['especialidade'] ?? ''));
                $bio = trim((string) ($profissional['bio'] ?? ''));
                $foto = urlFotoProfissional(isset($profissional['foto_url'])
                    ? (string) $profissional['foto_url'] : null);
                ?>
            <article class="sobre-profissional">
                <?php if ($foto !== null) : ?>
                <img class="sobre-profissional-foto" src="<?= htmlPublico($foto) ?>"
                     alt="<?= htmlPublico($nome) ?>" width="96" height="96" loading="lazy">
                <?php endif; ?>
              <h3><?= htmlPublico($nome) ?></h3>
                <?php if ($especialidade !== '') : ?>
                <p class="sobre-text"><?= htmlPublico($especialidade) ?></p>
                <?php endif; ?>
                <?php if ($bio !== '') : ?>
                <p class="sobre-text"><?= htmlPublico($bio) ?></p>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div class="sobre-dicas">
        <h4><i class="fas fa-lightbulb" aria-hidden="true"></i> Dicas para antes da sua massagem</h4>
        <ul>
          <li><i class="fas fa-check" aria-hidden="true"></i> Faça uma refeição leve</li>
          <li><i class="fas fa-check" aria-hidden="true"></i> Vista-se com roupa fácil de tirar e vestir</li>
          <li><i class="fas fa-check" aria-hidden="true"></i> Vá ao banheiro antes da sessão</li>
          <li><i class="fas fa-check" aria-hidden="true"></i> Desligue o celular</li>
          <li><i class="fas fa-check" aria-hidden="true"></i> Esteja pronto para relaxar!</li>
        </ul>
      </div>
      <a href="https://wa.me/5548996137757" target="_blank" rel="noopener noreferrer" class="btn-primary">
        <i class="fab fa-whatsapp" aria-hidden="true"></i> Me chama para agendar!
      </a>
    </div>
  </div>
</section>
