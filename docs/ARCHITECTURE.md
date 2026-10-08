# Arquitetura — Reiki Ana

Status: arquitetura PHP + MySQL; Fase 4 concluída
Última revisão: 2026-10-01

> **Governança:** o status de execução de cada fase (concluída/parcial/bloqueada) é controlado em `PLANO_MESTRE_ANAREIKI.md`. Este documento descreve a arquitetura técnica; não deve ser usado para acompanhar andamento de fase.

---

## 1. Visão geral

O site sai de um protótipo estático (Hono/Cloudflare) para uma **aplicação PHP 8 + MySQL** hospedada no plano compartilhado da Hostinger. O padrão é um **monólito PHP renderizado no servidor**: sem SPA, sem build de framework, sem processo Node persistente. A interatividade do calendário de agendamento usa um único endpoint JSON consumido por `fetch`.

Condução por fases (detalhe em `PLANO_MESTRE_ANAREIKI.md` §3):

1. banco;
2. fundação PHP (config, PDO, auth, CSRF);
3. site público dinâmico;
4. motor de agendamento;
5. painel admin (Bootstrap);
6. imagens locais;
7. deploy Hostinger;
8. limpeza da stack antiga.

---

## 2. Estado atual do repositório

O repositório mantém o protótipo Hono/Cloudflare durante a migração e já contém a fundação PHP da Fase 2. As Tasks 1–5 da Fase 3 implementaram a home dinâmica. A Fase 4 implementou o motor de agendamento: geração de slots, calendário vanilla JS, endpoints JSON, página de agendamento com POST handler e confirmação via WhatsApp. CRUD administrativo permanece nas fases posteriores.

```text
Anareiki/
├── public_html/index.php             # home PHP; consulta serviços e profissionais ativos
├── public_html/agendar.php           # fluxo de agendamento (form + calendário + POST)
├── public_html/confirmacao-agendamento.php  # confirmação + link WhatsApp
├── public_html/api/slots.php         # GET JSON: horários livres (rate limit 10/min)
├── public_html/api/profissionais.php # GET JSON: profissionais por serviço
├── public_html/includes/             # PDO, repositories, helpers públicos e autenticação
├── public_html/includes/slots.php    # geração de slots e validação de conflito
├── public_html/includes/layout/home/ # partials da home (sobre, serviços e conteúdo complementar)
├── public_html/static/               # CSS, JS público (calendar, agendar, confirmação)
├── src/index.tsx                     # legado — HTML da home como string Hono
├── src/renderer.tsx                  # legado
├── public/static/                     # CSS e JS atuais; origens preservadas até a Fase 8
├── public/favicon.svg
├── wrangler.jsonc · vite.config.ts · ecosystem.config.cjs # legado — remover na Fase 8
└── docs/
```

O schema e o seed estão nas migrações 001 e 002; a autenticação admin com 2FA e a fundação PDO também estão implementadas. A home pública PHP carrega somente serviços e profissionais ativos, escapa conteúdo dinâmico e valida URLs de imagem. O motor de agendamento (Fase 4) inclui: geração de slots com detecção de conflito O(n), calendário vanilla JS responsivo, endpoints JSON com rate limit, página de agendamento com validação server-side e confirmação via WhatsApp. Dados do cliente (nome e telefone) são persistidos; e-mail nunca é gravado. CRUD administrativo permanece nas fases posteriores.

---

## 3. Arquitetura-alvo

```text
Internet
   ↓  HTTPS (SSL grátis Hostinger)
Apache (Hostinger compartilhado) + PHP 8
   ↓
┌───────────────────────────────┬───────────────────────────────┐
│ Site público                  │ Área administrativa (/admin)  │
│ Home (serviços/profissionais) │ Login (senha + 2FA e-mail)    │
│ Agendar (calendário + form)   │ Serviços (CRUD)               │
│ Contato / WhatsApp            │ Profissionais (CRUD)          │
│                               │ Disponibilidade (CRUD)        │
│                               │ Agendamentos (status)         │
└───────────────────────────────┴───────────────────────────────┘
   ↓
Camada de acesso a dados (includes/repositories)
   ↓
PDO (prepared statements)
   ↓
MySQL
```

Regra de ouro: **componente visual não contém regra de negócio**. Consultas e regras ficam em `includes/`, nunca embutidas no HTML das páginas.

---

## 4. Modelo de dados (MySQL)

