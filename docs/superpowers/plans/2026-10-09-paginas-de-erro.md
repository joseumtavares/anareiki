# Páginas de erro humanizadas e manutenção Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entregar páginas de erro acolhedoras, seguras e responsivas para os erros HTTP relevantes, mais um modo de manutenção que preserve a disponibilidade administrativa e as respostas JSON.

**Architecture:** Um catálogo PHP central fornecerá o conteúdo de cada erro e renderizará um único template independente de banco, sessão e layout público. Arquivos mínimos em `errors/` preservarão o status HTTP para o Apache, enquanto o handler de exceções existente reutilizará o renderer para `500`. Um guard de manutenção, acionado por configuração, interromperá entradas públicas antes de consultas ao banco e escolherá HTML ou JSON conforme o consumidor.

**Tech Stack:** Apache `.htaccess`, PHP 8.2, PHPUnit, HTML semântico e CSS puro; sem dependências adicionais.

**Spec:** `docs/superpowers/specs/2026-10-09-paginas-de-erro.md`

## Global Constraints

- Não instalar bibliotecas, Bootstrap, jQuery, frameworks ou fontes externas para as páginas de erro.
- Não consultar banco, sessão ou serviços externos para renderizar uma página de erro.
- Nunca expor stack trace, SQL, caminho absoluto, credenciais ou dados pessoais em resposta pública.
- Preservar o redirecionamento de visitante não autenticado em `requireAdmin()` para `/admin/login.php`.
- Manter erros de validação, CSRF, conflito, método e rate limit nas telas/JSON atuais; eles não viram páginas HTML genéricas.
- Manter APIs como JSON, inclusive durante manutenção.
- Não executar `git add`, `git commit` ou `git push` sem autorização explícita do usuário.
- Verificar localmente no Apache disponível em `http://localhost:8080` antes de concluir.

## Review Focus

- O `request ID` pode conter HTML malicioso; a página `500` deve escapar o valor antes de renderizá-lo (Task 1).
- Visitar diretamente `/errors/500.php` ou `/errors/503.php` deve manter o status correto e não retornar `200` (Task 1).
- O template interno não pode ser acessado diretamente, nem criar recursão no `ErrorDocument` (Task 2).
- Com manutenção ativa, `index.php`, `agendar.php`, confirmação e todas as APIs devem ser bloqueadas antes de `db()`, mas `/admin/login.php` e as páginas administrativas não (Task 3).
- Uma API em manutenção precisa responder `503`, `Retry-After` e JSON válido, nunca o HTML da página de manutenção (Task 3).

---

## Estrutura de arquivos

| Arquivo | Responsabilidade após a implementação |
| --- | --- |
| `includes/errors.php` | Catálogo tipado de conteúdo, renderer único, logging e handler global de exceções. |
| `errors/error-page.php` | Template HTML interno, sem acesso a banco/sessão/layout e sem acesso direto por URL. |
| `errors/403.php`, `404.php`, `500.php`, `502.php`, `503.php`, `504.php` | Entradas finas que fixam o status e chamam o renderer. |
| `static/errors.css` | Identidade visual, responsividade, foco e contraste das páginas de erro. |
| `.htaccess` | Mapeia os seis `ErrorDocument`, protege o template e preserva regras de segurança atuais. |
| `includes/maintenance.php` | Determina o modo de manutenção e emite resposta HTML ou JSON `503`. |
| `config.example.php` e `config.php` local | Declaram `maintenance_mode` como configuração booleana desligada. |
| `index.php`, `agendar.php`, `confirmacao-agendamento.php` | Entradas HTML públicas protegidas pelo guard antes de acesso ao banco. |
| `api/profissionais.php`, `api/slots.php`, `api/disponibilidade.php` | Entradas JSON protegidas pelo mesmo guard. |
| `tests/ErrorHandlingTest.php`, `tests/ErrorPagesTest.php`, `tests/MaintenanceTest.php` | Cobrem conteúdo, status, proteção e contratos de manutenção. |
| `docs/setup.md`, `docs/ARCHITECTURE.md` | Explicam ativação da manutenção e a contingência hPanel para falhas de infraestrutura. |

