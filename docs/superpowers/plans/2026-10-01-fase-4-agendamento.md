# Fase 4: Motor e Fluxo de Agendamento — Plano de Implementação

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entregar o fluxo completo de agendamento online: cliente seleciona serviço/profissional/data/hora, grava agendamento como `pendente`, e recebe link WhatsApp para confirmação.

**Architecture:** Camadas: (1) lógica core de slots em PHP puro, (2) calendário vanilla JS + CSS, (3) endpoint JSON com rate limit, (4) página de agendamento com validação server-side, (5) confirmação via WhatsApp.

**Tech Stack:** PHP 8.2 + PDO, MySQL/MariaDB, JavaScript vanilla, HTML5, CSS (identidade roxo/rosa).

**Spec:** `docs/PLANO_MESTRE_ANAREIKI.md` (Fase 4, §3.2), `docs/ARCHITECTURE.md` (§7), `docs/API.md` (§2.1–2.2).

## Global Constraints

- PHP 8.2 + PDO prepared statements em toda query, sem exceção
- UUIDs v7 em CHAR(36), gerados via `gerarUuid()` no PHP
- Rate limit: máx. 10 req/min por IP em `api/slots.php` (tabela `limites_taxa`)
- Dados do cliente: nome + telefone persistidos; email coletado temporariamente mas NÃO gravado (← NULL)
- Token CSRF obrigatório em todo POST
- Cada arquivo-fonte até 350 linhas (`npm run check:lines`)
- PHPUnit + PHPCS + PHPStan + ESLint: 100% verde antes de cada commit
- Jose executa todos os comandos git; Claude entrega os comandos

## Review Focus

1. **Overbooking por corrida:** dois clientes submetem o mesmo `(profissional_id, data, hora_inicio)` simultaneamente → índice único deve capturar e retornar 409; teste em AgendamentoTest
2. **Rate limit evasão:** cliente consulta `api/slots.php` > 10 vezes/min → deve retornar 429; teste em AgendamentoTest
3. **CSRF bypass:** POST `agendar.php` sem token válido → deve rejeitar com 403; teste em AgendamentoTest
4. **Horário fora da faixa:** cliente submete hora que não cai em nenhuma faixa de `disponibilidade` → server revalida e rejeita (400); teste em SlotsTest/AgendamentoTest
5. **Mobile responsivo:** calendário vanilla JS em 360px deve funcionar sem scroll horizontal, botões clicáveis; teste manual

---

## File Structure

```
Criar:
├── public_html/agendar.php                       # Página pública: calendário + form
├── public_html/confirmacao-agendamento.php        # Confirmação + link WhatsApp
├── public_html/api/slots.php                      # GET JSON: horários livres + rate limit
├── public_html/api/profissionais.php              # GET JSON: profissionais por serviço
├── public_html/static/calendar.js                 # Calendário vanilla JS
├── public_html/static/calendar.css                # Estilos calendário (roxo/rosa, responsivo)
├── public_html/static/agendar.js                  # Lógica AJAX da página de agendamento
├── public_html/includes/slots.php                 # Geração de slots + validação de conflito
├── tests/SlotsTest.php                            # Testes de geração de slots
├── tests/AgendamentoTest.php                      # Testes de agendamento (POST, validação)

Modificar:
├── public_html/includes/repositories.php          # Add: queries de disponibilidade e agendamento
├── config.php                                     # Add: WHATSAPP_PHONE
├── docs/ARCHITECTURE.md                           # Atualizar §7
├── docs/API.md                                    # Confirmar §2.1–2.2
├── docs/HANDOFF.md                                # Encerramento Fase 4
```

---

### Task 1: Lógica de Geração de Slots (Core)

**Files:**
- Create: `public_html/includes/slots.php`
- Modify: `public_html/includes/repositories.php`
- Test: `tests/SlotsTest.php`

