# Páginas de erro humanizadas e manutenção

**Data:** 09/10/2026
**Status:** em revisão após aprovação do desenho

## Contexto

O site já possui handlers mínimos para `403`, `404` e `500`, configurados no
`.htaccess`. Eles não seguem a identidade visual atual; a página `500` ainda
carrega Bootstrap de CDN. O projeto também possui um handler global de
exceções PHP com registro e identificador de atendimento, mas não há uma
política para manutenção planejada nem conteúdo próprio para erros de gateway.

O objetivo é responder a falhas com textos claros, acolhedores e úteis, sem
expor detalhes técnicos, sem criar uma segunda rota pública e sem modificar as
respostas JSON das APIs.

## Decisão aprovada

As páginas serão versionadas no repositório e renderizadas localmente pelo
Apache/PHP. O `.htaccess` apontará os erros suportados para os arquivos em
`/errors`, e o atual handler PHP continuará sendo a origem da página `500`.

Não será adotada uma solução exclusiva do hPanel: a documentação da Hostinger
permite configurar páginas de erro pelo painel, mas o conteúdo mantido apenas
ali não acompanha o Git. O hPanel ficará como mecanismo complementar para
eventuais respostas produzidas antes de a requisição chegar ao Apache/PHP.

## Páginas públicas

| Status | Título e texto-base | Ações | Quando aparece |
| --- | --- | --- | --- |
| `403` | **Este espaço é reservado.** “Você chegou a uma área que não está aberta para visitas. Volte para um espaço de cuidado ou entre pela área administrativa, se ela for sua.” | Início; acesso administrativo | Tentativa de abrir arquivos ou diretórios protegidos pelo Apache. |
| `404` | **Parece que este caminho se perdeu.** “Talvez esta página tenha mudado de lugar; seu momento de pausa continua por aqui.” | Início; agendar | URL inválida, link quebrado ou recurso público inexistente. |
| `500` | **Nossa casa fez uma pausa inesperada.** “Já registramos o ocorrido. Respire fundo e tente novamente em alguns instantes.” | Tentar novamente; início | Exceção não tratada do PHP ou erro interno do Apache que alcance o handler. Exibe identificador de atendimento quando disponível. |
| `502` | **A ponte até o nosso espaço falhou por um instante.** “O site recebeu uma resposta incompleta. Uma nova tentativa costuma resolver.” | Tentar novamente; início | Falha entre servidor web e aplicação, quando produzida pelo servidor local. |
| `503` | **Estamos preparando o espaço para receber você.** “O site está em uma breve pausa de manutenção. Volte em alguns instantes.” | Início; redes sociais | Manutenção ativada explicitamente pela configuração da aplicação ou indisponibilidade local mapeada pelo Apache. Inclui `Retry-After`. |
| `504` | **O atendimento digital demorou mais que o normal.** “A resposta levou mais tempo do que esperávamos. Tente novamente daqui a pouco.” | Tentar novamente; início | Tempo esgotado na comunicação, quando a resposta puder ser interceptada localmente. |

O texto evita culpar o visitante, códigos técnicos como destaque principal e
promessas de prazo que não possam ser cumpridas. O código HTTP ainda aparecerá
em leitura secundária, para ajudar o suporte sem tornar a tela fria.

## Padrão visual e acessibilidade

- Um único template interno renderizará todas as páginas, recebendo apenas
  status, título, texto, ações e identificador opcional.
- Um CSS dedicado, sem Bootstrap, reutilizará as cores, tipografia, bordas e
  espaçamentos do site. O layout será centrado no desktop e ocupará a tela com
  margens confortáveis no celular.
- A hierarquia terá um único `h1`, conteúdo em `main`, foco visível, contraste
  suficiente, botões de tamanho acessível e nenhuma dependência de JavaScript.
- As páginas não consultarão banco, sessão, serviços externos ou arquivos de
  layout comuns; assim, continuam renderizáveis durante uma falha parcial.
- A página `500` escapará o identificador de atendimento e nunca mostrará stack
  trace, caminho de arquivo, credenciais, SQL ou detalhes da exceção.

## Regras de servidor e aplicação

### Apache e Hostinger

O `.htaccess` manterá as regras de HTTPS e bloqueio de diretórios existentes e
ganhará `ErrorDocument` para `502`, `503` e `504`, além de apontar `403`, `404`
e `500` para o novo template. O status original será preservado mesmo quando o
arquivo de erro for chamado diretamente pelo Apache.

