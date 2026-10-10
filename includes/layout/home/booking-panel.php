<?php

declare(strict_types=1);

?>
<aside class="booking-panel" id="booking-panel" aria-labelledby="booking-title">
  <button class="booking-trigger" id="booking-toggle" type="button"
          aria-controls="booking-content" aria-expanded="false">
    <i class="fas fa-calendar-check" aria-hidden="true"></i>
    <span>Agendar</span>
    <span class="booking-count" id="booking-count">0</span>
  </button>

  <form class="booking-content" id="booking-content" method="post" action="/agendar.php">
    <div class="booking-scroll">
      <?= csrfCampo() ?>
      <div class="booking-heading">
      <div>
        <p class="section-eyebrow">Seu momento</p>
        <h2 id="booking-title">Agende sua <span class="script">sessão</span></h2>
      </div>
      <button class="booking-close" id="booking-close" type="button" aria-label="Fechar agendamento">
        <i class="fas fa-xmark" aria-hidden="true"></i>
      </button>
    </div>

    <div class="booking-summary" id="booking-summary" aria-live="polite"></div>
    <div id="booking-service-ids"></div>

    <section class="booking-section" aria-labelledby="booking-professionals-title">
      <h3 id="booking-professionals-title">Profissional</h3>
      <p class="booking-help" id="booking-professionals-help">Adicione um serviço para ver profissionais.</p>
      <div class="booking-professionals" id="booking-professionals" role="radiogroup"
           aria-label="Escolha o profissional"></div>
      <input type="hidden" id="profissional_id" name="profissional_id">
    </section>

    <details class="booking-accordion" id="booking-date-time">
      <summary>Data e horário <span id="booking-date-status">Selecione um profissional</span></summary>
      <div class="booking-date-time-grid">
        <div>
          <div id="calendar-container" class="cal"></div>
          <p class="booking-help" id="calendar-status" aria-live="polite"></p>
          <ul class="booking-legend" aria-label="Legenda de disponibilidade">
            <li><span class="booking-legend-color is-available"></span>Disponível</li>
            <li><span class="booking-legend-color is-selected"></span>Selecionado</li>
            <li><span class="booking-legend-color is-unavailable"></span>Indisponível ou agendado</li>
          </ul>
          <input type="hidden" id="data" name="data">
        </div>
        <section class="booking-slots" aria-labelledby="booking-slots-title">
          <h3 id="booking-slots-title">Horários</h3>
          <p class="booking-help" id="booking-slots-date">Escolha uma data disponível.</p>
          <div id="slots-container" class="slots-grid" aria-live="polite"></div>
          <p class="booking-help" id="slots-loading" hidden>Carregando horários…</p>
          <p class="booking-help" id="slots-vazio" hidden>Nenhum horário disponível nesta data.</p>
          <input type="hidden" id="hora_inicio" name="hora_inicio">
        </section>
      </div>
    </details>

    <details class="booking-accordion" id="booking-details">
      <summary>Seus dados <span id="booking-details-status">Selecione um horário</span></summary>
      <div class="booking-fields">
        <label for="cliente_nome">Seu nome
          <input id="cliente_nome" name="cliente_nome" type="text" required minlength="2" maxlength="100"
                 autocomplete="name" placeholder="Nome completo">
        </label>
        <label for="cliente_telefone">Seu WhatsApp
          <input id="cliente_telefone" name="cliente_telefone" type="tel" required pattern="\d{10,15}"
                 maxlength="15" inputmode="numeric" autocomplete="tel" placeholder="48996249817">
        </label>
      </div>
    </details>

      <button class="btn-primary booking-confirm" id="btnConfirmar" type="submit" disabled>
        <i class="fab fa-whatsapp" aria-hidden="true"></i> Confirmar agendamento
      </button>
      <p class="booking-help booking-submit-status" id="booking-submit-status" role="status" hidden></p>
    </div>
  </form>
</aside>
