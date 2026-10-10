# Raiz Hostinger e Agendamento Múltiplo Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Publicar a aplicação PHP pela raiz do repositório e permitir uma reserva atômica com vários serviços, disponibilidade real e confirmação por WhatsApp.

**Architecture:** `agendamentos` continua como cabeçalho de reserva e `agendamento_servicos` guarda seus itens imutáveis. O motor de slots existente recebe a duração total confiável; o frontend apenas apresenta os dados e solicita operações. A retirada de `public_html/` é uma etapa mecânica isolada, seguida de verificação completa de caminhos e deploy.

**Tech Stack:** PHP 8.2 + PDO + MySQL/MariaDB, PHPUnit, HTML/CSS/JavaScript vanilla, Apache `.htaccess`, Hostinger Git deployment.

**Spec:** `docs/superpowers/specs/2026-10-07-raiz-hostinger-agendamento-multiplo.md`

## Global Constraints

- Manter PHP + PDO + MySQL, HTML, CSS e JavaScript vanilla; não adotar Laravel nem dependências públicas novas.
- Usar prepared statements, UUID v7, CSRF, rate limit e validação server-side; o navegador nunca é fonte de preço, duração ou disponibilidade.
- Estender, sem duplicar, a disponibilidade semanal/por data, bloqueios e conflitos existentes.
- `config.php` é manual/ignorado e `.htaccess` deve bloquear credenciais e diretórios internos.
- CTAs públicos não navegam para `agendar.php` quando JavaScript está disponível; calendário, horários e dados ficam no painel da home.
- Migration nova e numerada; nunca editar migration aplicada ou remover `agendamentos.servico_id` nesta fase.
- Respeitar o limite de 350 linhas e executar `composer test`, `composer stan`, `composer cs`, `npm run lint`, `npm run check:lines` e `git diff --check` ao final aplicável.

## Review Focus

1. Intervalos sobrepostos de reservas simultâneas devem falhar dentro da transação — Task 3.
2. Serviço inativo, duplicado ou profissional parcialmente compatível não pode gerar slot ou reserva — Tasks 2 e 4.
3. Duração combinada não pode atravessar fechamento, bloqueio ou reserva — Task 3.
4. Agendamento histórico deve continuar legível após o backfill de itens — Tasks 1 e 6.
5. Após a migração da raiz, nenhum caminho interno ou referência física `public_html/` pode ficar acessível/quebrado — Tasks 7 e 8.
6. O painel móvel precisa abrir em tela cheia, ser fechável por botão/Escape e restaurar a rolagem sem perder o carrinho — Task 5A.

---

## File Structure

- `sql/migrations/005_agendamento_servicos.sql`: tabela e backfill de snapshots.
- `includes/agendamentos-repository.php`: serviços confiáveis, compatibilidade de profissional, reserva transacional e detalhes.
- `includes/slots.php`: slots pela duração total, usando a mesma fonte de disponibilidade.
- `api/profissionais.php`, `api/slots.php`, `api/disponibilidade.php`: contratos públicos do carrinho/calendário.
- `includes/layout/home/booking-panel.php`, `index.php`, `includes/layout/home/head.php`, `includes/layout/home/services.php`: painel de agendamento embutido na home, CTAs que adicionam serviços e carregamento dos ativos.
- `static/booking-panel.js`, `static/booking-panel.css`, `static/calendar.*`: estado do carrinho, painel desktop/móvel, acordeões, cartões, calendário e slots; `agendar.php` continua apenas como contingência de links legados.
- `confirmacao-agendamento.php`, `admin/agendamentos.php`, includes de admin: snapshots de itens sem regressão de status/filtros.
- raiz (`index.php`, `.htaccess`, `admin/`, `api/`, `includes/`, `static/`, `assets/`, `errors/`, `uploads/`): árvore publicada pela Hostinger.

### Task 1: Migration de itens de reserva e compatibilidade histórica

**Files:** Create `sql/migrations/005_agendamento_servicos.sql`; modify/test `tests/AgendamentoTest.php`.

**Produces:** `agendamento_servicos(id, agendamento_id, servico_id, nome_servico, duracao_min, preco, ordem)` e um item para cada registro legado.

- [ ] Escrever testes que exigem backfill de uma reserva legada, preservação de `preco NULL` e unicidade `(agendamento_id, servico_id)`.
- [ ] Rodar `composer test -- --filter AgendamentoTest` e confirmar falha por tabela/itens ausentes.
- [ ] Criar a migration com o registro `005_agendamento_servicos` como primeira instrução, FKs/índices e `INSERT ... SELECT` de snapshots; não tocar em `001_schema_inicial.sql`.
- [ ] Aplicar no banco de teste e repetir o filtro: uma reserva legada deve ter exatamente um item.
- [ ] Commit: `feat: armazenar itens imutáveis de agendamento`.