Uma página definida no repositório cobre erros gerados pelo Apache ou pela
aplicação. Se o proxy, CDN ou a infraestrutura da Hostinger produzir `502`,
`503` ou `504` antes de encaminhar a requisição ao site, ela pode responder com
sua própria página. O mesmo conteúdo será documentado para cadastro opcional em
**hPanel → Error Pages**, sem depender dessa cópia para o funcionamento local.

### Manutenção planejada

Será adicionada uma chave booleana não secreta em `config.php` e
`config.example.php`, inicialmente desativada:

```php
'maintenance_mode' => false,
```

Um guard leve será executado nas entradas públicas HTML antes de qualquer acesso
ao banco. Com o modo ativo, ele responderá `503`, enviará `Retry-After: 3600` e
renderizará a página de manutenção. A área administrativa (`/admin`) continuará
acessível para que a responsável consiga desativar a manutenção; a página de
login também permanecerá disponível.

Os endpoints JSON não receberão HTML. Durante manutenção, APIs responderão
`503` com JSON no formato já usado pelo projeto, incluindo uma mensagem pública
e `Retry-After`; o JavaScript poderá informar a indisponibilidade dentro do
painel de agendamento.

### Autorização, validação e APIs

- O bloqueio atual de `config.php`, `.env`, `includes`, `vendor`, `sql`, `tests`,
  `bin`, `docs` e `.git` continuará respondendo `403` pelo Apache.
- `requireAdmin()` continuará redirecionando uma pessoa não autenticada para
  `/admin/login.php`. Não será trocado por `403`, pois login é o fluxo esperado.
- Falhas de CSRF em formulários, validações `400`/`422`, conflitos `409`, método
  não permitido `405` e limites `429` continuarão nas próprias telas ou JSON
  que hoje explicam a ação corretiva. Não serão transformados em páginas de
  erro genéricas.
- Não será criada uma página `401`: não há área pública com autenticação HTTP
  Basic/Bearer; o painel já usa sessão e redirecionamento de login.

## Arquivos previstos

| Arquivo | Responsabilidade |
| --- | --- |
| `errors/error-page.php` | Template resiliente e catálogo interno dos textos por status. Não acessível diretamente. |
| `errors/403.php`, `404.php`, `500.php`, `502.php`, `503.php`, `504.php` | Entradas pequenas: fixam o status correto e chamam o template. |
| `includes/errors.php` | Reutilizar o catálogo/renderer no handler global e manter logging com request ID. |
| `includes/maintenance.php` | Guard de manutenção para HTML e resposta JSON segura para APIs. |
| `.htaccess` | Mapeamento `ErrorDocument` e bloqueio de acesso direto ao template interno. |
| `config.example.php` | Documentar `maintenance_mode`, inicialmente `false`. |
| `static/errors.css` | Estilo responsivo e independente das páginas de erro. |
| `tests/ErrorPagesTest.php` | Cobertura das regras, status, segurança e manutenção. |

## Critérios de aceitação

1. Abrir um caminho inexistente retorna `404` e a página criativa correspondente.
2. Abrir um arquivo/diretório protegido retorna `403`, sem revelar seu conteúdo.
3. Uma exceção não tratada retorna `500`, registra o erro e não revela detalhes.
4. Cada uma das páginas `502`, `503` e `504` responde com o código correto
   quando acessada pelo servidor local ou pelo handler correspondente.
5. Com `maintenance_mode=true`, home e agendamento retornam `503` antes do
   banco; `/admin/login.php` e o painel autenticado continuam acessíveis.
6. APIs em manutenção retornam JSON `503`, nunca HTML.
7. `503` inclui `Retry-After`; as demais respostas não são cacheadas como
   conteúdo público permanente.
8. O CSS não importa Bootstrap, bibliotecas ou fontes externas e é utilizável
   em desktop e celular.
9. O fluxo normal de agendamento, APIs e painel administrativo continua sem
   regressões quando a manutenção está desativada.
10. O comportamento local é verificado em Apache na porta `8080`; antes do
    deploy, os equivalentes de infraestrutura poderão ser cadastrados no hPanel
    caso a Hostinger os substitua.

## Fora de escopo

- Monitoramento externo, alertas automáticos ou página de status pública.
- Alterar permissões de conta, papéis administrativos ou o modelo de autenticação.
- Forçar a infraestrutura da Hostinger a usar conteúdo do repositório para
  erros que ocorram antes do Apache/PHP.
- Transformar erros de uso do formulário ou APIs em páginas HTML.
