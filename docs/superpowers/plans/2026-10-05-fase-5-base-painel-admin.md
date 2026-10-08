# Fase 5 — Base compartilhada e navegação do painel Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entregar a primeira fatia da Fase 5: uma base autenticada e reutilizável para o painel administrativo, com navegação, breadcrumbs, alertas/flash, helpers de formulário e uma página inicial pronta para receber os quatro módulos.

**Architecture:** As páginas PHP em `public_html/admin/` continuarão responsáveis por autorização, parsing e renderização. O layout compartilhado em `public_html/includes/layout/admin.php` fornecerá estrutura visual e componentes de apresentação; mensagens flash e pequenos helpers de formulário ficarão em `public_html/includes/admin.php`, sem consultas SQL ou regras de negócio. O dashboard será uma página autenticada e sem consulta a dados de domínio nesta fatia.

**Tech Stack:** PHP 8.2+, PDO existente, sessões PHP, Bootstrap 5.3.3 via CDN, PHPUnit 11.

**Spec:** `docs/superpowers/specs/2026-10-05-fase-5-painel-admin.md`

## Global Constraints

- Manter páginas PHP em `public_html/admin/`, com um arquivo por módulo.
- Reutilizar `requireAdmin()`, `csrf_token()`, `validarCsrf()` e `adminTopo()/adminRodape()` (os nomes efetivamente existentes são `csrfToken()/csrfValido()`).
- Usar Bootstrap 5 e a identidade roxa existente; tabelas futuras devem degradar em telas estreitas.
- Toda escrita futura usará CSRF, validação server-side, prepared statements e Post/Redirect/Get; esta fatia não cria escrita de domínio.
- Nenhum novo papel além de `admin`; toda página autenticada chama `requireAdmin()` antes de renderizar.
- Não alterar schema, seed, fluxo público, branch de deploy ou arquivos fora da fatia.

## Review Focus

- Sessão ausente ao acessar `/admin/` deve redirecionar para o login por `requireAdmin()` — teste `AdminDashboardTest::test_dashboard_exige_admin_autenticado`.
- Conteúdo de título, nome do administrador, breadcrumb e mensagem deve ser escapado — testes `AdminLayoutTest::test_layout_escapa_conteudo_dinamico` e `AdminFlashTest::test_flash_e_consumido_uma_unica_vez`.
- Flash inválido ou tipo não permitido não pode virar classe HTML arbitrária — teste `AdminFlashTest::test_flash_normaliza_tipo_desconhecido`.
- Erros de campo e valores preservados devem funcionar quando o campo não existe no payload — testes `AdminFormTest::test_helper_de_valor_usa_fallback` e `AdminFormTest::test_helper_de_erro_nao_emite_markup_sem_erro`.
- Navegação não deve aparecer no login, mas todos os destinos da fase devem aparecer no painel autenticado — testes `AdminLayoutTest::test_login_nao_renderiza_menu` e `AdminLayoutTest::test_menu_do_painel_exibe_modulos`.

### Task 1: Extrair contratos compartilhados do admin

**Files:**
- Create: `public_html/includes/admin.php`
- Create: `tests/AdminFlashTest.php`
- Create: `tests/AdminFormTest.php`

**Interfaces:**
- Produces `adminFlash(string $mensagem, string $tipo = 'success'): void`, que grava uma mensagem na sessão ativa.
- Produces `consumirAdminFlash(): ?array`, que retorna `['mensagem' => string, 'tipo' => string]` e remove a mensagem; retorna `null` quando não há flash.
- Produces `adminValor(array $valores, string $campo, string $fallback = ''): string`, sempre retornando texto escapável sem acessar índice ausente.
- Produces `adminErro(array $erros, string $campo): ?string`, retornando `null` quando não há erro.
- O conjunto permitido de tipos de flash é `success`, `info`, `warning`, `danger`; qualquer outro tipo é normalizado para `info`.

- [ ] **Step 1: Write the failing tests**

  Em `AdminFlashTest`, iniciar sessão isolada e cobrir gravação/consumo único, ausência de flash e normalização do tipo. Em `AdminFormTest`, cobrir fallback de valor, preservação de string enviada e ausência de markup/efeito colateral em `adminErro` (o helper apenas devolve texto ou `null`).

- [ ] **Step 2: Run tests to verify they fail**

  Run: `composer.bat test -- --filter "Admin(Flash|Form)Test"`
  Expected: FAIL porque `public_html/includes/admin.php` e os contratos ainda não existem.

- [ ] **Step 3: Implement the helpers in `public_html/includes/admin.php`**

  Exigir sessão ativa antes de ler/gravar flash; usar chaves de sessão namespaced (`admin_flash`). Não escapar no armazenamento nem duas vezes no helper: a saída será escapada pelo layout com `e()`.

- [ ] **Step 4: Run the focused tests**

  Run: `composer.bat test -- --filter "Admin(Flash|Form)Test"`
  Expected: PASS.

