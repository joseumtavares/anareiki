# Regras do Projeto — Reiki Ana

Status: regras obrigatórias de desenvolvimento
Última revisão: 2026-09-25

> **Governança:** o status vigente de fase e as pendências que bloqueiam avanço estão em `PLANO_MESTRE_ANAREIKI.md`. Estas regras valem em todas as fases; o Plano Mestre diz qual fase está autorizada agora.

---

## 1. Regra principal de escopo

Não ampliar backend, banco, autenticação, CRUD administrativo ou regras de negócio além do escopo aprovado no `PLANO_MESTRE_ANAREIKI.md` §2. A stack é **PHP 8 + PDO + MySQL** no plano compartilhado da Hostinger — não introduzir Java, Node como serviço, framework SPA ou VPS sem decisão consciente registrada no Plano Mestre.

## 1.1. Fluxo obrigatório de aprovação em duas camadas

Nenhuma implementação estrutural (schema, endpoint, regra de negócio, componente com decisão de arquitetura) começa sem passar, nesta ordem:

1. proposta/modelagem apresentada pelo agente implementador;
2. análise e aprovação do Jose;
3. revisão técnica (Claude ou par técnico);
4. implementação + testes automatizados (a partir da Fase 3, regra de negócio nova vem com **contrato de testes em código**, não em prosa);
5. lista de testes manuais entregue ao Jose (no navegador);
6. aprovação dupla antes de qualquer commit ou push.

Detalhe completo em `PLANO_MESTRE_ANAREIKI.md` §4.

## 1.2. Skills e handoff obrigatório

- Cada tarefa deve usar a skill correspondente à fase do trabalho.
- A skill escolhida e o motivo de uso devem ser registrados no handoff da sessão.
- Ao finalizar qualquer etapa ou sessão, atualizar a documentação afetada e `docs/HANDOFF.md` antes de encerrar.
- O handoff também prepara a abertura da próxima etapa: registrar fase, branch/worktree, commit base, objetivo e escopo autorizado, documentos de contexto, estado inicial, gates, decisões, pendências aceitas, riscos e primeira ação autorizada.
- Na sessão seguinte, ler e validar o handoff e o Plano Mestre antes de alterar código. Se o handoff estiver ausente ou incompleto, completar a documentação primeiro.
- Não iniciar a próxima etapa do Plano Mestre enquanto o handoff anterior estiver ausente ou incompleto.

## 2. Convenções de código

Geral:

- HTML semântico;
- CSS organizado por seção, usando os tokens do `DESIGN-SYSTEM.md`;
- JavaScript só para interação de interface;
- não misturar regra de negócio com componente visual;
- evitar dependências desnecessárias (ver §14).

PHP:

- PHP 8+, `declare(strict_types=1)` no topo de todo arquivo de lógica;
- acesso a dados isolado em `includes/` (repositories); páginas `.php` só orquestram e renderizam;
- **nunca** concatenar entrada em SQL — sempre prepared statements (PDO);
- validar toda entrada (`$_POST`/`$_GET`) no servidor antes de usar;
- funções pequenas e nomeadas por verbo; sem lógica de negócio embutida no HTML.

JavaScript:

- ES moderno, sem framework;
- sem dependências pesadas para interações simples;
- `fetch` para o endpoint de horários; tratar erro e estado de carregamento.

## 3. Nomeação de arquivos

- páginas em português, minúsculas: `index.php`, `agendar.php`, `servicos.php`;
- includes por responsabilidade: `db.php`, `auth.php`, `csrf.php`, `mailer.php`, `limites.php`, `slots.php`, `repositories.php`;
- estáticos em `static/` (`style-01-foundation.css` through `style-06-footer-responsive.css`, `app.js`, `admin.js`, `img/`);
- SQL em `sql/migrations/NNN_descricao.sql` (ver §10.1).

Estrutura de pastas completa em `ARCHITECTURE.md` §5.

## 4. Nomeação de funções PHP

- consultas: `listarServicosAtivos()`, `buscarProfissional($id)`;
- gravação: `criarAgendamento($dados)`, `atualizarStatusAgendamento($id, $status)`;
- regras: `gerarHorariosLivres(...)`, `horarioColide(...)`;
- auth: `login()`, `logout()`, `requireAdmin()`, `csrfToken()`, `csrfValido()`, `enviarCodigo2fa()`, `validarCodigo2fa()`;
- ids: `gerarUuid()` (v7) — todo INSERT recebe o id gerado no PHP.

## 5. Organização de pastas

Documentação em `docs/`. Aplicação em `public_html/` conforme `ARCHITECTURE.md` §5. `config.php` e `includes/` nunca servidos diretamente (bloqueio no `.htaccess`).

## 6. Estrutura de commits

Jose conduz o fluxo Git. O agente sugere comandos e explica, mas **não executa Git sem pedido explícito**.

