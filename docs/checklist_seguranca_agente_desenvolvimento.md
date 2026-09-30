# Checklist Completo de Segurança para Agente de Desenvolvimento

Este checklist deve ser usado pelo agente de desenvolvimento para **auditar, corrigir, implementar e testar** a segurança de todo o site, incluindo frontend, backend, APIs, banco de dados, autenticação, autorização, uploads, integrações externas e recursos de IA.

---

# 1. Secrets, chaves e variáveis de ambiente

- [ ] Verificar se existem **API Keys, tokens, senhas, secrets ou credenciais expostas no frontend**.
- [ ] Nunca colocar secrets em:
  - JavaScript entregue ao navegador;
  - HTML;
  - código React/Vue/Next client-side;
  - arquivos públicos;
  - bundles;
  - comentários;
  - URLs.
- [ ] Secrets privados devem existir somente no backend.
- [ ] Usar variáveis de ambiente para credenciais.
- [ ] Garantir que arquivos `.env` estejam no `.gitignore`.
- [ ] Nunca versionar `.env`.
- [ ] Verificar se secrets já foram enviados para o Git.
- [ ] Caso tenham sido expostos, **revogar e gerar novas credenciais**.
- [ ] Remover secrets também do histórico do Git.
- [ ] Separar credenciais de:
  - desenvolvimento;
  - staging;
  - produção.
- [ ] Aplicar princípio do **menor privilégio** às API Keys.
- [ ] Não usar service keys ou admin keys no frontend.
- [ ] Configurar rotação periódica de secrets.

---

# 2. Autenticação

- [ ] Toda autenticação deve ser validada pelo servidor.
- [ ] Nunca confiar apenas em validações feitas no frontend.
- [ ] Endpoints privados devem verificar sessão/token.
- [ ] Validar:
  - autenticidade do token;
  - validade;
  - expiração;
  - emissor;
  - audiência, quando aplicável.
- [ ] Não aceitar tokens expirados.
- [ ] Implementar logout invalidando ou encerrando a sessão adequadamente.
- [ ] Impedir reutilização indevida de tokens.
- [ ] Utilizar tokens de curta duração quando aplicável.
- [ ] Implementar refresh tokens de maneira segura.
- [ ] Proteger fluxos de recuperação de senha.
- [ ] Tokens de redefinição de senha devem:
  - expirar;
  - ser de uso único;
  - ser imprevisíveis.
- [ ] Não revelar se determinado e-mail possui conta.
- [ ] Implementar proteção contra enumeração de usuários.

---

# 3. Senhas

- [ ] Nunca armazenar senha em texto puro.
- [ ] Nunca registrar senhas em logs.
- [ ] Nunca retornar senhas em APIs.
- [ ] Usar algoritmos apropriados de hash, como:
  - Argon2id;
  - bcrypt;
  - scrypt.
- [ ] Utilizar salt adequado.
- [ ] Não utilizar MD5 ou SHA puro para armazenamento de senhas.
- [ ] Exigir senha com tamanho mínimo adequado.
- [ ] Permitir senhas longas.
- [ ] Evitar regras artificiais excessivas que reduzam usabilidade.
- [ ] Permitir uso de gerenciadores de senha.
- [ ] Oferecer MFA/2FA quando o sistema justificar.

---

# 4. Autorização

- [ ] Confirmar que um usuário autenticado só acessa recursos aos quais possui permissão.
- [ ] Toda autorização deve acontecer no servidor.
- [ ] Nunca confiar no ID enviado pelo frontend.
- [ ] Nunca confiar em campos como:
  - `userId`;
  - `ownerId`;
  - `role`;
  - `isAdmin`;
  - `accountId`.
- [ ] Obter identidade do usuário através da sessão/token.
- [ ] Aplicar política **deny by default**.
- [ ] Implementar controle por:
  - usuário;
  - organização;
  - tenant;
  - função;
  - recurso.
- [ ] Verificar permissões em toda operação:
  - GET;
  - POST;
  - PUT;
  - PATCH;
  - DELETE.

---

# 5. IDOR / BOLA

Verificar endpoints como:

```text
/users/123
/orders/456
/documents/789
/projects/999
/api/profile?id=123
```

