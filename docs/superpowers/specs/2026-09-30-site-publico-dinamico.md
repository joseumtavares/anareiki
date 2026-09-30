# Especificação: Site público dinâmico — Fase 3

Data: 2026-09-30
Worktree: `.worktrees/fase-3-site-dinamico`
Branch/base: `fase-3-site-dinamico` sobre `main` (`1a2312d`)

## Objetivo

Preparar `public_html/index.php` como home PHP servida pelo Apache, carregando serviços e profissionais ativos do MySQL. A experiência deve preservar a linguagem visual e os conteúdos institucionais estáticos aprovados. O código Hono permanece até a Fase 8; esta fase não inclui agendamento, CRUD administrativo ou mudança de schema.

## Escopo e comportamento

- Consultar serviços ativos, ordenados por `ordem`, depois por `nome` e `id` para desempate estável.
- Consultar profissionais ativos, ordenados por nome e `id`.
- Renderizar os dados no template PHP da home. A seção “Sobre” mantém a imagem do ambiente, as dicas e o CTA compartilhados uma vez; o conteúdo textual de perfil é uma lista dinâmica dentro da composição existente.
- Para `especialidade` e `bio` nulas ou vazias, omitir o texto correspondente. Sem `foto_url`, não renderizar retrato individual; manter uma composição estável sem imagem de exemplo. Fotos reais poderão ser enviadas pelo painel na Fase 5. Textos fictícios podem existir somente nos dados de teste, nunca no seed ou na home pública.
- Para serviços, usar os campos de apresentação existentes. Preço nulo aparece como “Consultar valor”; duração aparece em minutos. Benefícios que existiam apenas no HTML legado não viram regra nova nem dados inventados.
- Se uma lista estiver vazia, mostrar mensagem neutra dentro da seção. Se o banco falhar, mostrar aviso genérico e registrar no log sem credenciais ou dados pessoais.
- Escapar todo texto/atributo derivado do banco. `foto_url` só aceita caminho local `/static/...`; `imagem_url` dos serviços pode aceitar os caminhos locais e o host HTTPS já usado pelo site (`www.genspark.ai`). Rejeitar URLs protocol-relative, outros hosts e esquemas não HTTPS. A saída não pode criar HTML ativo ou contornar a CSP.
- Copiar os CSS e scripts públicos necessários de `public/static/` para `public_html/static/` para que Apache, com `public_html` como document root, sirva a nova home com o visual atual. Não migrar imagens hotlinkadas nesta fase; continuam na Fase 6.

## Estrutura técnica

- `public_html/includes/repositories.php`: funções de leitura PDO para os conjuntos públicos, `SELECT` explícito, prepared statements, sem dados administrativos ou de clientes.
- `public_html/index.php`: carrega `db.php` e repositories, compõe o HTML e escapa valores no ponto de saída.
- `public_html/static/`: cópias dos ativos compartilhados necessários ao layout PHP.
- `tests/`: testes PHPUnit de apresentação/escaping e resultados vazios com nomes/bios fictícios; sem arquivo de imagem sintético.

## Contratos de teste

1. Serviço/profissional inativo não aparece na consulta pública.
2. Ordem dos serviços com `ordem` empatada é determinística por nome e ID.
3. Valores de texto contendo `<script>`, aspas ou `&` são exibidos como texto escapado.
4. `foto_url` nula/vazia não gera elemento `<img>`; URL com esquema não permitido não é renderizada.
5. Especialidade/bio vazias são omitidas; listas vazias produzem estado vazio sem warnings.
6. Os dados fictícios usados pelos testes não alteram o seed nem aparecem na home fora dos testes.

## Verificação

- PHPUnit: `composer test`.
- PHP: `composer cs`, `composer stan` e `php -l` nos arquivos novos/alterados.
- Ativos e linha máxima: `npm run check:lines`; lint se algum JS for alterado.
- Revisão manual no navegador: layout desktop/mobile, dados ativos/inativos, campos nulos e estados sem dados/sem banco.
- Não executar deploy, commit ou push nesta etapa sem o fluxo de aprovação definido no Plano Mestre.

## Limites

**Faz parte:** home PHP dinâmica para serviços/profissionais, templates, segurança da saída, ativos estáticos necessários e fixtures de texto para testes.
**Fora:** schema/migrações, alterar seed da Ana, publicar fotos/textos fictícios como conteúdo real, agendamento, CRUD admin, download das imagens atuais e remoção da stack Hono (Fases 4–8).

## Revisão

- Não gerar imagens de exemplo. Perfil sem foto não recebe retrato sintético nem fallback de imagem; a moldura/coluna existente continua preservando o layout.
- Textos fictícios ficam limitados aos dados dos testes; não entram no seed nem na home fora dos testes.
- A origem de foto profissional aceita é somente local em `/static/...`. Imagens de serviços também preservam o host HTTPS atual `www.genspark.ai`, já permitido pela CSP.