- [ ] **Step 5: Commit**

  ```bash
  git add public_html/includes/admin.php tests/AdminFlashTest.php tests/AdminFormTest.php
  git commit -m "feat: add shared admin flash and form helpers"
  ```

### Task 2: Consolidar layout, menu e componentes de navegação

**Files:**
- Modify: `public_html/includes/layout/admin.php`
- Create: `tests/AdminLayoutTest.php`

**Interfaces:**
- `adminTopo(string $titulo, bool $painel = false): void` mantém o HTML base atual; quando `$painel` for verdadeiro, renderiza cabeçalho autenticado, menu para `/admin/`, `servicos.php`, `profissionais.php`, `disponibilidade.php` e `agendamentos.php`, além do nome do admin e logout com CSRF.
- `adminBreadcrumb(array $itens): void` recebe pares `['rotulo' => string, 'url' => ?string]` e renderiza breadcrumb acessível; o último item não é link.
- `adminAlerta(?string $mensagem, string $tipo = 'danger'): void` conserva escape e semântica ARIA atuais, normalizando tipos pela mesma allowlist do flash.
- `adminRodape(): void` continua fechando o documento e carregando `admin-auth.js`.

- [ ] **Step 1: Write the failing tests**

  Capturar a saída de `adminTopo()` com e sem painel, verificando ausência/presença do menu, todos os cinco destinos, escape do título/nome e logout com token. Testar breadcrumb com último item não clicável e `adminAlerta()` com tipo inválido.

- [ ] **Step 2: Run tests to verify they fail**

  Run: `composer.bat test -- --filter AdminLayoutTest`
  Expected: FAIL porque as assinaturas e o menu compartilhado ainda não existem.

- [ ] **Step 3: Implement the layout changes**

  Incluir `admin.php` no layout compartilhado, obter o admin da sessão apenas quando `$painel` for verdadeiro e manter o login/2FA sem navegação. Reutilizar `e()`, `csrfCampo()` e `adminAlerta()`; não duplicar autorização.

- [ ] **Step 4: Run the focused tests**

  Run: `composer.bat test -- --filter AdminLayoutTest`
  Expected: PASS.

- [ ] **Step 5: Commit**

  ```bash
  git add public_html/includes/layout/admin.php tests/AdminLayoutTest.php
  git commit -m "feat: add authenticated admin navigation layout"
  ```

### Task 3: Transformar `/admin/` em dashboard inicial da fase

**Files:**
- Modify: `public_html/admin/index.php`
- Create: `tests/AdminDashboardTest.php`

**Interfaces:**
- O dashboard chama `requireAdmin()` antes de qualquer saída, inclui `public_html/includes/admin.php`, consome um flash pendente e chama `adminTopo('Painel', true)`.
- O conteúdo apresenta saudação escapada, breadcrumb “Painel”, quatro cards/links para os módulos e estado vazio informando que os dados da agenda serão exibidos nas fatias seguintes.
- POST de logout permanece no formulário existente com CSRF; não adicionar consultas nem dados sensíveis.

- [ ] **Step 1: Write the failing tests**

  Cobrir que a página inclui os quatro links, usa o nome autenticado escapado, exibe flash success uma vez e mantém o token no logout. O teste de sessão ausente deve simular `requireAdmin()` redirecionando (ou verificar o contrato por teste de integração já existente, sem conectar em MySQL).

- [ ] **Step 2: Run tests to verify they fail**

  Run: `composer.bat test -- --filter AdminDashboardTest`
  Expected: FAIL porque o dashboard ainda tem o placeholder da Fase 2 e não consome flash/menu.

- [ ] **Step 3: Implement the dashboard**

  Renderizar apenas dados da sessão e links fixos; escapar qualquer texto dinâmico; usar PRG apenas como contrato para mensagens já produzidas por ações futuras.

- [ ] **Step 4: Run the focused and full tests**

  Run: `composer.bat test -- --filter AdminDashboardTest`
  Expected: PASS.

  Run: `composer.bat test`
  Expected: PASS for the complete PHPUnit suite.

- [ ] **Step 5: Run static checks and diff validation**

  Run: `composer.bat cs`; `composer.bat stan`; `git diff --check`
  Expected: no PHPCS/PHPStan errors and no whitespace errors.

- [ ] **Step 6: Commit**

  ```bash
  git add public_html/admin/index.php tests/AdminDashboardTest.php
  git commit -m "feat: add phase five admin dashboard"
  ```

## Self-review

- Spec coverage for this plan is intentionally limited to Section 4, item 1 (base compartilhada e navegação) and the shared decisions in Sections 3.1/3.2; CRUD, availability, status transitions, schema and visual acceptance remain later plans.
- Every task has a failing test, implementation, focused verification and commit boundary.
- Contracts use the existing project names (`csrfToken`, `csrfValido`, `requireAdmin`) where the approved spec used conceptual aliases.
- No task introduces a repository query or a destructive operation.
