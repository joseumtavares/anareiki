# Plano Mestre — Reiki Ana (Massoterapeuta)

Status: planejamento aprovado da migração de stack
Última revisão: 2026-09-25

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

### 0.2. Stack descartada e por quê

- **Java Spring Boot** — descartado: **não roda no plano compartilhado** da Hostinger (exigiria VPS/KVM com JDK). Registrado aqui para não ser reproposto sem uma decisão consciente de trocar de hospedagem.
- **React/Vue/Angular** — descartado: página institucional de baixa interatividade; framework só adicionaria peso e passo de build sem ganho real.
- **Node.js** — descartado: nada precisa rodar como serviço persistente; PHP por requisição atende.

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
- painel administrativo autenticado (login por senha) com Bootstrap;
- site público dinâmico preservando o visual atual.

**Fora do escopo por ora (adicionar só com nova aprovação):**

- pagamento online;
- notificação automática por e-mail/WhatsApp na confirmação (o admin confirma manualmente);
- tabela de bloqueios/folgas de datas específicas (feriados);
- múltiplos papéis de acesso (só existe o papel `admin`);
- CRUD dos "pacotes" (permanecem estáticos no site até haver aprovação);
- multi-idioma.

---

## 3. Roadmap por fases

Cada fase só inicia após a anterior estar concluída, testada e aprovada (fluxo da seção 4).

| Fase | Entrega | Depende de | Doc de referência |
|---|---|---|---|
| **1. Banco** | `sql/schema.sql` + seed com serviços/profissional atuais | — | `ARCHITECTURE.md` §4 |
| **2. Fundação PHP** | conexão PDO, `config.php` protegido, sessão + login admin, CSRF, `.htaccess` | 1 | `ARCHITECTURE.md` §6, `RULES.md` §10 |
| **3. Site público dinâmico** | `index.php`: serviços e profissionais vindos do banco, visual intacto | 2 | `DESIGN-SYSTEM.md` |
| **4. Motor + fluxo de agendamento** | `agendar.php` + `includes/slots.php` (cálculo/validação) + **teste PHPUnit**; grava `pendente` | 3 | `API.md` §2, `ARCHITECTURE.md` §7 |
| **5. Painel admin (Bootstrap)** | CRUD serviços, profissionais, disponibilidade; lista de agendamentos com troca de status | 2 | `DESIGN-SYSTEM.md` §admin |
| **6. Imagens** | baixar as 10 imagens hoje hotlinkadas de `genspark.ai` para `static/img/` | 3 | risco §5 |
| **7. Deploy Hostinger** | criar MySQL no hPanel, importar `schema.sql`, subir arquivos, ativar SSL, configurar `config.php` | 1–6 | `ARCHITECTURE.md` §12 |
| **8. Limpeza** | remover Hono/Cloudflare/Vite/Wrangler do repositório | 7 | — |

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
| Sem VPS → sem processos de fundo | Não há como rodar filas/cron sofisticados | Escopo atual não exige; agendamento é síncrono. |

---

## 6. Documentos do projeto

- `PLANO_MESTRE_ANAREIKI.md` — **este arquivo** (escopo, fases, governança).
- `ARCHITECTURE.md` — arquitetura técnica, modelo de dados, estrutura de pastas.
- `API.md` — contrato dos endpoints públicos e ações administrativas.
- `DESIGN-SYSTEM.md` — identidade visual, tokens, componentes.
- `RULES.md` — regras de código, segurança, acessibilidade e gates de qualidade.

Manter os cinco **sincronizados**: toda decisão estrutural aprovada atualiza o documento correspondente na mesma entrega.
