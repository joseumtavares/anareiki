# Plano Mestre — Reiki Ana (Massoterapeuta)

Status: Fase 3 concluída; Fase 4 não iniciada
Última revisão: 2026-10-01

> **Governança:** este é o documento-raiz do projeto. Ele diz **qual fase está autorizada agora** e qual o escopo aprovado. Os demais documentos (`ARCHITECTURE.md`, `API.md`, `DESIGN-SYSTEM.md`, `RULES.md`) descrevem *como* fazer; este diz *o que* e *quando*. Nenhuma fase começa sem a fase anterior concluída e aprovada.

---

## 0. Contexto e ponto de partida

O projeto nasceu como **catálogo estático de página única** para uma massoterapeuta (Reiki Ana), com identidade visual em roxo/rosa/lilás. O protótipo atual está empacotado em **Hono + Cloudflare Workers/Pages** servindo uma única string HTML — camada de servidor desnecessária para conteúdo estático.

A decisão do cliente (Jose) foi **evoluir para uma aplicação com backend**, adicionando:

- painel administrativo para cadastrar **serviços**;
- **cadastro de profissionais**;
- **controle de dias/horários disponíveis** para agendamento;
- **agendamento registrado no próprio site** (gravado em banco, com status).

### 0.1. Decisões de stack (fechadas)

| Item | Decisão | Motivo |
|---|---|---|
| Hospedagem | **Hostinger — plano compartilhado** | Já contratado; suporta PHP + MySQL nativamente. |
| Backend | **PHP 8 + PDO** (sem framework pesado) | Roda no compartilhado sem VPS; deploy = subir arquivos; `password_hash`, sessões e prepared statements são nativos. |
| Banco | **MySQL** (via hPanel) | Incluído no plano. |
| Site público | HTML/CSS/JS atuais, tornados **dinâmicos** | Preserva 100% a identidade visual (ver `DESIGN-SYSTEM.md`). |
| Painel admin | **Bootstrap 5** | Componentes prontos aceleram CRUD administrativo. |
| Qualidade | **ESLint** (JS) + **PHPStan/PHP_CodeSniffer** (PHP) + **PHPUnit** (testes) | Gates adaptados à stack — ver `RULES.md` §11. |
| Identificadores | **UUID v7 em `CHAR(36)`**, gerado no PHP | IDs não sequenciais/não adivinháveis; v7 é ordenado no tempo (não fragmenta índice); funciona igual no MariaDB 10.4 local e na Hostinger. |
| Controle de acesso a dados | **Na camada PHP** (repositories), sem RLS | MySQL/MariaDB não têm Row Level Security. Com papel único (`admin`) e sem dados por cliente logado, repositories como porta única + colunas explícitas + `requireAdmin()` cobrem o caso. |
| 2FA do admin | **Código por e-mail** via **SMTP Hostinger** + **PHPMailer** | `smtp.hostinger.com:465` (SSL), autenticação com e-mail + senha da própria caixa (a Hostinger não gera senha de aplicativo). `mail()` nativo não faz SMTP autenticado de forma confiável. |

### 0.2. Stack descartada e por quê

- **Java Spring Boot** — descartado: **não roda no plano compartilhado** da Hostinger (exigiria VPS/KVM com JDK). Registrado aqui para não ser reproposto sem uma decisão consciente de trocar de hospedagem.
- **React/Vue/Angular** — descartado: página institucional de baixa interatividade; framework só adicionaria peso e passo de build sem ganho real.
- **Node.js** — descartado: nada precisa rodar como serviço persistente; PHP por requisição atende.
- **PostgreSQL/Supabase (por causa de RLS)** — descartado em 2026-09-25: o compartilhado da Hostinger não oferece Postgres; exigiria banco externo (novo provedor, mais latência) para um ganho pequeno com papel único.

---

## 1. Objetivo do produto

1. Apresentar a profissional e os serviços com a identidade visual aprovada.
2. Permitir que o cliente **agende online** (serviço → profissional → dia/hora → dados), gravando o pedido como `pendente`.
3. Dar à administradora (Ana) um painel para gerir serviços, profissionais, disponibilidade e agendamentos.
4. Manter o WhatsApp como canal complementar de contato.