Todo `id` (e toda FK) é **UUID v7 em `CHAR(36)`**, gerado no PHP (`gerarUuid()`); o seed usa valores fixos. MySQL/MariaDB não têm RLS — o controle de acesso a linhas/colunas é feito nos repositories (ver §9 e `RULES.md` §10).

```sql
administradores   -- login administrativo (papel único)
  id, nome, email (unique), senha_hash, criado_em

codigos_2fa       -- 2FA por e-mail: só hash do código, uso único
  id, administrador_id (FK, ON DELETE CASCADE), codigo_hash,
  expira_em, tentativas, usado_em (nullable), criado_em

limites_taxa      -- rate limit por chave em janela fixa (migração 002)
  chave (PK natural, ex.: 'login:ip:…'), contador, janela_inicio

profissionais
  id, nome, especialidade, bio, foto_url, ativo (bool), criado_em

servicos
  id, nome, descricao, duracao_min (int), preco (decimal, nullable → "Consultar valor"),
  categoria, imagem_url, icone, cor, tag (apresentação do card, todos nullable),
  ativo (bool), ordem (int)

profissional_servico          -- N:N: quais serviços cada profissional faz
  profissional_id, servico_id  -- PK composta

disponibilidade               -- recorrente por dia da semana
  id, profissional_id, dia_semana (0=domingo … 6=sábado, como date('w')),
  hora_inicio (time), hora_fim (time)

agendamentos
  id, servico_id, profissional_id,
  cliente_nome, cliente_telefone, cliente_email (nullable),
  data (date), hora_inicio (time), hora_fim (time),
  status ENUM('pendente','confirmado','cancelado','concluido') default 'pendente',
  observacao (nullable), criado_em
```

Integridade e proteção contra overbooking:

- FKs: `agendamentos.servico_id → servicos.id`, `agendamentos.profissional_id → profissionais.id`; `disponibilidade.profissional_id → profissionais.id`.
- **Índice único** `(profissional_id, data, hora_inicio)` em `agendamentos` — rede de segurança contra corrida, além da validação em `slots.php`.
- `ON DELETE`: preferir desativar (`ativo=0`) a apagar; não apagar profissional/serviço com agendamentos futuros (validar no admin).
- DDL + seed em **migrações numeradas** `sql/migrations/NNN_descricao.sql` (raiz do repositório, **fora** de `public_html/`); a tabela `migracoes (versao, aplicada_em)` registra o que já foi aplicado em cada ambiente. Regras em `RULES.md` §10.1. O seed não inclui administrador — ele é criado na Fase 2 por script local, sem senha versionada.

> `ponytail:` disponibilidade recorrente por dia da semana é o mínimo que cobre o caso. Tabela `bloqueios(data, profissional_id)` para feriados/folgas entra só quando aprovada (fora do escopo atual — ver Plano Mestre §2). `profissional_servico` pode ser removida se houver só uma profissional.

---

## 5. Organização de pastas alvo (`public_html/`)

```text
config.php                  # local secret file outside the public web root; Git-ignored

public_html/
├── index.php                # home — identidade visual atual, dados do banco
├── agendar.php              # fluxo de agendamento (form + calendário)
├── .htaccess                # nega acesso a config/includes; força HTTPS
├── includes/
│   ├── db.php               # conexão PDO única + gerarUuid() (v7)
│   ├── auth.php             # sessão, login/logout, 2FA, require_admin()
│   ├── mailer.php           # envio via PHPMailer + SMTP Hostinger
│   ├── limites.php          # rate limit (tabela limites_taxa)
│   ├── csrf.php             # geração/validação de token
│   ├── slots.php            # geração de horários e validação de conflito
│   ├── repositories.php     # consultas (serviços, profissionais, agendamentos)
│   └── layout/
│       ├── admin.php         # topo/rodapé Bootstrap do painel
│       └── home/             # head.php, top.php, about.php (Task 2)
├── api/
│   └── slots.php            # JSON: horários livres p/ serviço+profissional+data
├── admin/
│   ├── index.php            # dashboard (próximos agendamentos)
│   ├── login.php · verificar.php · logout.php   # senha → código 2FA → painel
│   ├── servicos.php
│   ├── profissionais.php
│   ├── disponibilidade.php
│   └── agendamentos.php
├── static/
│   ├── style-01-foundation.css through style-06-footer-responsive.css # copy planned in Task 4
│   ├── app.js               # interações públicas
│   ├── admin.js             # interações do painel (Bootstrap)
│   └── img/                 # imagens baixadas do genspark (Fase 6)
├── vendor/                  # Composer (PHPMailer) — bloqueado no .htaccess
└── favicon.svg

sql/                         # raiz do repositório — NÃO sobe para public_html
└── migrations/              # aplicadas em ordem via phpMyAdmin/CLI
    ├── 001_schema_inicial.sql   # tabelas + seed + tabela migracoes
    └── 002_limites_taxa.sql     # rate limit do login/2FA (e slots na Fase 4)

bin/criar-admin.php          # CLI local: cria admin / redefine senha — NÃO sobe
tests/                       # PHPUnit — NÃO sobe
composer.json · phpunit.xml · phpstan.neon · phpcs.xml   # vendor-dir = public_html/vendor
```

