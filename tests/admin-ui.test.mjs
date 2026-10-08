import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const script = readFileSync(new URL('../public_html/static/admin-ui.js', import.meta.url), 'utf8');

test('menu mobile alterna abertura e estado acessível', () => {
  const handlers = {};
  let open = false;
  const attributes = {};
  const menu = { classList: { toggle: () => { open = !open; return open; } } };
  const button = {
    getAttribute: () => '#admin-menu',
    setAttribute: (key, value) => { attributes[key] = value; },
  };
  vm.runInNewContext(script, {
    document: {
      addEventListener: (name, handler) => { handlers[name] = handler; },
      querySelector: () => menu,
    },
  });
  const event = { target: { closest: () => button } };
  handlers.click(event);
  assert.equal(open, true);
  assert.equal(attributes['aria-expanded'], 'true');
  handlers.click(event);
  assert.equal(open, false);
  assert.equal(attributes['aria-expanded'], 'false');
});

test('cancelar confirmação bloqueia envio e aceitar permite envio', () => {
  const handlers = {};
  let accept = false;
  let prevented = 0;
  vm.runInNewContext(script, {
    document: { addEventListener: (name, handler) => { handlers[name] = handler; } },
    window: { confirm: () => accept },
  });
  const event = {
    target: { getAttribute: () => 'Excluir imagem?' },
    preventDefault: () => { prevented++; },
  };
  handlers.submit(event);
  assert.equal(prevented, 1);
  accept = true;
  handlers.submit(event);
  assert.equal(prevented, 1);
});
