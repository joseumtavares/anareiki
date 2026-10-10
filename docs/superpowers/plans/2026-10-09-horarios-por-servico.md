# Horários individuais por serviço Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permitir uma reserva única, para um profissional e uma data, com um horário validado individualmente para cada serviço.

**Architecture:** Os intervalos passam a pertencer aos itens de `agendamento_servicos`; os campos de horário do agendamento pai permanecem como intervalo mínimo/máximo de compatibilidade. Um motor PHP único gera slots, aplica reservas e bloqueios persistidos, inclui escolhas provisórias e valida a combinação final dentro da transação. O JavaScript apenas apresenta os estados retornados por esse motor e envia o mapa de horários ao endpoint atual.

**Tech Stack:** PHP 8.2, PDO/MySQL, SQL de migração, HTML, CSS puro e JavaScript vanilla.

**Spec:** `docs/superpowers/specs/2026-10-09-horarios-por-servico.md`

## Global Constraints

- Manter o mesmo profissional e a mesma data para todos os serviços de uma reserva.
- Não instalar dependências, bibliotecas, jQuery, Bootstrap ou frameworks.
- Preservar reservas legadas, APIs existentes e o painel administrativo.
- Validar IDs, duração, preço, disponibilidade e cada intervalo exclusivamente no PHP antes de gravar.
- Não expor nomes, telefones, serviços ou horários identificáveis de terceiros nos endpoints públicos.
- Não aplicar a migração no banco sem autorização explícita do usuário.
- Não executar `git commit`, `git push` ou stage sem autorização explícita, conforme `docs/RULES.md`.

## Review Focus

- Uma seleção em horários separados precisa ser aceita, mas dois intervalos que se cruzam precisam falhar antes de gravar (Task 2).
- Uma data com vagas independentes, mas sem combinação completa para todos os serviços, não pode ficar verde (Task 2 e Task 3).
- Reserva cancelada não bloqueia horário; pendente, confirmada e concluída bloqueiam sem revelar dados pessoais (Task 2 e Task 3).
- O estado provisório de um serviço deve atualizar as grades dos demais sem apagar seleções ainda válidas (Task 4).
- Repetir a confirmação, ou confirmar simultaneamente, não pode criar uma reserva duplicada ou parcial (Task 5).

---

## Estrutura de arquivos

| Arquivo | Responsabilidade após a implementação |
| --- | --- |
| `sql/migrations/006_horarios_por_servico.sql` | Evolui itens e preserva intervalos de reservas existentes. |
| `includes/agendamentos-repository.php` | Persiste, lê e valida intervalos individuais por item. |
| `includes/slots.php` | Fonte única para grade de horários, colisões e combinação completa. |
| `api/disponibilidade.php` | Disponibilidade mensal e indicador público de ocupação. |
| `api/slots.php` | Grade por serviço, incluindo estado de cada horário. |
| `agendar.php` | Revalida o mapa `horarios[servico_id]` e cria a reserva atômica. |
| `includes/whatsapp-agendamento.php` | Formata serviços com horários individuais. |
| `static/calendar.js` / `static/calendar.css` | Consome os estados do mês e expõe indicador de ocupação acessível. |
| `includes/layout/home/booking-panel.php` / `static/booking-panel.js` / `static/booking-panel.css` | Cartões de horário por serviço, legenda e resumo reativo. |
| `includes/agendamentos-admin.php` / `admin/agendamentos.php` | Exibe itens e intervalos reais no painel, sem N+1. |
| `tests/*.php` / `tests/booking-panel.test.mjs` | Cobertura de motor, persistência, contratos e interface. |

### Task 1: Migrar intervalos para os itens de agendamento

**Files:**
- Create: `sql/migrations/006_horarios_por_servico.sql`
- Modify: `tests/AgendamentoTest.php`
- Modify: `tests/WhatsappAgendamentoTest.php`

**Interfaces:**
- Produces: colunas não nulas `agendamento_servicos.hora_inicio` e `agendamento_servicos.hora_fim` no formato `TIME`.
- Consumes: schema da migração `005_agendamento_servicos.sql` e campos legados de `agendamentos`.

- [ ] **Step 1: Escrever os testes de migração e leitura de itens históricos**

Adicionar testes que exijam a migration `006`, as duas colunas de tempo e o backfill a partir do agendamento pai. Cobrir um item legado que passa a ter início `09:00` e fim `10:00`.

- [ ] **Step 2: Executar o teste focado para confirmar a falha**

Run: `vendor\bin\phpunit tests/AgendamentoTest.php --filter migracao`