### Task 1: Criar o catálogo, template e páginas estáticas de erro

**Files:**
- Create: `errors/error-page.php`
- Create: `errors/502.php`
- Create: `errors/503.php`
- Create: `errors/504.php`
- Create: `static/errors.css`
- Modify: `includes/errors.php`
- Modify: `errors/403.php`
- Modify: `errors/404.php`
- Modify: `errors/500.php`
- Modify: `tests/ErrorHandlingTest.php`
- Create: `tests/ErrorPagesTest.php`

**Interfaces:**
- Produces: `dadosPaginaErro(int $status): array{status:int, codigo:string, titulo:string, mensagem:string, acao_primaria:array{rotulo:string,url:string}, acao_secundaria:array{rotulo:string,url:string}|null}`.
- Produces: `renderizarPaginaErro(int $status, ?string $requestId = null): void`, que fixa `http_response_code($status)`, envia `Content-Type: text/html; charset=utf-8` e `Cache-Control: no-store, max-age=0` antes de incluir o template.
- Consumes: `gerarRequestId()` e `registrarErroAplicacao()` existentes; nenhum arquivo desta tarefa pode chamar `config()` ou `db()`.

- [ ] **Step 1: Escrever os testes RED do catálogo e do renderer**

Em `tests/ErrorHandlingTest.php`, substituir as mensagens genéricas pelas expectativas dos títulos e textos aprovados para `403`, `404`, `500`, `502`, `503` e `504`. Em `tests/ErrorPagesTest.php`, renderizar cada entrada com `ob_start()` e exigir um único `h1`, o status e a ação primária prevista; exigir que um request ID `<img src=x onerror=alert(1)>` apareça escapado na página `500`.

- [ ] **Step 2: Executar os testes focados para confirmar a falha**

Run: `vendor\bin\phpunit tests/ErrorHandlingTest.php tests/ErrorPagesTest.php`

Expected: FAIL porque o catálogo, três entradas e o template ainda não existem.

- [ ] **Step 3: Implementar o catálogo e o renderer em `includes/errors.php`**

Implementar `dadosPaginaErro()` apenas para os seis status aprovados; usar `500` como fallback para qualquer outro valor. Implementar `renderizarPaginaErro()` com cabeçalhos seguros e `htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8')` antes de disponibilizar o valor ao template. Alterar o handler de exceções para chamar essa função após registrar a exceção.

- [ ] **Step 4: Implementar o template e as seis entradas de status**

O template `errors/error-page.php` recebe somente `$pagina` e `$requestId` do renderer, produz `<main>` com um único `h1`, código visual secundário, texto e no máximo duas ações. Cada entrada `errors/{status}.php` requer `includes/errors.php` por caminho absoluto com `__DIR__` e chama `renderizarPaginaErro(<status>)`; `500.php` também lê o identificador previamente preparado pelo handler. Nenhuma entrada deve importar fontes, Bootstrap, Font Awesome ou scripts.

- [ ] **Step 5: Criar o CSS responsivo e acessível**

Criar `static/errors.css` usando as variáveis/cores já existentes da fundação, layout centralizado, botões com foco visível e media query para telas pequenas. O CSS não pode ter `@import` ou URLs remotas; os links de ação devem permanecer claramente distinguíveis e clicáveis por toque.

- [ ] **Step 6: Executar a cobertura de erro e as verificações de estilo**

Run: `vendor\bin\phpunit tests/ErrorHandlingTest.php tests/ErrorPagesTest.php; vendor\bin\phpstan analyse includes/errors.php errors --no-progress; npm.cmd run check:lines`

Expected: PASS; não há dependência externa, HTML não expõe request ID sem escape e as seis páginas renderizam o status correto.

### Task 2: Configurar o Apache sem abrir o template interno

**Files:**
- Modify: `.htaccess`
- Modify: `tests/ErrorPagesTest.php`

**Interfaces:**
- Consumes: entradas `errors/403.php`, `404.php`, `500.php`, `502.php`, `503.php` e `504.php` da Task 1.
- Produces: os seis mapeamentos `ErrorDocument <status> /errors/<status>.php` e uma regra de negação para `/errors/error-page.php`.