- [ ] Alterar IDs manualmente e verificar se dados de outros usuários ficam acessíveis.
- [ ] Nunca liberar recurso apenas porque o usuário conhece o ID.
- [ ] Verificar propriedade do recurso no backend.
- [ ] Garantir isolamento entre usuários.
- [ ] Garantir isolamento entre organizações/tenants.
- [ ] Aplicar Row-Level Security quando disponível.
- [ ] Evitar consultas como:

```sql
SELECT * FROM orders WHERE id = ?
```

quando deveria existir também verificação do proprietário:

```sql
SELECT *
FROM orders
WHERE id = ?
AND user_id = ?
```

---

# 6. Row-Level Security — RLS

- [ ] Ativar RLS em tabelas com dados privados.
- [ ] Não depender apenas do frontend para filtrar registros.
- [ ] Criar políticas para:
  - leitura;
  - inserção;
  - atualização;
  - exclusão.
- [ ] Testar acesso com usuários diferentes.
- [ ] Verificar se usuários anônimos conseguem acessar registros privados.
- [ ] Revisar tabelas novas para garantir que RLS não ficou esquecido.

---

# 7. SQL Injection

- [ ] Nunca concatenar entrada do usuário em SQL.
- [ ] Usar consultas parametrizadas.
- [ ] Preferir ORM/query builders seguros.
- [ ] Validar tipos antes das queries.
- [ ] Validar IDs.
- [ ] Não permitir comandos SQL vindos do usuário.
- [ ] Revisar filtros, buscas e ordenações dinâmicas.

Evitar:

```js
"SELECT * FROM users WHERE email = '" + email + "'"
```

Usar:

```js
db.query(
  "SELECT * FROM users WHERE email = ?",
  [email]
)
```

---

# 8. Validação de entradas

Toda entrada externa deve ser considerada não confiável.

Validar:

- [ ] formulários;
- [ ] query parameters;
- [ ] JSON;
- [ ] headers;
- [ ] cookies;
- [ ] uploads;
- [ ] WebSockets;
- [ ] dados recebidos de APIs externas.

Verificar:

- [ ] tipo;
- [ ] tamanho;
- [ ] formato;
- [ ] intervalo;
- [ ] caracteres permitidos;
- [ ] campos obrigatórios;
- [ ] valores permitidos.

Preferir validação por **allowlist**.

Exemplo:

```text
role ∈ [user, editor, admin]
```

---

# 9. Mass Assignment / adulteração de campos

Evitar situações em que o cliente envie:

```json
{
  "name": "João",
  "role": "admin",
  "isAdmin": true,
  "balance": 999999
}
```

- [ ] Definir explicitamente campos que podem ser alterados.
- [ ] Nunca repassar todo o body diretamente para banco/ORM.
- [ ] Bloquear alteração de:
  - role;
  - ownerId;
  - accountId;
  - permissions;
  - balance;
  - status administrativo;
  - flags internas.

---

# 10. Cross-Site Scripting — XSS

- [ ] Escapar conteúdo controlado pelo usuário.
- [ ] Nunca inserir HTML não confiável diretamente na página.
- [ ] Evitar:
  - `innerHTML`;
  - `dangerouslySetInnerHTML`;
  - `v-html`;
  sem sanitização.
- [ ] Sanitizar HTML quando conteúdo rico for necessário.
- [ ] Validar URLs fornecidas pelo usuário.
- [ ] Bloquear URLs como:

```text
javascript:
data:
```

quando não forem necessárias.

- [ ] Configurar Content Security Policy.

---

# 11. Content Security Policy — CSP

Implementar cabeçalho:

```text
Content-Security-Policy
```

- [ ] Restringir scripts.
- [ ] Restringir iframes.
- [ ] Restringir fontes.
- [ ] Restringir imagens quando possível.
- [ ] Evitar `unsafe-inline`.
- [ ] Evitar `unsafe-eval`.
- [ ] Utilizar nonce/hash quando necessário.

---

# 12. CSRF

Para aplicações que utilizam autenticação por cookies:

- [ ] Implementar proteção CSRF.
- [ ] Utilizar cookies `SameSite`.
- [ ] Validar origem da requisição quando apropriado.
- [ ] Utilizar CSRF tokens para operações sensíveis.
- [ ] Nunca executar alterações importantes via GET.

---

# 13. Cookies

Cookies de autenticação devem usar:

```text
HttpOnly
Secure
SameSite
```