### Task 2: Repositório de reserva múltipla e profissionais compatíveis

**Files:** Create `includes/agendamentos-repository.php`; modify `includes/repositories.php`, `tests/AgendamentoTest.php`.

**Produces:** `obterServicosAtivosPorIds(PDO $pdo, array $servicoIds): array`, `obterProfissionaisPorServicos(PDO $pdo, array $servicoIds): array`, `criarAgendamentoMultiplo(PDO $pdo, array $servicoIds, string $profissionalId, string $data, string $horaInicio, string $clienteNome, string $clienteTelefone): string`, `obterAgendamentoComItens(PDO $pdo, string $id): ?array`.

- [ ] Escrever testes para IDs repetidos normalizados, serviço inativo/inexistente, profissional que atende só parte do carrinho, total vindo do banco e ordem dos itens persistidos.
- [ ] Rodar o filtro e confirmar falha por interfaces ausentes.
- [ ] Implementar o repositório: normalizar IDs mantendo a primeira ocorrência, consultar serviços ativos com parâmetros, exigir vínculos para todos os serviços e gravar snapshots; incluir o arquivo via `repositories.php` para compatibilidade.
- [ ] Rodar `composer test -- --filter AgendamentoTest` até passar.
- [ ] Commit: `feat: adicionar repositório de reservas múltiplas`.

### Task 3: Duração total e confirmação transacional

**Files:** Modify `includes/slots.php`, `includes/agendamentos-repository.php`, `tests/SlotsTest.php`, `tests/DisponibilidadeDataTest.php`, `tests/AgendamentoTest.php`.

**Consumes:** serviços confiáveis da Task 2 e disponibilidade semanal/por data existente.

- [ ] Adicionar testes de 45 + 30 minutos perto do fechamento, atravessando reserva/bloqueio, arredondamento de 30/60 e duas reservas sobrepostas concorrentes.
- [ ] Rodar `composer test -- --filter "(SlotsTest|DisponibilidadeDataTest|AgendamentoTest)"` e confirmar falha.
- [ ] Manter `obterSlotsDisponiveis(PDO $pdo, string $profissionalId, string $data, int $duracaoTotalMin): array`; em `criarAgendamentoMultiplo`, abrir transação, travar o profissional com `FOR UPDATE` em MySQL, recalcular serviços/slots, inserir cabeçalho com primeiro `servico_id` por compatibilidade e inserir os itens.
- [ ] Rodar o filtro e `composer stan`; provar que só uma das reservas sobrepostas persiste.
- [ ] Commit: `feat: validar reservas múltiplas pela duração total`.

### Task 4: Endpoints para carrinho, calendário e slots

**Files:** Modify `api/profissionais.php`, `api/slots.php`, `tests/AgendamentoTest.php`; create `api/disponibilidade.php`.

**Produces:** `GET /api/profissionais.php?servicos[]=UUID`, `GET /api/slots.php?servicos[]=UUID&profissional=UUID&data=YYYY-MM-DD`, `GET /api/disponibilidade.php?servicos[]=UUID&profissional=UUID&mes=YYYY-MM`, retornando somente dados públicos.

- [ ] Escrever testes para lista vazia, UUID inválido, duplicidade, serviço inativo/incompatível, mês/data inválidos, dias com slot e ausência de dados de cliente.
- [ ] Rodar o filtro e confirmar falha pois o contrato atual usa somente `servico` singular.
- [ ] Implementar `servicos[]` como contrato canônico e aceitar `servico` singular temporariamente; aplicar o rate limit existente e retornar dias futuros do mês que tenham ao menos um slot.
- [ ] Rodar `composer test -- --filter AgendamentoTest` e `php -l` nos três endpoints.
- [ ] Commit: `feat: expor disponibilidade para múltiplos serviços`.

### Task 5: Carrinho, cartões, calendário destacado e rota de contingência

**Files:** Modify `agendar.php`, `static/agendar.js`, `static/agendar.css`, `static/calendar.js`, `static/calendar.css`; create/test `tests/agendar-multiplo.test.mjs`.

**Produces:** estado `{servicos, profissional, data, hora}` e `Calendar#setAvailableDates(string[]): void`.