Formato: `tipo: descrição curta` — tipos: `docs`, `feat`, `fix`, `style`, `refactor`, `test`, `chore`. Exemplo: `feat: motor de agendamento com validação de conflito`.

## 7. Performance

- otimizar/comprimir imagens antes de produção (as imagens do genspark, ao serem baixadas na Fase 6, devem ser otimizadas);
- toda `<img>` declara `width` e `height` explícitos (evita CLS);
- evitar scripts e bibliotecas desnecessários;
- não introduzir Bootstrap no site público (só no admin);
- medir antes de otimizar; a geração de slots é O(n) por dia e só se otimiza se medir lentidão real.

## 8. Acessibilidade

- toda imagem relevante tem `alt`; SVG decorativo usa `aria-hidden="true"`;
- botões têm texto ou label acessível;
- foco visível preservado (não remover `outline` sem substituto);
- menu mobile informa estado aberto/fechado;
- respeitar `prefers-reduced-motion`;
- verificar contraste de texto sobre imagem e sobre cor.

## 8.1. Mensagens de erro e estados assíncronos

- toda mensagem de erro nomeia o problema **e** o que fazer a seguir (ex.: "Este horário acabou de ser reservado. Escolha outro horário disponível." em vez de só "Horário indisponível.");
- atualização assíncrona (calendário de horários, toasts) usa `aria-live="polite"`;
- placeholder mostra exemplo terminado em `…`, nunca instrução.

## 9. SEO

- cada página com título único e meta description;
- URLs legíveis;
- imagens importantes com `alt` descritivo;
- dados estruturados de negócio local (LocalBusiness) na home, quando aprovado;
- `robots.txt` e sitemap na Fase 7.

## 10. Segurança (obrigatória — stack PHP/MySQL)

- Usar `docs/checklist_seguranca_agente_desenvolvimento.md` como roteiro de revisão em cada tarefa de segurança. Avaliar os controles aplicáveis ao escopo alterado, registrar evidências, itens não aplicáveis e pendências no handoff; não marcar controles de infraestrutura ou de funcionalidades futuras como verificados sem evidência.
- Para alterações que recebem dados externos, autenticação, autorização, uploads, chamadas externas ou operações de escrita, revisar os testes negativos pertinentes do checklist e cobrir regressões automatizadamente.

- **PDO com prepared statements** em toda query, sem exceção;
- **`password_hash`/`password_verify`** para senha do admin; nunca senha em texto puro;
- **CSRF token** validado em todo POST (público e admin);
- sessão com cookie `HttpOnly`, `Secure`, `SameSite=Lax`; **regenerar id de sessão** no login;
- `config.php` com credenciais **fora da árvore pública** quando possível; senão, `.htaccess` negando acesso direto; **nunca versionar** credenciais;
- `.htaccess`: negar acesso a `config.php` e `includes/`; forçar HTTPS;
- validar e sanear toda entrada; `SELECT` explícito de colunas em consulta pública (nunca expor dados de cliente);
- validar uploads (tipo, tamanho, extensão) se/quando houver upload de foto de profissional;
- rate limit (tabela `limites_taxa`, `includes/limites.php` — nunca só na sessão) por IP em `api/slots.php` e no POST de agendamento; no login admin por IP e por e-mail (5 falhas/15 min) e na verificação 2FA por admin (10 falhas/15 min);
- **não logar** telefone, e-mail ou observação de cliente; em erro, mensagem genérica ao usuário e `error_log` sem dados sensíveis;
- proteger `/admin/**` com `requireAdmin()` — que só libera sessão com **2FA concluído**;
- **2FA por e-mail** obrigatório no login admin: código de 6 dígitos com `random_int`, gravar **só o hash**, validade 60 s, máx. 5 tentativas, uso único, reenvio ≥ 60 s; nenhum caminho no código para pular o 2FA; nunca logar o código;
- credenciais SMTP (e-mail + senha da caixa dedicada) só em `config.php`, com o mesmo tratamento das credenciais MySQL;
- **sem RLS no banco** (MySQL não tem): toda leitura/escrita passa por `includes/repositories.php`; página nunca executa SQL direto;
- ids são UUID v7 — validar formato de UUID em toda entrada de id (`$_GET`/`$_POST`) antes de consultar;
- índice único `(profissional_id, data, hora_inicio)` como rede de segurança contra overbooking.

## 10.1. Migrações de banco (obrigatório)

- **toda** alteração de banco (tabela, coluna, índice, constraint, dado de seed) é entregue como **nova** migração em `sql/migrations/`, numerada em sequência: `002_adiciona_x.sql`, `003_…`;
- **nunca editar** uma migração já aplicada em qualquer ambiente — corrigir com uma nova;
- a primeira instrução de toda migração (a partir da 002) é `INSERT INTO migracoes (versao) VALUES ('NNN_descricao');` — reaplicar falha nessa linha antes de alterar qualquer tabela;
- aplicar em ordem numérica: local via `mysql … -e "source sql/migrations/NNN_….sql"`; Hostinger via phpMyAdmin → Importar;
- conferir o que já foi aplicado com `SELECT * FROM migracoes ORDER BY versao;`;
- MySQL não faz DDL em transação: se uma migração falhar no meio, corrigir manualmente o estado e registrar o ocorrido na entrega.