Expected: FAIL pela ausência de `006_horarios_por_servico.sql`.

- [ ] **Step 3: Criar a migração compatível**

Adicionar primeiro o registro `006_horarios_por_servico` em `migracoes`, criar as colunas anuláveis, preencher cada item com os horários do pai, e então torná-las `NOT NULL`. Não alterar nem apagar a migração `005`.

- [ ] **Step 4: Executar o teste focado para confirmar o backfill**

Run: `vendor\bin\phpunit tests/AgendamentoTest.php --filter migracao`

Expected: PASS.

- [ ] **Step 5: Validar a migration em banco local somente quando houver autorização**

Executar o script com a conexão local e confirmar `SHOW COLUMNS FROM agendamento_servicos` e o registro `006` em `migracoes`. Não realizar esta etapa em banco compartilhado ou produção sem nova autorização.

### Task 2: Centralizar disponibilidade e persistência por serviço

**Files:**
- Modify: `includes/slots.php`
- Modify: `includes/agendamentos-repository.php`
- Modify: `includes/repositories.php`
- Modify: `tests/SlotsTest.php`
- Modify: `tests/AgendamentoTest.php`

**Interfaces:**
- Produces: `obterEstadosSlotsServico(PDO $pdo, string $profissionalId, string $data, int $duracaoMin, array $intervalosProvisorios = []): array`.
- Produces: `existeCombinacaoHorarios(PDO $pdo, string $profissionalId, string $data, array $servicos): bool`.
- Produces: `criarAgendamentoMultiplo(PDO $pdo, array $servicoIds, string $profissionalId, string $data, array $horariosPorServico, string $clienteNome, string $clienteTelefone): string`.
- Consumes: disponibilidade por data, faixas semanais, bloqueios e reservas ativas existentes.

- [ ] **Step 1: Escrever testes RED para a grade e a combinação**

Em `SlotsTest.php`, criar cenários com dois serviços de 30/60 minutos, horários livres separados e uma reserva intermediária. Exigir: combinação com `09:00` e `11:00` é válida; intervalos `09:00–10:00` e `09:30–10:00` não podem coexistir; reserva cancelada não bloqueia; reserva pendente bloqueia e retorna estado `agendado` sem dados do cliente.

- [ ] **Step 2: Executar os testes do motor para confirmar a falha**

Run: `vendor\bin\phpunit tests/SlotsTest.php tests/AgendamentoTest.php`

Expected: FAIL pela ausência de estados por slot, combinação e horários por item.

- [ ] **Step 3: Implementar o motor único de intervalos**

Em `includes/slots.php`, manter `obterSlotsDisponiveis()` como adaptador compatível que retorna somente inícios disponíveis. Implementar a grade rica como lista de `{inicio, fim, estado}`; `estado` será `disponivel`, `agendado` ou `indisponivel`. Somar às reservas persistidas os intervalos provisórios já escolhidos pelo visitante. Implementar `existeCombinacaoHorarios()` por busca com retrocesso, priorizando o serviço com menos slots válidos.

- [ ] **Step 4: Atualizar gravação e leitura de itens**

Alterar a validação e `criarAgendamentoMultiplo()` para exigir um horário para cada ID de serviço, calcular cada término pelo servidor e validar colisão entre todos os itens antes do `INSERT`. Gravar os horários em `agendamento_servicos` e os limites mínimo/máximo no pai. Atualizar `obterAgendamentoComItens()` para devolver `hora_inicio` e `hora_fim` dos itens; ao ler legado, usar o intervalo do pai como fallback.

- [ ] **Step 5: Executar os testes do motor e persistência**

Run: `vendor\bin\phpunit tests/SlotsTest.php tests/AgendamentoTest.php`

Expected: PASS, incluindo rollback integral em conflito.

### Task 3: Estender contratos públicos sem expor reservas

**Files:**
- Modify: `api/disponibilidade.php`
- Modify: `api/slots.php`
- Modify: `docs/API.md`
- Create: `tests/DisponibilidadeApiContractTest.php`

**Interfaces:**
- Produces: disponibilidade mensal `{mes, dias: string[], estados: Record<string, {disponivel: bool, possui_ocupacao: bool}>}`.
- Produces: grade `{servico_id, profissional_id, data, duracao_min, horarios: string[], slots: [{inicio, fim, estado}]}`.
- Consumes: interfaces da Task 2 e parâmetros públicos já validados.

- [ ] **Step 1: Escrever testes RED dos contratos JSON**