---

## 2. Escopo aprovado

**Dentro do escopo (Fases 1–8 abaixo):**

- CRUD de serviços;
- cadastro de profissionais;
- controle de disponibilidade (dias da semana + faixas de horário por profissional);
- motor de agendamento com validação de conflito no servidor;
- painel administrativo autenticado (login por senha **+ 2FA com código por e-mail**) com Bootstrap;
- site público dinâmico preservando o visual atual.

**Fora do escopo por ora (adicionar só com nova aprovação):**

- pagamento online;
- notificação automática por e-mail/WhatsApp na confirmação (o admin confirma manualmente) — o único e-mail do sistema é o código de 2FA do admin;
- tabela de bloqueios/folgas de datas específicas (feriados);
- múltiplos papéis de acesso (só existe o papel `admin`);
- CRUD dos "pacotes" (permanecem estáticos no site até haver aprovação);
- multi-idioma.

---

## 3. Roadmap por fases

Cada fase só inicia após a anterior estar concluída, testada e aprovada (fluxo da seção 4).

### 3.1. Marcadores de status

| Marcador | Estado | Uso |
|---|---|---|
| 🟢 | Concluída | Entrega concluída, validada e registrada no handoff/histórico. |
| 🟡 | Em andamento | Etapa iniciada, com tarefas, validações ou aprovações ainda pendentes. |
| 🔴 | Não iniciada | Etapa ainda não começou ou aguarda a conclusão de dependências. |
| 🛑 | Bloqueada | Não pode avançar até resolver uma dependência externa ou risco impeditivo. |

### 3.2. Estado consolidado em 01 de outubro de 2026

| Status | Etapa | Situação atual | Próximo portão |
|---|---|---|---|
| 🟢 | Fase 1 — Banco | Migração inicial `001_schema_inicial.sql`, tabela `migracoes`, schema e seed presentes; a fundação foi usada pelas etapas posteriores. | Nenhum; manter como base concluída. |
| 🟢 | Fase 2 — Fundação PHP | Encerrada e aceita por Jose em 30/09/2026. PDO, configuração privada fora de `public_html`, sessão, login, 2FA por e-mail, CSRF, rate limit, UUID v7, PHPMailer e testes estão implementados. PHPUnit, build, ESLint, limite de linhas, PHPCS e PHPStan foram registrados como aprovados. Jose confirmou a rotação da senha SMTP em 01/10/2026. | Nenhum. |
| 🟢 | Fase 3 — Site público dinâmico | Tasks 1–5 concluídas em 01/10/2026. Home dinâmica, documentação, gates e revisão encerrados; Jose aprovou a inspeção visual responsiva e autorizou commit, merge e push. | Encerrada; manter a home pública como base das fases seguintes. |
| 🔴 | Fase 4 — Motor e fluxo de agendamento | Não iniciada. Inclui consulta de horários, validação de conflito no servidor e gravação de agendamento como `pendente`. | Antes de implementar, apresentar proposta/modelagem, obter aprovação de Jose e revisão técnica conforme a seção 4. |
| 🔴 | Fase 5 — Painel admin (Bootstrap) | Não iniciada. Inclui CRUD de serviços, profissionais e disponibilidade, além da gestão de status dos agendamentos. | Aguardar os portões de fase e definir a sequência de execução com o estado da Fase 4; não implementar fora do fluxo de aprovação. |
| 🔴 | Fase 6 — Imagens | Não iniciada. Baixar as imagens externas ainda usadas no site para `public_html/static/img/`, verificando referências e licenças/origem conforme aplicável. | Inventariar as imagens restantes após a Fase 3 e migrá-las sem quebrar o visual aprovado. |
| 🔴 | Fase 7 — Deploy Hostinger | Não iniciada. Implantar banco e aplicação no hPanel, habilitar SSL e configurar credenciais privadas. | Depende das Fases 1–6. Rotação SMTP confirmada; verificar configuração real no ambiente Hostinger. |
| 🔴 | Fase 8 — Limpeza | Não iniciada. Remover Hono, Cloudflare, Vite e Wrangler do repositório legado. | Depende do deploy validado (Fase 7). |