- [ ] **Step 1: Escrever os testes RED da configuração Apache**

Em `tests/ErrorPagesTest.php`, ler `.htaccess` e exigir exatamente os seis caminhos `ErrorDocument` aprovados, além de uma regra de rewrite que retorne `403` para `errors/error-page.php`. Exigir que as regras existentes que bloqueiam `config.php`, `includes`, `vendor`, `sql`, `tests`, `bin`, `docs` e `.git` continuem presentes.

- [ ] **Step 2: Executar o teste de configuração para confirmar a falha**

Run: `vendor\bin\phpunit tests/ErrorPagesTest.php --filter apache`

Expected: FAIL porque `502`, `503`, `504` e a proteção do template ainda não estão configurados.

- [ ] **Step 3: Atualizar `.htaccess` preservando a ordem de segurança**

Adicionar `ErrorDocument` para `502`, `503` e `504` junto aos existentes. Inserir, antes de qualquer regra permissiva futura, `RewriteRule ^errors/error-page\.php$ - [NC,F,L]`; não bloquear as seis entradas de erro, pois o Apache precisa incluí-las durante a resposta.

- [ ] **Step 4: Executar a verificação local dos caminhos HTTP**

Run: `Invoke-WebRequest -SkipHttpErrorCheck http://localhost:8080/caminho-que-nao-existe; Invoke-WebRequest -SkipHttpErrorCheck http://localhost:8080/includes/errors.php; Invoke-WebRequest -SkipHttpErrorCheck http://localhost:8080/errors/error-page.php`

Expected: respectivamente `404`, `403` e `403`, todos sem conteúdo PHP interno ou stack trace.

- [ ] **Step 5: Executar os testes de configuração**

Run: `vendor\bin\phpunit tests/ErrorPagesTest.php --filter apache; git diff --check`

Expected: PASS e nenhuma alteração de whitespace inválida.

### Task 3: Implementar manutenção para HTML e JSON sem afetar o painel

**Files:**
- Create: `includes/maintenance.php`
- Modify: `config.example.php`
- Modify: `config.php` local, sem registrar segredos no Git
- Modify: `index.php`
- Modify: `agendar.php`
- Modify: `confirmacao-agendamento.php`
- Modify: `api/profissionais.php`
- Modify: `api/slots.php`
- Modify: `api/disponibilidade.php`
- Create: `tests/MaintenanceTest.php`

**Interfaces:**
- Produces: `modoManutencaoAtivo(array $config): bool`.
- Produces: `dadosRespostaManutencao(bool $json): array{status:503, content_type:string, retry_after:int, corpo:?string}`; para JSON, `corpo` é `{"erro":"O site está em manutenção. Tente novamente em breve."}`.
- Produces: `interromperSeEmManutencao(bool $json = false): void`, que consulta `config()`, envia `Retry-After: 3600` e encerra com JSON ou `renderizarPaginaErro(503)` quando ativo.
- Consumes: `config()` de `includes/db.php` sem abrir conexão PDO e `renderizarPaginaErro()` da Task 1.

- [ ] **Step 1: Escrever os testes RED do contrato de manutenção**

Em `tests/MaintenanceTest.php`, exigir que `modoManutencaoAtivo(['maintenance_mode' => true])` seja verdadeiro e que valores ausentes, `false` e strings como `'true'` sejam falsos. Exigir que `dadosRespostaManutencao(true)` tenha status `503`, `application/json; charset=utf-8`, `retry_after=3600` e JSON sem HTML; exigir que a versão HTML não tenha corpo pré-renderizado. Ler as entradas públicas e exigir a chamada do guard antes da primeira ocorrência de `db()`; exigir sua ausência em todos os arquivos `admin/`.

- [ ] **Step 2: Executar o teste de manutenção para confirmar a falha**

Run: `vendor\bin\phpunit tests/MaintenanceTest.php`

Expected: FAIL porque o guard e a configuração ainda não existem.

- [ ] **Step 3: Implementar o módulo puro e o guard de encerramento**