- [ ] Cookies sensíveis devem ser `HttpOnly`.
- [ ] Cookies sensíveis devem usar `Secure`.
- [ ] Definir `SameSite=Lax` ou `Strict` quando possível.
- [ ] Configurar domínio corretamente.
- [ ] Definir tempo de expiração adequado.
- [ ] Não armazenar dados sensíveis diretamente no cookie.
- [ ] Não armazenar senha em cookie.

---

# 14. SSRF

Validar funcionalidades onde o servidor acessa URLs fornecidas pelo usuário.

Exemplos:

```text
/import?url=
fetch-url
webhook tester
preview
image proxy
PDF generator
```

- [ ] Não permitir acesso livre a URLs arbitrárias.
- [ ] Criar allowlist de hosts.
- [ ] Bloquear localhost.
- [ ] Bloquear IPs privados, quando aplicável.
- [ ] Bloquear metadata endpoints de cloud.
- [ ] Bloquear redirecionamentos perigosos.
- [ ] Resolver e validar DNS/IP adequadamente.

Proteger contra destinos como:

```text
127.0.0.1
localhost
169.254.169.254
10.0.0.0/8
172.16.0.0/12
192.168.0.0/16
```

quando não forem necessários.

---

# 15. Prompt Injection

Se o site possuir IA/LLM:

- [ ] Tratar entrada do usuário como conteúdo não confiável.
- [ ] Não concatenar instruções do usuário diretamente às instruções privilegiadas.
- [ ] Separar:
  - system prompt;
  - dados;
  - instruções do usuário.
- [ ] Nunca enviar secrets para o modelo.
- [ ] Nunca disponibilizar API Keys ao modelo.
- [ ] Limitar ferramentas que o agente pode utilizar.
- [ ] Implementar permissões por ferramenta.
- [ ] Validar parâmetros antes de executar ferramentas.
- [ ] Não confiar automaticamente em comandos produzidos pelo LLM.
- [ ] Solicitar confirmação para ações críticas.
- [ ] Impedir que conteúdo externo redefina instruções de segurança.

---

# 16. Vazamento de dados por IA

- [ ] Não enviar ao modelo informações que o usuário não deveria acessar.
- [ ] Aplicar autorização antes de recuperar dados para RAG.
- [ ] Filtrar documentos por usuário/tenant antes de enviar ao LLM.
- [ ] Impedir acesso cross-tenant.
- [ ] Não expor system prompts sem necessidade.
- [ ] Não colocar secrets dentro de prompts.

---

# 17. Rate Limiting

Aplicar limites principalmente em:

- [ ] login;
- [ ] cadastro;
- [ ] recuperação de senha;
- [ ] envio de e-mail;
- [ ] SMS;
- [ ] geração de IA;
- [ ] busca;
- [ ] uploads;
- [ ] endpoints caros.

Considerar limites por:

```text
IP
usuário
sessão
API Key
endpoint
```

---

# 18. Brute Force

- [ ] Limitar tentativas de login.
- [ ] Aplicar atraso progressivo.
- [ ] Implementar bloqueios temporários.
- [ ] Usar CAPTCHA quando comportamento suspeito for detectado.
- [ ] Alertar sobre tentativas anormais.
- [ ] Não revelar se o usuário existe.

---

# 19. Bots

- [ ] Implementar proteção contra bots em formulários públicos.
- [ ] Usar CAPTCHA/Turnstile quando apropriado.
- [ ] Implementar honeypots em formulários quando útil.
- [ ] Detectar volumes anormais de requisições.
- [ ] Bloquear automação maliciosa.

---

# 20. DoS / DDoS

- [ ] Implementar rate limiting.
- [ ] Limitar tamanho de requests.
- [ ] Limitar tamanho de uploads.
- [ ] Limitar tempo de processamento.
- [ ] Aplicar timeout.
- [ ] Aplicar limite de concorrência.
- [ ] Cachear respostas quando apropriado.
- [ ] Colocar CDN/WAF quando possível.
- [ ] Proteger endpoints computacionalmente caros.
- [ ] Limitar chamadas a APIs externas.

---

# 21. Uploads de arquivos

- [ ] Limitar tipos permitidos.
- [ ] Limitar tamanho.
- [ ] Verificar MIME real.
- [ ] Não confiar somente na extensão.
- [ ] Gerar nome aleatório para arquivo.
- [ ] Nunca utilizar diretamente o nome enviado pelo usuário.
- [ ] Evitar armazenar uploads dentro da pasta executável da aplicação.
- [ ] Bloquear execução de arquivos enviados.
- [ ] Sanitizar SVG.
- [ ] Inspecionar arquivos quando necessário.
- [ ] Bloquear extensões perigosas.
- [ ] Exigir autorização para download de arquivos privados.