**Pendências transversais registradas:** (1) ampliar a cobertura de testes de concorrência das escritas atômicas do OTP antes de alterar essa persistência. A senha SMTP foi rotacionada, conforme confirmação de Jose em 01/10/2026. A validação visual automatizada do painel admin não foi realizada e deve ser considerada nas verificações aplicáveis antes do deploy.

O estado acima foi consolidado a partir de `docs/HANDOFF.md`, dos arquivos presentes no repositório e do histórico publicado em `main`. A Fase 3 foi encerrada com o aceite de Jose em 01/10/2026; a Fase 4 continua não iniciada.

---

## 4. Fluxo obrigatório de aprovação em duas camadas

Nenhuma implementação estrutural (schema, endpoint, regra de negócio, componente com decisão de arquitetura) começa sem passar, nesta ordem:

1. proposta/modelagem apresentada pelo agente implementador;
2. análise e aprovação do Jose;
3. revisão técnica (Claude ou par técnico);
4. implementação + testes automatizados (a partir da Fase 3, toda regra de negócio nova vem com **contrato de testes em código**, não em prosa);
5. lista de testes manuais entregue ao Jose (no navegador);
6. aprovação dupla antes de qualquer commit ou push.

Jose conduz o fluxo Git. O agente sugere comandos e explica, mas **não executa Git sem pedido explícito**.

---

## 5. Riscos e pendências

| Risco | Impacto | Mitigação |
|---|---|---|
| Imagens hotlinkadas de `genspark.ai` | Links externos podem cair e quebrar o visual | Fase 6: baixar tudo para `static/img/`. |
| Credenciais MySQL em `config.php` no compartilhado | Exposição de segredo | `config.php` fora da árvore pública quando possível; senão, `.htaccess` bloqueando acesso direto; nunca versionar. Ver `RULES.md` §10. |
| Overbooking por corrida em agendamento | Dois clientes no mesmo horário | Validação de conflito no servidor + índice único no banco (ver `ARCHITECTURE.md` §4). Coberto por teste (Fase 4). |
| E-mail do 2FA não chega (spam, SMTP fora) → admin trancado fora | Painel inacessível | Caixa dedicada no domínio com SPF/DKIM configurados no hPanel; testes sempre com o SMTP real; recuperação via script local/phpMyAdmin (sem "pular 2FA" no código). |
| Senha da caixa SMTP em `config.php` | Envio de e-mail em nome do domínio | Mesmo tratamento das credenciais MySQL: fora da árvore pública/bloqueado no `.htaccess`, nunca versionado; caixa dedicada só para envio. |
| Sem VPS → sem processos de fundo | Não há como rodar filas/cron sofisticados | Escopo atual não exige; agendamento é síncrono. |

---

### 4.1. Encerramento e handoff entre sessoes

Toda sessao deve terminar com a documentacao afetada atualizada e `docs/HANDOFF.md` pronto para ser usado na abertura da proxima etapa. Alem do estado da fase, arquivos, decisoes, skills, verificacoes, bloqueios, riscos e autorizacao, o handoff registra branch/worktree, commit base, objetivo e escopo da proxima etapa, contexto que deve ser lido e a primeira acao prevista.

Ao abrir uma etapa, ler o handoff e este Plano Mestre antes de alterar codigo. Uma nova etapa so pode comecar quando o handoff estiver preenchido e as pendencias bloqueadoras estiverem explicitamente aceitas pelo Jose.

## 6. Documentos do projeto

- `PLANO_MESTRE_ANAREIKI.md` — **este arquivo** (escopo, fases, governança).
- `ARCHITECTURE.md` — arquitetura técnica, modelo de dados, estrutura de pastas.
- `API.md` — contrato dos endpoints públicos e ações administrativas.
- `DESIGN-SYSTEM.md` — identidade visual, tokens, componentes.
- `RULES.md` — regras de código, segurança, acessibilidade e gates de qualidade.
- `HANDOFF.md` — estado de transição entre sessões, skills usadas, verificações e próximo passo autorizado.

Manter os seis **sincronizados**: toda decisão estrutural aprovada atualiza o documento correspondente na mesma entrega, e o `HANDOFF.md` é atualizado ao encerrar a sessão.