Cobrir `dias` como lista compatível e `estados` como extensão; exigir que uma data sem combinação completa fique fora de `dias`, mas um dia verde com uma reserva parcial tenha `possui_ocupacao=true`. Para slots, exigir uma grade com estado `agendado` e ausência de nome/telefone de cliente.

- [ ] **Step 2: Executar os testes de contrato para confirmar a falha**

Run: `vendor\bin\phpunit tests/DisponibilidadeApiContractTest.php`

Expected: FAIL pela ausência dos campos de estado e da grade.

- [ ] **Step 3: Atualizar os dois endpoints**

`api/disponibilidade.php` deve usar `existeCombinacaoHorarios()` para o verde e preservar a lista `dias`. `api/slots.php` deve aceitar `servico_id` como alvo, `selecoes[servico_id]=HH:MM` para os demais itens, validar que todas pertencem à seleção e devolver `slots`; manter `horarios` como a lista reduzida dos itens disponíveis para consumidores legados. Preservar rate limit, `400`, `429` e respostas sem PII.

- [ ] **Step 4: Documentar os contratos e erros**

Atualizar `docs/API.md` com os parâmetros, exemplos JSON, estados e a regra de que POST revalida tudo. Documentar `409` para combinação ocupada e `422` para mapa incompleto ou inválido.

- [ ] **Step 5: Executar os testes de contrato**

Run: `vendor\bin\phpunit tests/DisponibilidadeApiContractTest.php tests/SlotsTest.php`

Expected: PASS.

### Task 4: Apresentar calendário, legenda e horários por serviço

**Files:**
- Modify: `includes/layout/home/booking-panel.php`
- Modify: `static/booking-panel.js`
- Modify: `static/booking-panel.css`
- Modify: `static/calendar.js`
- Modify: `static/calendar.css`
- Modify: `tests/booking-panel.test.mjs`

**Interfaces:**
- Consumes: `estados` de mês e `slots` por serviço da Task 3.
- Produces: mapa de cliente `state.serviceSlots[servicoId] = inicio` e campos ocultos `horarios[servico_id]`.
- Produces: estados visuais `available`, `selected`, `booked` e `unavailable` acessíveis ao teclado e leitores de tela.

- [ ] **Step 1: Escrever testes RED da estrutura e dos estados**

Exigir painel com legenda textual, contêiner de cartões por serviço, campos ocultos nomeados `horarios[...]`, classe de slot selecionado e suporte no calendário para indicador de ocupação sem texto de cliente.

- [ ] **Step 2: Executar o teste de painel para confirmar a falha**

Run: `node --test tests/booking-panel.test.mjs`

Expected: FAIL pelos elementos, estados e manipuladores inexistentes.

- [ ] **Step 3: Atualizar calendário e estilos de estado**

Fazer `Calendar.setAvailableDates(dias, estados)` preservar o primeiro argumento compatível e aplicar verde a datas disponíveis, roxo à data selecionada e marcador vermelho acessível quando `possui_ocupacao` for verdadeiro. Criar legenda: verde disponível, roxo selecionado, vermelho agendado/indisponível. Não revelar detalhes de terceiros.

- [ ] **Step 4: Renderizar cartões de horário por serviço**

Depois da data, renderizar um cartão por serviço. Buscar sua grade passando as escolhas dos outros cartões, marcar roxo apenas o slot atualmente escolhido, exibir verde nos disponíveis e vermelho nos bloqueados/ocupados. Toda nova seleção atualiza as grades dos demais cartões e remove somente escolhas que o servidor marcou inválidas.

- [ ] **Step 5: Atualizar resumo, validação e responsividade**

O resumo deve listar início/fim de cada serviço e o botão só habilita quando todos possuírem slots válidos. Em celular, cartões, legenda e grades ficam em coluna sem rolagem sobreposta nem perda de foco. Usar `aria-live` para carregamento/erro e manter `Escape`, fechar e foco existentes.

- [ ] **Step 6: Executar testes de interface e lint**

Run: `node --test tests/booking-panel.test.mjs; npm.cmd run lint; npm.cmd run check:lines`

Expected: PASS.

### Task 5: Confirmar, comunicar e administrar os intervalos individuais

**Files:**
- Modify: `agendar.php`
- Modify: `includes/whatsapp-agendamento.php`
- Modify: `includes/agendamentos-admin.php`
- Modify: `admin/agendamentos.php`
- Modify: `tests/AgendamentoTest.php`
- Modify: `tests/WhatsappAgendamentoTest.php`
- Modify: `tests/AgendamentoAdminTest.php`

