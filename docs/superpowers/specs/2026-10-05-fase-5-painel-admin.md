# Especificacao - Fase 5: painel administrativo

Data: 2026-10-05
Status: concluída; ampliações e correções R1–R6 aprovadas por Jose; gates aprovados e integração Git autorizada em 2026-10-07
Escopo aprovado: CRUD de servicos, profissionais e disponibilidade; gestao de status dos agendamentos.

## 1. Objetivo e resultado esperado

Entregar um painel Bootstrap 5 autenticado para que a administracao mantenha o catalogo e a agenda sem editar o banco manualmente. O painel reutiliza autenticacao, 2FA, CSRF, conexao PDO e identidade visual existentes. O fluxo publico da Fase 4 permanece compativel e continua consumindo apenas registros ativos.

A fase sera considerada concluida quando:

- os quatro modulos estiverem acessiveis apenas a administradores autenticados;
- cada alteracao persistente tiver validacao server-side, prepared statements, CSRF e redirecionamento pos-POST;
- entidades puderem ser desativadas sem remocao destrutiva;
- transicoes de status e conflitos de disponibilidade forem bloqueados no servidor;
- testes automatizados e revisao tecnica passarem;
- os fluxos principais forem validados manualmente em desktop e nas larguras moveis usadas no aceite da Fase 4.

## 2. Limites da fase

### Incluido

- layout compartilhado do admin com navegacao, breadcrumbs, alertas e estados vazios;
- servicos: listar, criar, editar, ativar/desativar;
- profissionais: listar, criar, editar, ativar/desativar e vincular servicos;
- disponibilidade: listar por profissional, criar, editar, remover e impedir sobreposicao;
- agendamentos: listar, filtrar e alterar status conforme a maquina de estados abaixo.

### Fora do escopo

- novos papeis alem de `admin`;
- pagamentos, notificacoes ou envio de e-mail;
- upload de arquivos; imagens continuam usando URL validada pela politica existente;
- alteracoes de schema sem necessidade comprovada;
- publicacao em producao durante esta fase.

A implementacao deve partir de uma branch `fase-5-painel-admin` criada a partir de `main` no commit publicado que contem a Fase 4 (`03b5b11` como topo funcional). A branch `vercel-deploy` contem configuracao especifica de build e nao deve ser usada como base da feature.

## 3. Decisoes de arquitetura

### 3.1 Estrutura

- Manter paginas PHP em `public_html/admin/`, com um arquivo por modulo e acoes POST no proprio modulo ou em handler separado.
- Extrair consultas e regras de persistencia para repositorios em `public_html/includes/`; paginas ficam responsaveis por autorizacao, parsing da requisicao, mensagens e renderizacao.
- Reutilizar `requireAdmin()`, `csrf_token()`, `validarCsrf()` e `adminTopo()/adminRodape()`. Nenhuma pagina administrativa deve duplicar essas rotinas.
- Usar Post/Redirect/Get e mensagens flash para evitar reenvio de formulario.
- Manter Bootstrap 5 e a identidade roxa definida no layout; tabelas devem degradar para cartoes ou rolagem horizontal em telas estreitas.

### 3.2 Validacao e persistencia

- IDs devem ser UUID v7 em `CHAR(36)` e referenciar registros existentes, conforme a arquitetura do projeto.
- Texto deve ser normalizado, limitado por tamanho e escapado na saida.
- `duracao_min` deve ser inteiro positivo; preco, quando informado, deve ser decimal nao negativo.
- URLs de imagem devem passar pela mesma allowlist usada na home; nao havera caminho arbitrario ou upload.
- Horarios devem usar `H:i`, com inicio anterior ao fim; o mesmo profissional nao pode ter intervalos sobrepostos no mesmo dia.
- Desativacao usa `ativo = 0`. Exclusao fisica nao sera oferecida; registros ligados a agendamentos futuros nunca podem ser removidos.
- Toda escrita usara transacao quando envolver mais de uma tabela, especialmente vinculo profissional-servico e atualizacao de status.

### 3.3 Status de agendamento

A maquina proposta e: `pendente -> confirmado`, `pendente -> cancelado`, `confirmado -> concluido` e `confirmado -> cancelado`. `cancelado` e `concluido` sao estados finais. Qualquer outra transicao deve retornar erro sem alterar o registro. A acao exige CSRF, revalida o agendamento no banco e preserva os dados do cliente.