## 11. Gates de qualidade obrigatórios

Toda alteração de código deve passar, antes de revisão, pelos comandos aplicáveis, com o resultado real registrado na entrega:

| Camada | Ferramenta | Comando |
|---|---|---|
| JavaScript/TypeScript | **ESLint** | `npm run lint` |
| Linhas por arquivo | Verificador de fonte | `npm run check:lines` (m?ximo 350 linhas) |
| PHP — estilo | **PHP_CodeSniffer** (PSR-12) | `composer cs` |
| PHP — análise estática | **PHPStan** | `composer stan` |
| PHP — testes | **PHPUnit** | `composer test` |

(Os scripts do Composer chamam os binários de `public_html/vendor/bin/`; configuração em `phpcs.xml`, `phpstan.neon` e `phpunit.xml` na raiz.)

Regras:

- ESLint termina **sem erros nem avisos**; não introduzir `eslint-disable` sem justificativa explícita;
- PHPStan em nível progressivo (começar em `level 5`, subir quando o código estabilizar);
- **teste obrigatório** para a lógica não-trivial: `slots.php` (geração de horários + detecção de conflito) tem teste PHPUnit desde a Fase 4 — é onde um bug vira overbooking;
- para mudanças sem código, rodar só os gates afetados e declarar os demais como não aplicáveis;
- falha de ambiente não é sucesso: registrar o bloqueio e como reproduzi-lo.

> `ponytail:` sem passo de build no deploy (Hostinger serve os arquivos direto). Os gates rodam **localmente** antes do upload. Ferramentas de qualidade entram via Composer como `require-dev`; a **única dependência de produção é o PHPMailer** (2FA) — no deploy, subir `vendor/` gerado com `composer install --no-dev`.

## 12. O que nunca deve ser feito

- não criar regra de negócio, banco ou CRUD sem aprovação;
- não concatenar entrada em SQL;
- não guardar segredo no repositório;
- não expor telefone/e-mail de cliente em página pública, URL ou log;
- não alterar o padrão visual aprovado sem avisar (ver `DESIGN-SYSTEM.md`);
- não adicionar dependência pesada sem justificativa;
- não fazer commit/push sem a aprovação dupla (§1.1);
- não reintroduzir Java/VPS/SPA sem decisão registrada no Plano Mestre.

## 13. Checklist antes de finalizar tarefa

- A alteração está dentro do escopo pedido?
- Algum documento (`ARCHITECTURE`/`API`/`DESIGN-SYSTEM`/`PLANO_MESTRE`) precisa ser atualizado?
- O visual aprovado foi preservado?
- Houve mudança no banco? Ela está numa **nova** migração em `sql/migrations/`, sem editar migração já aplicada?
- Toda query usa prepared statements?
- Toda entrada foi validada no servidor?
- CSRF validado nos POSTs?
- Nenhum segredo ou dado de cliente foi exposto ou logado?
- Acessibilidade e responsividade preservadas?
- ESLint passou sem erros/avisos?
- PHPCS/PHPStan passaram?
- PHPUnit passou (incluindo o teste de `slots.php`), ou o bloqueio foi registrado sem expor segredos?
# Complemento de regras — Fase 5 (2026-10-07)

R1–R6 corrigidos sem reduzir os gates ou afrouxar CSP; resultado atual em `CORRECOES-FASE-5.md`. Diagnóstico não deve gravar mensagem bruta de exceção; usa identificador, tipo, nome do arquivo e linha. Configuração de destino privado e retenção permanece tarefa da preparação de deploy.

Ampliações aprovadas: uploads restritos a JPG/PNG/WEBP até 5 MB, nomes aleatórios e categorias `servicos`/`profissionais`; exclusão física somente em uploads sem uso, com caminho validado e CSRF. Arquivos de design seguem `assets/img/`, enquanto uploads não devem ser sobrescritos pelo deploy.

Exceção de contato aprovada: no painel autenticado, o telefone do cliente pode compor link HTTPS `wa.me` para contato manual. Não expor contatos publicamente, em filtros internos ou logs; o link não envia mensagem automaticamente.

O resumo financeiro soma somente concluídos e deve permanecer rotulado como estimativa pelo preço atual do catálogo. Serviços sem preço não podem ser tratados como receita conhecida. O novo calendário não cria papel/login próprio de profissional.

Gates continuam obrigatórios: não reduzir limite de 350 linhas, severidade do PHPCS ou CSP para fechar a fase. A revisão encontrou violações e integrações pendentes em [REVISAO-FASE-5.md](REVISAO-FASE-5.md); nenhuma exceção aos gates foi aprovada.