---

# 22. Path Traversal

Proteger endpoints que recebem nomes/caminhos:

```text
/download?file=
/images/:filename
/files/:path
```

Bloquear ataques como:

```text
../../../../etc/passwd
```

- [ ] Nunca concatenar caminhos diretamente.
- [ ] Normalizar paths.
- [ ] Criar allowlist.
- [ ] Garantir que arquivos acessados estejam dentro da pasta permitida.

---

# 23. CORS

- [ ] Não usar:

```text
Access-Control-Allow-Origin: *
```

em APIs privadas autenticadas.

- [ ] Configurar origens permitidas explicitamente.
- [ ] Não refletir automaticamente qualquer Origin.
- [ ] Restringir métodos.
- [ ] Restringir headers.
- [ ] Configurar credenciais corretamente.

---

# 24. HTTPS

- [ ] Toda aplicação em produção deve utilizar HTTPS.
- [ ] Redirecionar HTTP → HTTPS.
- [ ] Bloquear conteúdo misto.
- [ ] Configurar certificados válidos.
- [ ] Ativar HSTS.

Exemplo:

```text
Strict-Transport-Security
```

---

# 25. Headers de segurança

Verificar:

```text
Content-Security-Policy
Strict-Transport-Security
X-Content-Type-Options
Referrer-Policy
Permissions-Policy
```

- [ ] Configurar CSP.
- [ ] Configurar HSTS.
- [ ] Usar:

```text
X-Content-Type-Options: nosniff
```

- [ ] Definir Referrer Policy.
- [ ] Definir Permissions Policy.
- [ ] Proteger framing com CSP `frame-ancestors`.

---

# 26. Clickjacking

- [ ] Impedir que páginas sensíveis sejam carregadas dentro de iframes externos.
- [ ] Usar:

```text
Content-Security-Policy: frame-ancestors
```

ou mecanismos equivalentes.

---

# 27. Open Redirect

Verificar parâmetros como:

```text
redirect=
next=
return=
callback=
```

- [ ] Não redirecionar livremente para URL fornecida pelo usuário.
- [ ] Usar allowlist.
- [ ] Preferir caminhos internos.

---

# 28. APIs

Cada endpoint deve verificar:

```text
Autenticação
Autorização
Validação
Rate limit
Escopo de dados retornados
```

- [ ] Retornar somente dados necessários.
- [ ] Nunca retornar campos internos desnecessários.
- [ ] Não usar:

```text
SELECT *
```

sem necessidade.

Exemplo ruim:

```json
{
  "id": 5,
  "name": "João",
  "passwordHash": "...",
  "internalNotes": "...",
  "secretToken": "..."
}
```

Retornar somente:

```json
{
  "id": 5,
  "name": "João"
}
```

---

# 29. Enumeração de usuários

Evitar respostas diferentes como:

```text
Usuário não existe
```

e

```text
Senha incorreta
```

Preferir:

```text
Credenciais inválidas
```

- [ ] Aplicar mesmo cuidado em recuperação de senha.
- [ ] Evitar diferenças significativas de timing.

---

# 30. Rotas administrativas

Procurar rotas como:

```text
/admin
/dashboard/admin
/api/admin
/internal
/debug
/dev
/staging
```

- [ ] Não confiar em esconder a URL.
- [ ] Todas devem exigir autorização.
- [ ] Verificar role/permissão no servidor.
- [ ] Remover rotas de debug de produção.
- [ ] Desativar ferramentas administrativas desnecessárias.

---

# 31. Erros e exceptions

Nunca mostrar ao usuário:

```text
stack trace
query SQL
senha
token
API Key
path interno
variáveis de ambiente
configurações do servidor
```

- [ ] Usar mensagens genéricas em produção.
- [ ] Registrar detalhes somente nos logs internos.
- [ ] Não retornar stack trace pela API.

---

# 32. Logs

- [ ] Registrar eventos de segurança.
- [ ] Nunca registrar:
  - senha;
  - cartão;
  - token completo;
  - cookies;
  - secrets.
- [ ] Registrar:
  - login;
  - login falho;
  - alterações administrativas;
  - mudança de senha;
  - mudança de permissões;
  - ações críticas.
