# API — Reiki Ana

Status: endpoints públicos implementados (Fase 4); endpoints admin planejados (Fase 5)
Escopo atual: PHP 8 + MySQL; monólito renderizado no servidor + endpoints JSON para o calendário
Última revisão: 2026-10-01

> **Governança:** consulte `PLANO_MESTRE_ANAREIKI.md` para saber a fase vigente e o escopo aprovado antes de propor qualquer endpoint novo. Este arquivo define o **contrato e o padrão**; não autoriza, por si só, a criação de rotas.

---

## 1. Natureza da "API"

Este projeto **não é uma API REST desacoplada**. É um monólito PHP em que:

- páginas públicas e admin são **`.php` renderizados no servidor**;
- formulários enviam **POST** para um handler `.php` (com token CSRF);
- há **um único endpoint JSON** (`api/slots.php`), consumido por `fetch` no calendário de agendamento.

Ainda assim, cada handler segue um contrato documentado (seção 5) para manter previsibilidade e segurança.

---

## 2. Endpoints públicos

### 2.1. Horários livres (JSON)

- URL: `GET /api/slots.php`
- Descrição: retorna os horários disponíveis de um profissional para um serviço em uma data. Alimenta o calendário de `agendar.php`.
- Status: **implementado** (Fase 4).
- Visibilidade: **pública**.
- Autenticação: não exige.
- Parâmetros (query): `servico` (UUID), `profissional` (UUID), `data` (`YYYY-MM-DD`).
- Resposta de sucesso (`200`):

```json
{ "servico_id": "01a0db02-f803-7481-8c64-693c404ead75",
  "profissional_id": "01a0db02-f800-76df-a6eb-97a169b3083f",
  "data": "2026-10-02", "duracao_min": 60, "horarios": ["09:00", "10:00", "14:00"] }
```

- Respostas de erro:
  - `400`: parâmetro ausente/inválido (data no passado, id que não é UUID válido).
  - `429`: rate limit por IP excedido (proteção contra varredura da agenda).
- Observações de segurança:
  - parâmetros validados e convertidos antes de qualquer consulta;
  - consulta com prepared statements; retorna **só horários**, nunca dados de outros clientes;
  - a lista é conveniência de UI — a validade real é **reconferida no servidor** ao gravar (2.2).

### 2.2. Profissionais por serviço (JSON)

- URL: `GET /api/profissionais.php`
- Descrição: retorna os profissionais ativos que realizam um serviço.
- Status: **implementado** (Fase 4).
- Visibilidade: **pública**.
- Autenticação: não exige.
- Parâmetros (query): `servico` (UUID).
- Resposta de sucesso (`200`):

```json
[{ "id": "01a0db02-f800-76df-a6eb-97a169b3083f", "nome": "Ana" }]
```

- Respostas de erro:
  - `400`: parâmetro ausente ou UUID inválido.

### 2.3. Criar agendamento

- URL: `POST /agendar.php`
- Descrição: grava um pedido de agendamento como `pendente`.
- Status: **implementado** (Fase 4).
- Visibilidade: **pública**.
- Autenticação: não exige. **Exige token CSRF** válido.
- Body (form-urlencoded): `servico_id` (UUID), `profissional_id` (UUID), `data`, `hora_inicio`, `cliente_nome`, `cliente_telefone`, `csrf_token`. Nota: `cliente_email` não é coletado nem gravado; `observacao` não é coletada (uso futuro do admin).
- Regras de validação no servidor (obrigatórias, não confiar no front):
  1. serviço e profissional existem e estão ativos, e o profissional faz o serviço;
  2. `hora_inicio` cai dentro de uma faixa de `disponibilidade` daquele dia da semana;
  3. o intervalo `[hora_inicio, hora_inicio + duracao_min)` **não colide** com agendamento existente do profissional na data;
  4. `data` não está no passado; nome e telefone preenchidos e saneados.
- Resposta de sucesso: redireciona para página de confirmação (`303`), sem reenvio de formulário.
- Respostas de erro:
  - `400`: dados inválidos → re-renderiza o formulário com mensagem clara.
  - `403`: CSRF inválido.
  - `409`: horário acabou de ser ocupado (corrida) → pede para escolher outro.
- Observações de segurança:
  - índice único `(profissional_id, data, hora_inicio)` no banco garante atomicidade (`409` capturado do `PDOException`);
  - telefone/e-mail do cliente nunca vão para log nem para URL.

---

## 3. Ações administrativas (`/admin/**`)

Todas exigem **sessão autenticada com 2FA concluído** (`require_admin()`) + **token CSRF** em todo POST. Sem sessão → redireciona para `login.php`. Papel único: `admin`.