**Interfaces:**
- Consumes: `db.php` (PDO), `repositories.php` (queries)
- Produces:
  - `obterSlotsDisponiveis(PDO $pdo, string $profissionalId, string $data, int $duracaoMin): array` — retorna `["09:00","10:00",...]` ou `[]`
  - `obterDisponibilidadeDia(PDO $pdo, string $profissionalId, int $diaSemana): array` — retorna faixas `[["hora_inicio"=>"09:00","hora_fim"=>"18:00"],...]`
  - `obterAgendamentosDia(PDO $pdo, string $profissionalId, string $data): array` — retorna agendamentos ativos da data

- [ ] Step 1: Write failing tests in `tests/SlotsTest.php`

```php
public function test_gerar_slots_sem_conflito(): void
// Ana, sábado 09:00–18:00, duração 60 min, sem agendamentos
// Esperado: 9 slots ["09:00","10:00",...,"17:00"]

public function test_gerar_slots_com_conflito(): void
// Pre-insert agendamento 10:00–11:00
// Esperado: 10:00 removido da lista

public function test_data_no_passado_retorna_vazio(): void
// Data ontem → retorna []

public function test_sem_disponibilidade_retorna_vazio(): void
// Dia da semana sem faixa (ex: quinta=4) → retorna []

public function test_multiplos_conflitos(): void
// Pre-insert 2 agendamentos → ambos removidos

public function test_duracao_diferente_gera_passos_corretos(): void
// Duração 30 min → passos de meia hora
```

- [ ] Step 2: Run tests — all FAIL (function not defined)
- [ ] Step 3: Add `obterDisponibilidadeDia()` and `obterAgendamentosDia()` to `repositories.php`
- [ ] Step 4: Implement `obterSlotsDisponiveis()` in `includes/slots.php`
- [ ] Step 5: Run tests — all PASS
- [ ] Step 6: Gates: `php -l`, `composer stan`, `composer cs`, `npm run check:lines`
- [ ] Step 7: Commit — `feat: implementar geração de slots com detecção de conflito`

---

### Task 2: Endpoint JSON `api/slots.php` + `api/profissionais.php`

**Files:**
- Create: `public_html/api/slots.php`
- Create: `public_html/api/profissionais.php`
- Modify: `public_html/includes/repositories.php` (add `obterProfissionaisPorServico`)

**Interfaces:**
- Consumes: `includes/slots.php`, `includes/limites.php`, `includes/db.php`, `includes/repositories.php`
- Produces:
  - GET `api/slots.php?servico=<uuid>&profissional=<uuid>&data=<YYYY-MM-DD>` → JSON `{servico_id, profissional_id, data, duracao_min, horarios}`
  - GET `api/profissionais.php?servico=<uuid>` → JSON `[{id, nome}, ...]`
  - 400 (parâmetro inválido), 429 (rate limit)

- [ ] Step 1: Write failing tests for slots endpoint and profissionais endpoint
- [ ] Step 2: Add `obterProfissionaisPorServico()` to `repositories.php`
- [ ] Step 3: Implement `api/slots.php` (validate, rate limit, call `obterSlotsDisponiveis`, return JSON)
- [ ] Step 4: Implement `api/profissionais.php` (validate UUID, query, return JSON)
- [ ] Step 5: Run tests — PASS
- [ ] Step 6: Gates
- [ ] Step 7: Commit — `feat: endpoints JSON para slots e profissionais`

---

### Task 3: Calendário Vanilla JS + CSS

**Files:**
- Create: `public_html/static/calendar.js`
- Create: `public_html/static/calendar.css`

**Interfaces:**
- Consumes: nenhuma (JS puro)
- Produces:
  - Classe `Calendar(containerId, options)` com `render()`, `getSelectedDate()`, `setMinDate()`, `setMaxDate()`
  - Custom event `calendarDateSelected` com `detail.date` (YYYY-MM-DD)