- [ ] Implementar rotação/retenção de logs.

---

# 33. Dados sensíveis

- [ ] Identificar dados sensíveis armazenados.
- [ ] Criptografar dados quando necessário.
- [ ] Criptografar dados em trânsito.
- [ ] Reduzir coleta de dados.
- [ ] Não armazenar dados sem necessidade.
- [ ] Implementar políticas de retenção.
- [ ] Proteger backups.

---

# 34. Banco de dados

- [ ] Banco não deve ficar diretamente exposto à internet quando não necessário.
- [ ] Restringir IP/rede.
- [ ] Usar usuário com privilégios mínimos.
- [ ] Backend não deve usar usuário root/admin desnecessariamente.
- [ ] Separar usuário de leitura/escrita quando apropriado.
- [ ] Fazer backups.
- [ ] Testar restauração dos backups.
- [ ] Criptografar conexões com o banco.

---

# 35. Dependências

Executar auditorias periódicas.

Para Node.js:

```bash
npm audit
```

ou ferramentas equivalentes.

- [ ] Identificar dependências vulneráveis.
- [ ] Atualizar dependências críticas.
- [ ] Remover pacotes não utilizados.
- [ ] Verificar dependências abandonadas.
- [ ] Manter lockfile.
- [ ] Não instalar pacotes desconhecidos sem revisão.

---

# 36. Supply Chain

- [ ] Revisar dependências novas.
- [ ] Fixar versões quando apropriado.
- [ ] Verificar integridade.
- [ ] Proteger CI/CD.
- [ ] Não colocar secrets em workflows públicos.
- [ ] Restringir permissões de GitHub Actions/GitLab CI.

---

# 37. Frontend

O agente deve procurar:

```text
API_KEYS
SECRET
TOKEN
PASSWORD
SERVICE_ROLE
PRIVATE_KEY
```

em:

```text
src/
public/
dist/
build/
.next/
```

- [ ] Inspecionar bundles produzidos.
- [ ] Verificar DevTools → Network.
- [ ] Verificar se APIs retornam informações desnecessárias.
- [ ] Não tratar frontend como ambiente seguro.

---

# 38. Cache

- [ ] Não cachear páginas privadas publicamente.
- [ ] Não armazenar dados sensíveis em cache compartilhado.
- [ ] Configurar headers adequados.
- [ ] Evitar cache de respostas autenticadas quando inadequado.

---

# 39. Webhooks

- [ ] Validar assinatura do webhook.
- [ ] Verificar timestamp.
- [ ] Proteger contra replay.
- [ ] Não confiar no conteúdo apenas porque chegou ao endpoint.
- [ ] Implementar idempotência.

---

# 40. WebSockets

Se houver WebSockets:

- [ ] Autenticar conexão.
- [ ] Verificar autorização por evento.
- [ ] Validar mensagens.
- [ ] Limitar tamanho.
- [ ] Aplicar rate limit.
- [ ] Impedir inscrição em canais de outros usuários.

---

# 41. Controle de sessão

- [ ] Renovar sessão após login.
- [ ] Impedir session fixation.
- [ ] Encerrar sessões após troca de senha, quando apropriado.
- [ ] Oferecer logout de outros dispositivos.
- [ ] Definir tempo máximo de sessão.
- [ ] Invalidar sessão quando conta for bloqueada.

---

# 42. Segurança de endpoints DELETE/UPDATE

Antes de:

```text
DELETE
PUT
PATCH
```

verificar:

```text
usuário autenticado?
↓
possui permissão?
↓
recurso pertence ao usuário?
↓
dados são válidos?
↓
operação é permitida?
```

---

# 43. Privilege Escalation

Testar se um usuário comum consegue:

```text
role=admin
isAdmin=true
permissions=["*"]
accountType=enterprise
```

- [ ] Bloquear alterações de privilégios pelo próprio usuário.
- [ ] Verificar permissões sempre no servidor.

---

# 44. Segurança multi-tenant

Se houver organizações/clientes diferentes:

- [ ] Toda consulta deve respeitar `tenant_id`.
- [ ] Usuário de organização A nunca pode consultar organização B.
- [ ] Aplicar isolamento também em:
  - arquivos;
  - logs;
  - cache;
  - buscas;
  - IA/RAG;
  - relatórios.

---