| Recurso | Página | Ações (POST via `?action=`) | Auth |
|---|---|---|---|
| Login | `admin/login.php` | `login` (valida senha → envia código 2FA por e-mail) | Nenhuma — ponto de entrada |
| 2FA | `admin/verificar.php` | `verificar` (confere código), `reenviar` (≥ 60 s) | Sessão "pendente 2FA" + CSRF |
| Logout | `admin/logout.php` | `logout` | Sessão |
| Serviços | `admin/servicos.php` | `criar`, `editar`, `ativar`, `desativar` | Sessão + CSRF |
| Profissionais | `admin/profissionais.php` | `criar`, `editar`, `ativar`, `desativar` | Sessão + CSRF |
| Disponibilidade | `admin/disponibilidade.php` | `adicionar_faixa`, `remover_faixa` | Sessão + CSRF |
| Agendamentos | `admin/agendamentos.php` | `confirmar`, `cancelar`, `concluir` | Sessão + CSRF |

Exclusão real de registros é evitada — usar `desativar`. Não remover profissional/serviço com agendamentos futuros.

---

## 4. Modelo obrigatório para documentar um endpoint novo

```md
## Nome do endpoint

- URL:
- Método HTTP:
- Descrição:
- Status:
- Visibilidade: Pública | Administrativa
- Autenticação:
- CSRF: exige? (todo POST exige)
- Parâmetros / Body:
- Resposta de sucesso:
- Respostas de erro:
- Observações de segurança:
```

---

## 5. Convenções

### 5.1. Nomenclatura

- páginas em português (o produto é PT-BR): `servicos.php`, `profissionais.php`, `agendar.php`;
- colunas de banco em português (ver `ARCHITECTURE.md` §4);
- ações via `?action=verbo` em POST; nunca ação destrutiva em GET.

### 5.2. Separação público × admin

```text
/ , /agendar.php , /api/slots.php   → sem sessão; só dados públicos
/admin/**                            → require_admin() + CSRF
```

### 5.3. Respostas e erros

- páginas: renderizam HTML; em erro de validação, re-renderizam o formulário com mensagem que **diz o problema e o que fazer** (ver `RULES.md` §8.1);
- `api/slots.php`: JSON puro (`Content-Type: application/json`), sem envelope extra;
- **nunca** retornar stack trace, SQL, credenciais ou detalhe interno ao cliente; em `500`, mensagem genérica e `error_log` no servidor (sem dados sensíveis).

### 5.4. Segurança de dados

- toda query com **prepared statements** (PDO), sem exceção;
- `SELECT` explícito de colunas em consulta pública — nunca `SELECT *` que exponha dados de cliente;
- validar e sanear toda entrada antes de gravar;
- rate limit por IP no `api/slots.php` e no POST de agendamento;
- não logar telefone, e-mail ou observação do cliente.

---

## 6. Regra final

Este arquivo documenta o contrato-alvo e o padrão (seção 4) para qualquer endpoint **novo**. A criação efetiva de rotas, regras de negócio ou mudanças de contrato continua exigindo o fluxo de aprovação do `PLANO_MESTRE_ANAREIKI.md` §4.

## 7. Contratos administrativos da Fase 5 — 2026-10-07

Atualização após correções: `/admin/` recebe POST com `categoria` e CSRF para cadastrar categoria independente (até 50 caracteres), com redirecionamento no sucesso. Migration `004_categorias_servicos`; seleção em serviços inclui categorias cadastradas e legadas. A integração do upload profissional R1 foi corrigida. As referências a pendências abaixo preservam a revisão inicial; estado atual em `CORRECOES-FASE-5.md`.

Todos exigem `requireAdmin()`; POST persistente exige CSRF. Sem novos endpoints públicos para dados de clientes.

| Página | Consulta/ação |
|---|---|
| `/admin/` | GET `mes=YYYY-MM`; mês atual por padrão; resumo agregado, somente concluídos nos valores; mês inválido retorna 422. Sem cadastro efetivo de categorias nesta versão. |
| `/admin/servicos.php` | GET listagem/`editar`; POST salvar, `acao=alternar`, `excluir`, `excluir_imagem`; multipart com `imagem_upload` ou `imagem_existente`. Exclusão de entidade bloqueada por vínculos/agendamentos; imagem em uso por serviço é bloqueada. |
| `/admin/profissionais.php` | GET listagem/`editar`; POST salvar, alternar, excluir; multipart `foto_upload` ou `imagem_existente`; vínculo com serviços. Integração do upload requer R1. |
| `/admin/disponibilidade.php` | GET `profissional`; POST `profissional_id`, `data=YYYY-MM-DD`, `intervalo=30 ou 60`, `horarios[]=HH:mm`; lista vazia bloqueia a data. Datas passadas e horários desalinhados são rejeitados. |
| `/admin/agendamentos.php` | GET `inicio`, `fim`, `status`, `profissional`; POST `id` UUID, `origem`, `destino`; atualização condicional, CSRF inválido 403, conflito/transição inválida 409, filtro inválido 422. Exibe contato só no admin. |

Sucesso usa redirecionamento pós-POST. WhatsApp é link de navegação externa acionado pelo administrador, não API/envio automático. Páginas de erro Apache: `/errors/403.php`, `/errors/404.php`, `/errors/500.php`.

Contratos com pendências de integração e cobertura estão detalhados em [REVISAO-FASE-5.md](REVISAO-FASE-5.md); esta seção não declara gates completos aprovados.
