# API — Reiki Ana

Status: contrato-alvo dos endpoints (ainda não implementados; serão criados nas Fases 4–5)
Escopo atual: PHP 8 + MySQL; monólito renderizado no servidor + 1 endpoint JSON para o calendário
Última revisão: 2026-09-25

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
- Status: planejado (Fase 4).
- Visibilidade: **pública**.
- Autenticação: não exige.
- Parâmetros (query): `servico` (int), `profissional` (int), `data` (`YYYY-MM-DD`).
- Resposta de sucesso (`200`):

```json
{ "servico_id": 3, "profissional_id": 1, "data": "2026-10-02",
  "duracao_min": 60, "horarios": ["09:00", "10:00", "14:00"] }
```

- Respostas de erro:
  - `400`: parâmetro ausente/inválido (data no passado, ids não numéricos).
  - `429`: rate limit por IP excedido (proteção contra varredura da agenda).
- Observações de segurança:
  - parâmetros validados e convertidos antes de qualquer consulta;
  - consulta com prepared statements; retorna **só horários**, nunca dados de outros clientes;
  - a lista é conveniência de UI — a validade real é **reconferida no servidor** ao gravar (2.2).

### 2.2. Criar agendamento

- URL: `POST /agendar.php`
- Descrição: grava um pedido de agendamento como `pendente`.
- Status: planejado (Fase 4).
- Visibilidade: **pública**.
- Autenticação: não exige. **Exige token CSRF** válido.
- Body (form-urlencoded): `servico_id`, `profissional_id`, `data`, `hora_inicio`, `cliente_nome`, `cliente_telefone`, `cliente_email` (opcional), `observacao` (opcional), `csrf_token`.
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

Todas exigem **sessão autenticada** (`require_admin()`) + **token CSRF** em todo POST. Sem sessão → redireciona para `login.php`. Papel único: `admin`.

| Recurso | Página | Ações (POST via `?action=`) | Auth |
|---|---|---|---|
| Login | `admin/login.php` | `login` (valida senha) | Nenhuma — ponto de entrada |
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