# 45. Testes negativos obrigatórios

O agente não deve testar somente o fluxo correto.

Também deve tentar:

```text
IDs inválidos
IDs de outros usuários
campos extras
payload vazio
payload gigante
HTML
JavaScript
SQL
URLs internas
arquivos malformados
requisições repetidas
tokens expirados
tokens inexistentes
usuário sem permissão
```

---

# 46. Testes automatizados de segurança

Criar testes para:

- [ ] usuário não autenticado;
- [ ] usuário sem permissão;
- [ ] acesso cross-user;
- [ ] acesso cross-tenant;
- [ ] inputs inválidos;
- [ ] rate limit;
- [ ] uploads inválidos;
- [ ] campos protegidos;
- [ ] tokens expirados;
- [ ] tentativa de alteração de role.

---

# 47. Scanner de código

Adicionar ao pipeline ferramentas para analisar:

```text
SAST
Dependency Scanning
Secret Scanning
```

Exemplos de categorias:

```text
Secrets
SQL Injection
XSS
unsafe eval
command injection
dependências vulneráveis
```

---

# 48. Command Injection

Pesquisar código como:

```js
exec()
spawn()
system()
shell_exec()
```

- [ ] Nunca concatenar entrada do usuário em comando de shell.
- [ ] Preferir APIs que não chamam shell.
- [ ] Usar allowlist quando necessário.

---

# 49. NoSQL Injection

Se houver MongoDB/NoSQL:

Não aceitar estruturas arbitrárias como:

```json
{
  "email": {
    "$ne": null
  }
}
```

- [ ] Validar schema.
- [ ] Bloquear operadores inesperados.
- [ ] Não repassar request diretamente à query.

---

# 50. GraphQL

Se utilizado:

- [ ] Autenticar e autorizar resolvers.
- [ ] Limitar profundidade.
- [ ] Limitar complexidade.
- [ ] Aplicar rate limiting.
- [ ] Restringir introspection em produção se necessário.
- [ ] Evitar exposição excessiva de dados.

---

# Ordem de execução para o agente

```text
1. Mapear arquitetura
2. Mapear frontend
3. Mapear backend
4. Mapear APIs
5. Mapear banco
6. Mapear autenticação
7. Mapear autorização
8. Procurar secrets
9. Verificar IDOR/BOLA
10. Verificar SQL/NoSQL Injection
11. Verificar XSS
12. Verificar SSRF
13. Verificar CSRF
14. Verificar Prompt Injection
15. Verificar uploads
16. Verificar rate limiting
17. Verificar cookies/sessões
18. Verificar CORS
19. Verificar headers
20. Verificar logs/erros
21. Verificar dependências
22. Corrigir vulnerabilidades
23. Criar testes
24. Rodar testes novamente
25. Gerar relatório final
```

---

# Regra principal para o agente

> Analise toda a aplicação considerando frontend, backend, APIs, banco de dados, autenticação, autorização, uploads, integrações externas e recursos de IA. Não considere uma funcionalidade segura apenas porque existe validação no frontend. Toda validação, autenticação e autorização crítica deve ser aplicada no servidor. Procure vulnerabilidades de secrets expostos, SQL Injection, NoSQL Injection, XSS, CSRF, SSRF, IDOR/BOLA, Mass Assignment, Path Traversal, Open Redirect, Command Injection, Prompt Injection, privilege escalation, enumeração de usuários, exposição de rotas administrativas, vazamento de dados, armazenamento inseguro de senhas, falhas de sessão, CORS incorreto, uploads inseguros, DoS, ausência de rate limiting e dependências vulneráveis. Corrija os problemas encontrados sem quebrar funcionalidades existentes e crie testes para impedir regressões.

## Regra de correção

> Não apenas informe que encontrou uma vulnerabilidade. Localize o arquivo e trecho responsável, explique o risco, implemente a correção, teste a correção e confirme que a funcionalidade original continua funcionando.

---

# Relatório final esperado

Ao concluir a auditoria, o agente deve gerar um relatório contendo:

- [ ] vulnerabilidade encontrada;
- [ ] criticidade;
- [ ] arquivo afetado;
- [ ] trecho ou função afetada;
- [ ] explicação do risco;
- [ ] correção aplicada;
- [ ] teste criado;
- [ ] resultado do teste;
- [ ] possíveis impactos;
- [ ] pendências;
- [ ] recomendações futuras.