Em `includes/maintenance.php`, implementar as duas funções puras e o guard. O guard deve retornar sem efeitos quando desligado. Quando ligado, deve definir `503`, `Retry-After: 3600` e `Cache-Control: no-store`; para JSON, enviar o corpo produzido pelo contrato e encerrar; para HTML, chamar `renderizarPaginaErro(503)` e encerrar. Não iniciar sessão, não abrir PDO e não capturar/ocultar exceções.

- [ ] **Step 4: Declarar e consumir a configuração sem afetar o admin**

Adicionar `'maintenance_mode' => false` próximo a `debug` em `config.example.php` e no `config.php` local. Em cada entrada pública, requerer `includes/maintenance.php` depois de carregar `db.php`, chamar `interromperSeEmManutencao(false)` antes de repositories/validação/`db()`, e chamar `interromperSeEmManutencao(true)` antes de qualquer resposta API. Não alterar arquivos sob `admin/`.

- [ ] **Step 5: Verificar contratos e o modo ativo em processo isolado**

Run: `vendor\bin\phpunit tests/MaintenanceTest.php tests/ErrorPagesTest.php; vendor\bin\phpstan analyse includes/maintenance.php index.php agendar.php confirmacao-agendamento.php api --no-progress`

Expected: PASS. Em uma cópia temporária não versionada de `config.php` com `maintenance_mode=true`, `Invoke-WebRequest -SkipHttpErrorCheck http://localhost:8080/` retorna `503` HTML e `Invoke-WebRequest -SkipHttpErrorCheck http://localhost:8080/api/profissionais.php` retorna `503` com JSON; restaurar imediatamente `false` após a inspeção.

### Task 4: Atualizar documentação e executar a regressão completa

**Files:**
- Modify: `docs/ARCHITECTURE.md`
- Modify: `docs/setup.md`
- Modify: `tests/ErrorPagesTest.php` somente se a verificação final identificar uma lacuna concreta

**Interfaces:**
- Consumes: páginas e guard das Tasks 1–3.
- Produces: instruções de operação para ativar/desativar manutenção e contingência no hPanel, além de evidência de regressão.

- [ ] **Step 1: Documentar a operação segura**

Em `docs/ARCHITECTURE.md`, registrar o fluxo Apache → erro local e a separação HTML/JSON da manutenção. Em `docs/setup.md`, documentar a troca temporária de `maintenance_mode`, o retorno obrigatório a `false` e a configuração opcional de conteúdo equivalente em **hPanel → Error Pages** para erros que a infraestrutura gerar antes da aplicação. Não incluir senhas, URLs privadas ou dados de produção.

- [ ] **Step 2: Criar a matriz de aceite manual**

Acrescentar a `tests/ErrorPagesTest.php` ou à documentação uma matriz com: URL inexistente (`404`), arquivo protegido (`403`), template interno (`403`), exceção simulada (`500`), cada entrada de `502`/`503`/`504`, manutenção HTML e manutenção JSON. Para cada linha, registrar status, conteúdo esperado, ausência de detalhe técnico e ação disponível.

- [ ] **Step 3: Executar a suíte, análises e teste local do Apache**

Run: `vendor\bin\phpunit; vendor\bin\phpstan analyse --no-progress; npm.cmd run lint; npm.cmd run check:lines; git diff --check`

Expected: PASS completo, sem erros de PHP/JavaScript, sem regressão de agendamento ou painel.

- [ ] **Step 4: Fazer o aceite visual responsivo**

Abrir localmente `403`, `404`, `500`, `502`, `503` e `504` em desktop e celular. Confirmar um único `h1`, legibilidade, foco de teclado, botões funcionais, ausência de Bootstrap/CDN e consistência com lilás/rosa do site. Com manutenção desligada, confirmar home, painel de agendamento, APIs e login administrativo normais.

## Cobertura da especificação

- Textos, seis status, template independente, acessibilidade e ausência de Bootstrap: Task 1.
- Mapeamentos Apache, proteção do template e preservação das regras atuais: Task 2.
- Flag de manutenção, `Retry-After`, HTML antes do banco, JSON de APIs e exceção para admin: Task 3.
- Orientação Hostinger/hPanel, matriz de aceite e regressão visual/técnica: Task 4.

Não há requisito da especificação sem tarefa correspondente.