- [ ] Step 1: Create `calendar.css` — grid 7 colunas, roxo/rosa, responsivo 360px+, passado desativado
- [ ] Step 2: Create `calendar.js` — constructor, render, navegação mês, clique em data, custom event
- [ ] Step 3: Gates: `npm run lint`, `npm run check:lines`
- [ ] Step 4: Commit — `feat: calendário vanilla JS com estilos roxo/rosa`

---

### Task 4: Página `agendar.php` + AJAX + POST Handler

**Files:**
- Create: `public_html/agendar.php`
- Create: `public_html/static/agendar.js`
- Create: `public_html/confirmacao-agendamento.php`
- Modify: `public_html/includes/repositories.php` (add `criarAgendamento`, `obterAgendamento`)
- Modify: `config.php` (add `WHATSAPP_PHONE`)
- Test: `tests/AgendamentoTest.php`

**Interfaces:**
- Consumes: Task 1 (`includes/slots.php`), Task 2 (`api/slots.php`), Task 3 (calendar.js), `includes/csrf.php`, `includes/repositories.php`
- Produces:
  - GET `agendar.php` → HTML com dropdowns + calendário + form
  - POST `agendar.php?action=agendar` → valida, grava, redireciona (303)
  - GET `confirmacao-agendamento.php?id=<uuid>` → dados + link WhatsApp
  - `criarAgendamento(PDO, servico_id, profissional_id, data, hora_inicio, cliente_nome, cliente_telefone): string` (retorna id)

- [ ] Step 1: Add `criarAgendamento()` and `obterAgendamento()` to `repositories.php`
- [ ] Step 2: Write failing tests in `tests/AgendamentoTest.php`

```php
public function test_post_valido_grava_pendente(): void
public function test_csrf_invalido_retorna_403(): void
public function test_horario_ocupado_retorna_409(): void
public function test_profissional_nao_faz_servico_retorna_400(): void
public function test_email_nao_gravado_no_banco(): void
public function test_data_passado_retorna_400(): void
```

- [ ] Step 3: Create `agendar.php` — shell HTML com dropdowns (serviços ativos), calendário, placeholder para horários e form
- [ ] Step 4: Create `agendar.js` — AJAX (mudança serviço → fetch profissionais, clique data → fetch slots, clique hora → exibir form)
- [ ] Step 5: Implement POST handler em `agendar.php` — CSRF, validação, revalidação slot, gravação, redirect 303
- [ ] Step 6: Create `confirmacao-agendamento.php` — fetch agendamento, montar mensagem WhatsApp, exibir botão
- [ ] Step 7: Run tests — PASS
- [ ] Step 8: Gates: `php -l`, `composer stan`, `composer cs`, `npm run lint`, `npm run check:lines`
- [ ] Step 9: Commit — `feat: página de agendamento com confirmação WhatsApp`

---

### Task 5: Testes Finais + Gates + Documentação

**Files:**
- Modify: `docs/ARCHITECTURE.md`, `docs/API.md`, `docs/HANDOFF.md`

- [ ] Step 1: Run full PHPUnit suite (`composer test`) — all PASS
- [ ] Step 2: Run all code gates (`composer stan`, `composer cs`, `npm run lint`, `npm run check:lines`, `git diff --check`)
- [ ] Step 3: Manual test desktop (1024px) — fluxo completo agendar → confirmação → WhatsApp
- [ ] Step 4: Manual test mobile (360px, 375px, 390px, 412px) — calendário responsivo, botões clicáveis
- [ ] Step 5: Manual test edge cases — data passada, CSRF inválido, overbooking, rate limit
- [ ] Step 6: Update `docs/ARCHITECTURE.md` §7 — confirmar fluxo implementado
- [ ] Step 7: Update `docs/API.md` §2.1–2.2 — confirmar endpoints com exemplos reais
- [ ] Step 8: Update `docs/HANDOFF.md` — encerramento Fase 4
- [ ] Step 9: Commit — `docs: sincronizar documentação com Fase 4`
- [ ] Step 10: Entregar lista de testes manuais ao Jose + comandos git para commit/merge/push