Ambiente local: Apache do XAMPP em `http://localhost:8080` com `DocumentRoot` em `public_html/` (VirtualHost com `AllowOverride All` e `Require local`), para o `.htaccess` valer igual à Hostinger. O `.htaccess` não força HTTPS em `localhost`, e o cookie de sessão só recebe `Secure` quando a conexão é HTTPS.

Convention: data access lives in `includes/`; `config.php` stays outside the document root and `.htaccess` blocks `includes/`.

---

## 6. Fundação PHP (Fase 2)

- **`db.php`**: instancia um único `PDO` com `ERRMODE_EXCEPTION`, `charset=utf8mb4`, `PDO::ATTR_EMULATE_PREPARES=false`.
- **`auth.php`**: `session_start()` com cookie `HttpOnly`, `Secure`, `SameSite=Lax`; `login()` usa `password_verify`; `require_admin()` redireciona para `login.php` se não houver sessão.
- **`csrf.php`**: token por sessão, validado em todo POST (público e admin).
- **`mailer.php`**: PHPMailer com `smtp.hostinger.com`, porta 465 (SSL) — 587/STARTTLS como alternativa; usuário = e-mail completo da caixa dedicada, senha = senha da caixa (em `config.php`, nunca versionada).
- **Criação do admin**: script CLI local que pede nome, e-mail e senha no terminal e grava `password_hash` — nenhuma senha ou hash no repositório.
- Nunca acessar `$_POST`/`$_GET` sem validar; nunca concatenar SQL — sempre prepared statements. Ver `RULES.md` §10.

---

## 7. Fluxo de agendamento (Fase 4)

```text
Cliente escolhe serviço
   ↓ (define duracao_min)
escolhe profissional que faz o serviço  (profissional_servico)
   ↓
escolhe data → GET api/slots.php?servico=&profissional=&data=
   ↓
slots.php:
   1. lê disponibilidade do profissional para aquele dia_semana
   2. gera horários em passos de duracao_min dentro das faixas
   3. remove horários que colidem com agendamentos existentes na data
   ↓ devolve JSON de horários livres
cliente escolhe horário + preenche nome/telefone
   ↓ POST agendar.php (com CSRF)
agendar.php revalida no servidor (slot ainda livre e dentro da faixa)
   ↓
grava agendamento status='pendente'  (índice único evita corrida)
   ↓
admin confirma no painel (status='confirmado')
```

> `ponytail:` geração de slots é O(n) por dia (varredura ingênua) — suficiente para a agenda de uma terapeuta. A lógica de `slots.php` (geração + detecção de conflito) é a única não-trivial do projeto e **tem teste PHPUnit obrigatório** (ver `RULES.md` §11). Otimizar só se a agenda crescer muito.

---

## 8. Fluxo de autenticação admin (Fase 2/5)

```text
Acessa /admin/*
   ↓
require_admin() verifica sessão
   ↓ sem sessão → redireciona /admin/login.php
login.php: valida CSRF → password_verify
   ↓ senha ok → sessão "pendente 2FA" (ainda sem acesso ao painel)
gera código de 6 dígitos (random_int) → grava só o hash em codigos_2fa
   (validade 60 s, máx. 5 tentativas, uso único) → envia por e-mail
   ↓
verificar.php: valida CSRF → confere hash, validade e tentativas
   ↓ ok → marca usado_em → regenera id de sessão → sessão admin completa
libera o painel (papel único: admin)
```

Regras do 2FA: reenvio no mínimo a cada 60 s; ao gerar um código novo, os anteriores daquele admin deixam de valer; mensagem de erro não revela se o e-mail existe; não existe caminho no código para pular o 2FA.

Só existe o papel `admin`. RBAC com múltiplos papéis está fora do escopo atual (Plano Mestre §2).