**Interfaces:**
- Consumes: `horarios[servico_id]` do painel e persistência da Task 2.
- Produces: resposta já existente `{whatsapp_url}` após reserva completa; `409` sem reserva parcial em conflito.
- Produces: itens agregados na listagem administrativa, cada um com serviço e intervalo.

- [ ] **Step 1: Escrever testes RED para POST, WhatsApp e admin**

Adicionar teste que submete dois serviços com horários separados e exige itens persistidos com inícios/fins distintos, pai com intervalo mínimo/máximo e mensagem contendo `• Serviço — 09:00 às 09:30 — 30 min — R$ ...`. Exigir rejeição para mapa ausente, ID extra e sobreposição. Na lista admin, exigir itens sem consulta N+1 e preservação de cliente/telefone apenas no contexto autenticado.

- [ ] **Step 2: Executar os testes focados para confirmar a falha**

Run: `vendor\bin\phpunit tests/AgendamentoTest.php tests/WhatsappAgendamentoTest.php tests/AgendamentoAdminTest.php`

Expected: FAIL porque o POST ainda recebe `hora_inicio` único e as saídas não mostram intervalos por item.

- [ ] **Step 3: Atualizar o endpoint de confirmação**

Em `agendar.php`, aceitar somente o mapa completo `horarios`, normalizar e validar cada chave/valor antes de chamar a Task 2. Conservar CSRF, formato JSON, erro público sem detalhes internos e a navegação ao WhatsApp. Uma nova tentativa após reserva concorrente deve devolver `409`, não uma segunda reserva.

- [ ] **Step 4: Atualizar WhatsApp e painel administrativo**

Formatar cada linha do WhatsApp com início e fim do item. Em `listarAgendamentosAdmin()`, carregar os itens de todos os pais em uma consulta adicional e associá-los em memória; em `admin/agendamentos.php`, exibir cada serviço e seu intervalo abaixo da janela geral. Não usar `GROUP_CONCAT` nem uma consulta por linha.

- [ ] **Step 5: Executar os testes focados**

Run: `vendor\bin\phpunit tests/AgendamentoTest.php tests/WhatsappAgendamentoTest.php tests/AgendamentoAdminTest.php`

Expected: PASS.

### Task 6: Revisão integrada e aceite visual

**Files:**
- Modify: `docs/ARCHITECTURE.md`
- Modify: `docs/API.md`
- Modify: `docs/superpowers/specs/2026-10-09-horarios-por-servico.md` somente se a implementação revelar ajuste aprovado de contrato

**Interfaces:**
- Consumes: Tasks 1–5.
- Produces: documentação coerente e evidência de regressão/aceite visual.

- [ ] **Step 1: Atualizar a arquitetura**

Registrar que os itens, e não o intervalo pai, são a autoridade de ocupação; documentar a compatibilidade legada e o fluxo painel → revalidação → WhatsApp.

- [ ] **Step 2: Executar a suíte e análises completas**

Run: `composer test; composer stan; composer cs; npm.cmd run lint; npm.cmd run check:lines; node --test tests/booking-panel.test.mjs; git diff --check`

Expected: todos os testes passam, PHPStan/PHPCS/ESLint sem erros e sem whitespace inválido.

- [ ] **Step 3: Realizar aceite visual no Apache local**

Em `http://localhost:8080/`, verificar desktop e celular: serviços em horários separados; cores e legenda; escolha roxa; ocupado/vermelho sem PII; dia verde apenas com combinação; erro de conflito não fecha o painel; resumo e WhatsApp com intervalos individuais. Usar uma data/serviços de teste e limpar apenas registros de teste com autorização explícita.

- [ ] **Step 4: Solicitar revisão de código e handoff**

Revisar corretude, segurança, legibilidade, arquitetura e desempenho. Informar arquivos alterados, comandos executados, resultado do aceite e qualquer migração pendente. Não criar commit sem autorização explícita.

## Self-review

- **Cobertura da especificação:** Tasks 1–2 cobrem schema, compatibilidade e motor; Task 3 cobre contratos públicos e privacidade; Task 4 cobre calendário, cores, legenda, responsividade e resumo; Task 5 cobre confirmação, WhatsApp e admin; Task 6 cobre documentação, regressão e aceite.
- **Consistência de interfaces:** Task 2 define os intervalos e a combinação; Task 3 os expõe; Task 4 os consome e produz `horarios`; Task 5 valida e grava o mesmo mapa.
- **Falhas de maior risco:** Os cinco itens de `Review Focus` estão cobertos por Tasks 2–5.
- **Proporção:** O plano define contratos, dados e evidências sem transcrever a implementação.
