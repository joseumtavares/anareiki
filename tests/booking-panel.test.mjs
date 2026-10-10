import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (file) => readFileSync(new URL(file, import.meta.url), 'utf8');

test('a home expõe CTAs de serviço para o painel, sem perder a rota de contingência', () => {
  const services = read('../includes/layout/home/services.php');

  assert.match(services, /data-booking-service/);
  assert.match(services, /href="\/agendar\.php"/);
});

test('o painel mantém calendário, horários e dados no mesmo formulário da home', () => {
  const panel = read('../includes/layout/home/booking-panel.php');

  assert.match(panel, /id="booking-panel"/);
  assert.match(panel, /class="booking-scroll"/);
  assert.match(panel, /id="calendar-container"/);
  assert.match(panel, /id="slots-container"/);
  assert.match(panel, /id="cliente_nome"/);
  assert.match(panel, /id="cliente_telefone"/);
});

test('o painel oferece abertura, fechamento por Escape e operação móvel em tela cheia', () => {
  const script = read('../static/booking-panel.js');
  const styles = read('../static/booking-panel.css');

  assert.match(script, /window\.BookingPanel/);
  assert.match(script, /event\.key === 'Escape'/);
  assert.match(script, /data-booking-service/);
  assert.match(styles, /@media \(max-width: 767px\)/);
  assert.match(styles, /booking-panel-open/);
});

test('a confirmação permanece na home e abre o WhatsApp retornado pelo servidor', () => {
  const panel = read('../includes/layout/home/booking-panel.php');
  const script = read('../static/booking-panel.js');

  assert.match(panel, /id="booking-submit-status"/);
  assert.match(script, /form\.addEventListener\('submit'/);
  assert.match(script, /event\.preventDefault\(\)/);
  assert.match(script, /new window\.FormData\(elements\.form\)/);
  assert.match(script, /window\.location\.assign\(data\.whatsapp_url\)/);
});

test('data e horários são empilhados e a rolagem fica protegida pelos cantos do painel', () => {
  const styles = read('../static/booking-panel.css');

  assert.match(styles, /\.booking-date-time-grid\s*\{\s*display:\s*block;/);
  assert.doesNotMatch(styles, /\.booking-slots\s*\{\s*border-left:/);
  assert.match(styles, /\.booking-scroll\s*\{/);
  assert.match(styles, /\.booking-content\s*\{[\s\S]*?overflow:\s*hidden;/);
});

test('a mensagem vazia verifica slots disponiveis', () => {
  const script = read('../static/booking-panel.js');

  assert.match(script, /var hasAvailableSlot = grades\.some/);
  assert.match(script, /elements\.slotsEmpty\.hidden = hasAvailableSlot;/);
});
