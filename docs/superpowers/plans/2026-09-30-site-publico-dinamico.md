# Site público dinâmico — Plano de implementação

> **Para agentes implementadores:** use `superpowers:executing-plans` e marque cada etapa ao concluí-la.

**Objetivo:** servir a home PHP com serviços e profissionais ativos do banco, preservando o visual aprovado e sem fotos fictícias.

**Arquitetura:** repositories recebem PDO e consultam apenas colunas públicas; helpers escapam saída; partials PHP renderizam as seções sem acoplar a página à conexão. Os ativos atuais necessários serão copiados para a raiz pública servida pelo Apache.

**Stack:** PHP 8.2+, PDO/MySQL em produção, PDO/SQLite nos testes, PHPUnit 11, CSS e JavaScript atuais.

**Especificação:** `docs/superpowers/specs/2026-09-30-site-publico-dinamico.md`

## Restrições globais

- Sem schema, migrações, alteração do seed da Ana, agendamento ou CRUD admin.
- Não criar imagens de exemplo; profissional sem `foto_url` permanece sem retrato.
- Escapar todo conteúdo dinâmico. Foto de profissional aceita apenas caminho local `/static/...`; imagens de serviço preservam caminho local ou HTTPS de `www.genspark.ai`.
- SQL fica em `includes/repositories.php`, usa colunas explícitas e nunca retorna dados de clientes.
- Manter cada PHP abaixo de 350 linhas e o visual atual.
- Jose conduz Git; commit, merge e push dependem de autorização explícita. Jose autorizou essas operações para a Task 1 em 2026-09-30.

## Foco de revisão

- Ativos ocultos: testar serviço e profissional com `ativo=0`.
- Ordem determinística: testar serviços com `ordem` empatada (nome e ID como desempate).
- XSS: testar texto com tags, aspas e entidades em texto e atributos.
- URLs: testar caminhos locais aceitos e protocol-relative, esquemas inseguros ou hosts não aprovados recusados.
- Campos vazios e indisponibilidade: renderizar listas vazias e falha do banco sem warnings nem detalhes técnicos.

---

### Task 1: Queries públicas e regras de saída

**Arquivos:** criar `public_html/includes/repositories.php`, `public_html/includes/public-view.php`, `tests/HomeRepositoryTest.php` e `tests/PublicViewTest.php`.

**Interfaces:** `listarServicosPublicos(PDO $pdo): array`; `listarProfissionaisPublicos(PDO $pdo): array`; `htmlPublico(?string $valor): string`; `urlImagemServico(?string $url): ?string`; `urlFotoProfissional(?string $url): ?string`.

- [x] Escrever testes PHPUnit com PDO SQLite em memória cobrindo filtros ativos, colunas, desempate, campos opcionais, escaping e URLs aceitas/rejeitadas.
- [x] Rodar os testes e confirmar falhas pela ausência das funções.
- [x] Implementar as duas consultas com prepared statements e colunas explícitas; implementar helpers mínimos para passar os testes.
- [x] Rodar testes focados, `composer cs` e `composer stan`.

### Task 2: Shell e apresentação da home

**Arquivos:** criar `public_html/index.php`, `public_html/includes/layout/home/head.php`, `top.php`, `about.php` e `tests/HomePageTest.php`.

- [x] Escrever teste de renderização para título, navegação e seção “Sobre” preservados; a lista de profissionais fictícios deve ser escapada e campos nulos omitidos.
- [x] Confirmar que o teste falha antes de implementar.
- [x] Ligar a home aos repositories; manter imagem do ambiente, dicas e CTA compartilhados uma vez. Inserir a lista textual dinâmica de perfis na composição aprovada, sem foto inventada.
- [x] Confirmar que foto nula/vazia não emite `<img>` de profissional e que caminho fora de `/static/` é recusado.
- [x] Rodar o teste focado, `composer cs`, `composer stan` e `php -l` nos PHPs criados.

### Task 3: Catálogo dinâmico e conteúdo complementar

**Arquivos:** criar `public_html/includes/layout/home/services.php`, `sessions-packages.php`, `availability-gallery.php` e `contact-footer.php`; atualizar `tests/HomePageTest.php`.

- [ ] Acrescentar testes para cards de serviço escapados, preço nulo “Consultar valor”, duração, ícone/placeholder e estado sem serviços.
- [ ] Confirmar falha dos testes antes da implementação.
- [ ] Renderizar serviços ativos pela ordem dos repositories, com imagem apenas quando a URL existente for aprovada pelo helper; não criar benefícios ausentes do schema.
- [ ] Preservar sessões, pacotes, disponibilidade, galeria, contato, footer e WhatsApp estáticos nos partials indicados.
- [ ] Rodar teste focado e gates PHP.

### Task 4: Ativos estáticos no DocumentRoot PHP

**Arquivos:** copiar `style-01-foundation.css`, `style-02-hero-about.css` e `style-03-services-sessions.css` de `public/static/` para `public_html/static/`; copiar `style-04-packages-hours.css`, `style-05-gallery-contact.css`, `style-06-footer-responsive.css` e `app.js` para o mesmo destino.

- [ ] Copiar os ativos sem editar/remover as origens e sem substituir `public_html/static/admin-auth.js`.
- [ ] Confirmar que os nomes e conteúdos copiados correspondem às origens.
- [ ] Rodar `npm run check:lines` e ESLint se houver ajuste em JavaScript.
- [ ] Conferir manualmente no Apache local desktop/mobile, conteúdo carregado, campos opcionais e estados vazio/erro; registrar bloqueios de configuração local.

### Task 5: Documentação e encerramento da etapa

**Arquivos:** atualizar `docs/ARCHITECTURE.md`; atualizar `docs/DESIGN-SYSTEM.md` somente se o estado sem foto exigir nova regra; atualizar `docs/HANDOFF.md` e `tasks/todo.md`.

- [ ] Rodar suíte PHPUnit, `composer cs`, `composer stan`, `npm run check:lines` e `git diff --check`.
- [ ] Revisar escopo, acessibilidade, escaping, allowlists de imagem, ausência de imagens/textos fictícios na home pública e limites de 350 linhas.
- [ ] Entregar a lista de testes manuais e aguardar aprovação antes de qualquer commit/push ou início da Fase 4.

## Checkpoint final

- [ ] `public_html/index.php` carrega apenas serviços/profissionais ativos e mantém o visual aprovado.
- [ ] Testes e gates aplicáveis passam; eventuais bloqueios manuais ficam no handoff.
- [ ] Sem alterações em schema/seed, imagens de exemplo, commits, pushes ou escopo de fases futuras.