---

## 9. Fluxo de dados público vs. privado

```text
Site público  → só dados públicos (serviços ativos, profissionais ativos, horários livres)
Admin         → dados públicos + dados de clientes (nome/telefone dos agendamentos)
```

Telefone e e-mail de cliente são dados pessoais: nunca exibidos em página pública nem logados. Exceção aprovada na Fase 5: o admin autenticado pode abrir o WhatsApp via URL `https://wa.me/NUMERO` do cliente; o telefone só é enviado ao destino ao abrir o link. Não incluir contatos em filtros/query strings internos. Ver `RULES.md` §10.

---

## 10. Como adicionar uma nova página

1. confirmar que está no escopo aprovado (Plano Mestre);
2. definir objetivo e CTA principal;
3. reutilizar `header.php`/`footer.php` e tokens do `DESIGN-SYSTEM.md`;
4. isolar qualquer consulta em `includes/`;
5. verificar título único + meta description (SEO, `RULES.md` §9);
6. documentar em `API.md` se criar endpoint.

---

## 11. Como adicionar uma nova funcionalidade

1. validar com o Jose se há regra de negócio nova;
2. verificar impacto em banco/segurança;
3. seguir o fluxo de aprovação em duas camadas (`PLANO_MESTRE_ANAREIKI.md` §4);
4. atualizar `API.md`/`DESIGN-SYSTEM.md`/`RULES.md` conforme o caso;
5. implementar com o menor acoplamento possível;
6. revisar acessibilidade, responsividade e segurança.

---

## 12. Deploy (Fase 7 — Hostinger compartilhado)

1. hPanel → criar banco MySQL + usuário; anotar credenciais.
2. hPanel → Bancos → phpMyAdmin → importar os arquivos de `sql/migrations/` **em ordem numérica**, só os que ainda não constam em `SELECT * FROM migracoes`.
3. Subir o conteúdo de `public_html/` (sem `sql/`) via Gerenciador de Arquivos ou FTP para a raiz `public_html`.
4. Editar `config.php` no servidor com as credenciais (não versionar).
5. hPanel → SSL → ativar certificado grátis; forçar HTTPS no `.htaccess`.
6. Testar: home, agendamento ponta a ponta, login admin, CRUD.

Sem passo de build: são arquivos PHP/CSS/JS servidos diretamente. O ESLint e os testes rodam localmente antes do upload (ver `RULES.md` §11), não no servidor.

## 13. Ampliações da Fase 5 — 2026-10-07

Correções de fechamento: `categorias_servicos(nome)` é cadastro independente adicionado pela migration 004; repositórios administrativos foram extraídos para `servicos-repository.php`, `profissionais-repository.php` e `disponibilidade-repository.php`, carregados pelo arquivo compatível `repositories.php`. O menu/confirmacões usa JS nativo local `admin-ui.js`, sem bundle remoto. Logs agora omitem mensagem bruta da exceção. Ver `CORRECOES-FASE-5.md` para gates e revalidação visual pendente.

- Design: padrão aprovado `public_html/assets/img/{logo,icons,backgrounds,static}/`; ativos em `static/` permanecem legados até migração da Fase 6.
- Conteúdo administrativo: `public_html/uploads/{servicos,profissionais}/`, nomes aleatórios, JPG/PNG/WEBP até 5 MB. Não confundir com ativos versionados de design; uploads exigem backup e preservação no deploy.
- Calendário: `disponibilidade_datas`, chave profissional/data, TEXT contendo JSON `{intervalo, horarios}`. Migration `003`; leitura de listas legadas como 30 min; regras semanais usadas somente em datas sem override. Intervalos 30/60 e duração arredondada reservam blocos contíguos. Ver [calendário](DISPONIBILIDADE-CALENDARIO.md).
- Indicadores: consulta mensal agregada em `resumo-mensal-admin.php`; pizza SVG no servidor, sem dependência de JS. Quantidades não canceladas e estimativa de receita somente de concluídos, calculada pelo preço atual (não histórico financeiro).
- Diagnóstico: páginas 403/404/500 e handler com request ID. Logs dependem de configuração PHP/Apache privada; sanitização ainda pendente.
- Somente papel `admin`; profissionais são entidades administradas, não usuários com credenciais próprias.

Estado técnico e divergências da implementação: [revisão da Fase 5](REVISAO-FASE-5.md). Não considerar esses padrões integralmente validados enquanto R1–R6 estiverem abertos.