## 4. Sequencia incremental

Cada etapa termina com codigo executavel, testes da fatia e revisao antes da seguinte.

1. **Base compartilhada e navegacao** - consolidar layout, menu, autorizacao, flash messages, helpers de formulario e pagina inicial do painel.
2. **CRUD de servicos** - repositorio, listagem, formulario, validacao, ativacao/desativacao e testes.
3. **CRUD de profissionais** - dados basicos, vinculos com servicos, validacao de URLs e testes de integridade.
4. **Disponibilidade** - formulario por dia/horario, deteccao de sobreposicao, edicao/remocao e testes de limites.
5. **Agendamentos** - tabela responsiva, filtros, acoes de status, maquina de estados e testes de autorizacao/conflitos.
6. **Fechamento** - revisao de seguranca, testes completos, inspecao visual e atualizacao de `HANDOFF.md` e `PLANO_MESTRE_ANAREIKI.md`.

## 5. Contratos de cada modulo

### Servicos

Campos minimos: nome, descricao, duracao, preco opcional, categoria, icone, cor, tag, ordem e ativo. O formulario deve mostrar erros por campo e preservar os valores enviados. Servicos inativos nao aparecem na home nem podem ser escolhidos em novo agendamento.

### Profissionais

Campos minimos: nome, especialidade, biografia, foto opcional por URL, ativo e servicos vinculados. O painel deve impedir vinculo com servico inexistente e mostrar o estado dos vinculos ao editar. Profissionais inativos nao aparecem na selecao publica.

### Disponibilidade

Cada intervalo pertence a um profissional e a um dia da semana. Validar dia permitido, horario valido, inicio menor que fim e ausencia de intersecao com outro intervalo do mesmo profissional. A remocao exige confirmacao visual e CSRF.

### Agendamentos

A listagem deve exibir data, horario, servico, profissional, status e identificador do agendamento, com filtros por periodo/status/profissional. Dados sensiveis do cliente devem ser minimizados na tabela e nunca escritos em logs. Acoes invalidas ou concorrentes falham de forma segura e informam o administrador.

## 6. Testes e portoes

Para cada fatia, criar testes de repositorio/HTTP cobrindo sucesso, validacao, autorizacao, CSRF, registros inativos, duplicidades e concorrencia relevante. O fechamento executara a suite existente, analise estatica/lint disponiveis no projeto e `git diff --check`.

A validacao visual manual deve cobrir login, navegacao, formularios, alertas, tabelas, estados vazios e acoes de status em desktop e em 360x800, 375x667, 390x844, 412x915, 430x932, 768x1024 e 800x1280.

## 7. Gate de aprovacao

Este documento e a proposta da Fase 5. Nenhum arquivo de produto deve ser implementado ate a aprovacao desta especificacao e da revisao tecnica correspondente. Apos a aprovacao, sera criado o plano de implementacao detalhado e executada a sequencia da secao 4 em fatias revisaveis.

## 8. Registro de evolução e revisão — 2026-10-07

As seções anteriores preservam a proposta original, exceto a correção dos IDs para UUID. Jose aprovou a implementação e as ampliações durante o desenvolvimento: upload/galeria/exclusão de imagens, exclusão condicionada de entidades, seletores do catálogo/categorias, erros e logs, disponibilidade por data com blocos de 30/60 minutos, cartões móveis de agendamentos, identificação/WhatsApp e resumo mensal com valores somente de concluídos.

Essas aprovações substituem as restrições originais de URL sem upload, ausência de exclusão física e disponibilidade exclusivamente semanal. Não autorizam pagamentos, novos papéis ou deploy. O padrão de caminhos aprovado é `assets/img/` para design e `uploads/servicos/` e `uploads/profissionais/` para conteúdo administrativo, sob `public_html`.

Jose informou que os testes visuais foram concluídos e aprovados. A revisão encontrou pendências, corrigidas após nova autorização: gates globais passaram; repetir a conferência visual dos fluxos afetados antes do fechamento. Ver [revisão e inventário](../../REVISAO-FASE-5.md) e [correções e evidências](../../CORRECOES-FASE-5.md).