- [ ] Criar testes Node para adicionar/remover sem duplicidade, totais, limpeza de data/hora ao trocar carrinho/profissional, habilitação do confirmar e `URLSearchParams` com vários IDs.
- [ ] Rodar `node --test tests/agendar-multiplo.test.mjs` e confirmar falha.
- [ ] Renderizar CTAs de adicionar, carrinho removível, cartões acessíveis e resumo aderente; carregar dias disponíveis antes de habilitar o calendário e slots após a data. Usar apenas os endpoints da Task 4.
- [ ] Atualizar o POST para `servico_ids[]`, CSRF, cliente e chamada exclusiva a `criarAgendamentoMultiplo`; ignorar preço/duração do form e devolver 409 para indisponibilidade revalidada.
- [ ] Rodar teste Node, filtro PHP, `npm run lint` e `npm run check:lines`; extrair módulo JS se algum arquivo exceder 350 linhas.
- [ ] Commit: `feat: permitir carrinho de serviços no agendamento`.

### Task 5A: Painel de agendamento aderente na home

**Files:** Create `includes/layout/home/booking-panel.php`, `static/booking-panel.js`, `static/booking-panel.css`, `tests/booking-panel.test.mjs`; modify `index.php`, `includes/csrf.php` integration in `index.php`, `includes/layout/home/head.php`, `includes/layout/home/services.php`, `static/calendar.js`, `static/calendar.css`, `tests/HomePageTest.php`.

**Consumes:** os contratos `GET /api/profissionais.php?servicos[]=UUID`, `GET /api/disponibilidade.php?...` e `GET /api/slots.php?...` da Task 4 e o POST confiável de `agendar.php` da Task 5.

**Produces:** `BookingPanel` no escopo de `static/booking-panel.js`, com estado `{services, professional, date, hour, calendar}` e métodos públicos `addService(service)`, `open()`, `close()` e `render()`; `Calendar#setAvailableDates(string[]): void` preservado.

- [ ] Escrever `tests/booking-panel.test.mjs` para provar que um CTA de card usa `data-booking-service`, não navega quando JavaScript está disponível, não duplica o item, abre/fecha com Escape, limpa data/hora quando muda carrinho ou profissional e monta `URLSearchParams` com todos os IDs. Em `HomePageTest`, exigir o partial, o gatilho compacto e o formulário dentro do painel.
- [ ] Rodar `node --test tests/booking-panel.test.mjs` e `composer test -- --filter HomePageTest`; confirmar falha antes da implementação.
- [ ] Iniciar sessão e incluir `csrf.php` em `index.php`; criar `booking-panel.php` com formulário POST para `/agendar.php`, CSRF, região de resumo, cartões de profissionais, dois acordeões e controles semânticos. No primeiro acordeão, organizar `#calendar-container` e `#slots-container` como seletor único: duas colunas em desktop, calendário antes dos horários em telas estreitas. Não duplicar regras PHP ou consultas de disponibilidade.
- [ ] Alterar `services.php` para manter o link de contingência a `/agendar.php`, mas expor `data-booking-service`, nome, duração e preço para cada CTA; registrar o painel e seus assets em `index.php`/`head.php` sem adicionar bibliotecas.
- [ ] Implementar `BookingPanel` isoladamente de `app.js`: interceptar CTAs, adicionar/remover serviços, buscar profissionais compatíveis, abrir os acordeões progressivamente, buscar dias/mês e slots/data, manter o resumo em tempo real e submeter somente os IDs/campos atuais. Em desktop o painel deve permanecer aderente à rolagem; no celular, um gatilho fixo abre o mesmo conteúdo em tela cheia, fecha por botão/Escape e restaura a rolagem do documento.
- [ ] Estilizar `booking-panel.css` com tokens já existentes, foco visível, estados de carregamento/erro e seleção não dependente só de cor; adicionar `prefers-reduced-motion`. Não copiar textos, cores ou imagens da referência.
- [ ] Rodar `node --test tests/booking-panel.test.mjs`, `composer test -- --filter HomePageTest`, `npm run lint`, `npm run check:lines`, `composer cs` e `php -l index.php`; conferir manualmente 1440, 1024, 768, 430, 390 e 360 px.
- [ ] Commit: `feat: agendar sem sair da página inicial`.

### Task 6: Confirmação, WhatsApp e painel com itens múltiplos

**Files:** Modify `confirmacao-agendamento.php`, `admin/agendamentos.php`, `includes/agendamentos-admin.php`, `includes/resumo-mensal-admin.php`, `tests/AgendamentoTest.php`, `tests/AgendamentoAdminTest.php`, `tests/ResumoMensalAdminTest.php`.

**Consumes:** `obterAgendamentoComItens()` da Task 2.

- [ ] Escrever testes de confirmação com dois itens/preço nulo, URL WhatsApp codificada, filtros/status admin e resumo mensal sem duplicar cabeçalhos por join de itens.
- [ ] Rodar os três filtros e confirmar falha pois as consultas ainda usam apenas `a.servico_id`.
- [ ] Ler snapshots dos itens, exibir duração/preço de cada um e término persistido; montar WhatsApp com o número configurado e codificação correta; agregar financeiro apenas dos itens concluídos com preço conhecido.
- [ ] Rodar os filtros e `php -l confirmacao-agendamento.php`/`php -l admin/agendamentos.php` até passar.
- [ ] Commit: `feat: exibir itens da reserva na confirmação e no painel`.

### Task 7: Remover galeria e mover a árvore pública à raiz

**Files:** Move com `git mv` todos os arquivos e diretórios de `public_html/` para a raiz; modify `index.php`, `includes/layout/home/availability-gallery.php`, `.htaccess`, `composer.json`, `eslint.config.js`, `scripts/check-source-lines.mjs`; modify/test `tests/HomePageTest.php`, `tests/ResponsiveNavigationTest.php`.

- [ ] Adicionar teste de que a home não contém a galeria e testes textuais de `.htaccess` para negar `config.php`, `includes`, `vendor`, `sql`, `tests`, `bin` e arquivos de ambiente.
- [ ] Rodar filtros de home/responsividade e confirmar falha.
- [ ] Mover arquivos rastreados com `git mv`; depois de validar os caminhos, mover separadamente os uploads não rastreados para `uploads/` sem os apagar. Remover somente o bloco “Nosso espaço / Galeria de Tratamentos” e CSS órfão, preservando horários/CTA; alterar `vendor-dir` para `vendor` e os ignores/listas de fonte.
- [ ] Rodar `composer test -- --filter "(HomePageTest|ResponsiveNavigationTest)"`, `Test-Path public_html` e busca de referências nos caminhos migrados.
- [ ] Commit: `refactor: publicar aplicação PHP pela raiz do repositório`.

### Task 8: Ferramentas, testes, documentação e deploy preparado

**Files:** Modify `tests/*.php`, `tests/*.mjs`, `bin/*.php`, `phpcs.xml`, `phpstan.neon`, `phpunit.xml` se necessário; modify `docs/ARCHITECTURE.md`, `docs/API.md`, `docs/RULES.md`, `docs/setup.md`, `docs/HANDOFF.md`, `docs/PLANO_MESTRE_ANAREIKI.md`.

- [ ] Atualizar paths físicos de testes/scripts de `../public_html/` para `../`, sem mudar expectativas de negócio alheias.
- [ ] Documentar árvore raiz, `config.php` bloqueado/manual, migration 005, contratos `servicos[]`, endpoint mensal e procedimento Hostinger: branch certa, Install Path vazio, backup de uploads, criação de config e migration única.
- [ ] Rodar `rg -n "public_html" --glob '!docs/superpowers/**' --glob '!node_modules/**' --glob '!dist/**' .` e resolver toda referência ativa.
- [ ] Rodar gates completos: `composer test`, `composer stan`, `composer cs`, `npm run lint`, `npm run check:lines`, `git diff --check`.
- [ ] Executar roteiro manual em 1024, 768, 430, 390, 375 e 360 px, incluindo bloqueio/colisão, admin, 2FA e WhatsApp; registrar evidências e pendências no handoff. Não fazer push/deploy sem autorização específica.
- [ ] Commit: `docs: atualizar operação para deploy Git na Hostinger`.

## Checkpoints

- [ ] Após Tasks 1–3: migration reproduzível, itens históricos preservados, duração e concorrência cobertas.
- [ ] Após Tasks 4–6: fluxo público/admin usam os mesmos itens e endpoints não vazam dados; CTAs da home abrem o painel sem navegação e o seletor de data/hora permanece nele.
- [ ] Após Tasks 7–8: raiz publicável, referências íntegras, gates aprovados e aceite manual documentado.

## Risks and Mitigations

| Risco | Impacto | Mitigação |
|---|---|---|
| Migration parcialmente aplicada | Alto | Registro primeiro, backup e conferência em phpMyAdmin. |
| Deploy sobrescrever uploads/config | Alto | Backup e checklist explícito; não versionar credenciais. |
| Corrida entre intervalos diferentes | Alto | Lock por profissional e revalidação na transação. |
| Quebra após mover raiz | Alto | Movimento isolado, busca de paths, suite completa e smoke tests. |
| Calendário gerar tráfego excessivo | Médio | Uma consulta mensal limitada, não uma consulta por dia. |
