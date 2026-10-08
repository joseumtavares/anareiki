# Fase 5 — revisão e preparação do fechamento

Data: 2026-10-07. Branch: `fase-5-painel-admin`, base funcional `03b5b11`.

**Atualização após autorização de correções:** R1–R6 foram corrigidos no código e os gates globais passaram. Migration 004 de categorias aplicada localmente. O restante deste documento preserva a revisão inicial; consultar [CORRECOES-FASE-5.md](CORRECOES-FASE-5.md) para implementação, evidências e revalidação visual ainda necessária. Não houve merge/push.

## Resultado

Aceite visual informado por Jose na conversa, incluindo o resumo mensal. Não houve nova inspeção automatizada de navegador nesta revisão nem evidência individual para todas as resoluções da especificação.

**Fechamento técnico pendente:** a revisão encontrou falhas de integração e gates globais reprovados. Não houve merge, push, deploy nem alterações de código nesta etapa de revisão/documentação. O último commit observado foi `96fc741`; há mudanças locais de responsividade e contato do cliente ainda não commitadas, além de arquivos do usuário, que foram preservados.

## Escopo original entregue

- Layout Bootstrap compartilhado, autenticação administrativa existente, navegação, breadcrumbs, mensagens flash e estados vazios.
- Serviços: criação, edição, ativação/desativação e validação no servidor.
- Profissionais: cadastro, edição, ativação/desativação e vínculos com serviços.
- Disponibilidade: regras e repositório semanal; calendário por data passou a ser a interface administrativa principal.
- Agendamentos: filtros por período, status e profissional; transições `pendente → confirmado/cancelado` e `confirmado → concluído/cancelado`, com atualização condicional transacional. Estados finais não oferecem novas transições.

## Ampliações solicitadas e aprovadas durante a implementação

| Alteração além da proposta original | Implementação e limite atual |
|---|---|
| Upload em vez de URL de imagem | Seleção de arquivo do computador/celular; JPG, PNG e WEBP até 5 MB; nomes aleatórios. `includes/uploads.php`. Integração da foto de profissionais precisa da correção R1. |
| Organização das imagens | Padrão aprovado: imagens de design em `assets/img/{logo,icons,backgrounds,static}/`; arquivos cadastrados em `uploads/{servicos,profissionais}/`, sob `public_html`. Não houve migração integral dos ativos legados de `static/` nem de imagens externas. |
| Galeria de miniaturas e escolha de imagem | Seleção por radio nos formulários de serviços e profissionais. Corrigir estrutura de formulário dos serviços (R3). Não foi criado módulo de produtos. |
| Exclusão física de imagens | Ação em serviços, com verificação de uso por serviço e restrição ao diretório de uploads. Não há ação equivalente na galeria de profissionais. Não remove automaticamente arquivos órfãos. |
| Exclusão física de serviços/profissionais | Repositórios rejeitam exclusão quando existem vínculos ou agendamentos, mantendo histórico; ativação/desativação continua disponível. É mais restritivo que verificar somente atendimentos realizados. |
| Ícones, cores e tags | Seletores de ícone/cor e sugestões de tags. A seleção de cores existente não deve ser descrita como uma grade visual sem nova conferência. |
| Categorias no painel inicial | Botão e listagem com cor padrão dos módulos; categoria selecionável no serviço. Cadastro separado ainda incompleto (R2); categorias vêm de valores distintos em `servicos`. |
| Erros personalizados e diagnóstico | Páginas 403/404/500, `ErrorDocument` no Apache, handler PHP e código de atendimento correlacionado a `error_log`. Mensagens internas ainda exigem sanitização para evitar dados pessoais nos logs (R4). Não há painel de logs, retenção/rotação configuradas pela aplicação ou captura de todo erro fatal. |
| Calendário interativo por data | Um clique seleciona/abre horários em verde; duplo clique ou botão bloqueia o dia em vermelho. É necessário **Salvar dia** para publicar. Sessão continua sendo de administrador, sem login/papel separado para profissional. |
| Intervalos de 30/60 minutos | Padrão 30, configuração por data; duração arredondada para blocos contíguos. 45 min ocupa 60; 75 min ocupa 90 em blocos de 30 ou 120 em blocos de 60. O final reservado persiste esse arredondamento. |
| Responsividade dos agendamentos | Tabela transforma-se em cartões abaixo de 992 px, ações com quebra de linha e alvo mínimo de toque de 44 px. |
| Identificação e WhatsApp | Nome e telefone no painel autenticado; link `wa.me` com normalização brasileira e nova aba. ID secundário, sem envio automático de mensagens. Exceção aprovada à minimização original da listagem. |
| Resumo mensal | Seletor de mês e pizza SVG sem biblioteca externa, cores por serviço e legenda textual. Quantidades incluem pendentes/confirmados/concluídos, excluindo cancelados. Valores por serviço e total somam **somente concluídos**, pelo mês da data do atendimento. |

### Limitações do faturamento

O sistema não registra pagamento nem preço histórico na reserva. O resumo é uma **estimativa pelo preço atual do catálogo**, e muda se o preço for editado. Serviços sem preço são sinalizados e não entram no valor somado. Isso não constitui implementação de pagamentos ou contabilidade.

### Banco e compatibilidade

`sql/migrations/003_disponibilidade_datas.sql` adiciona a tabela `disponibilidade_datas` com chave profissional/data. Aplicação local realizada na etapa anterior, seguida de confirmação de funcionamento por Jose; esta revisão não reaplicou a migration nem verificou produção.

A coluna `horarios` é TEXT com JSON: objeto com `intervalo` e `horarios`. Listas JSON antigas continuam interpretadas como blocos de 30 minutos. Uma data configurada substitui a regra semanal apenas naquele dia; lista vazia bloqueia; datas sem configuração usam a regra semanal. Reservas existentes continuam ocupando seus horários.

## Evidências automatizadas desta revisão

| Comando | Resultado em 2026-10-07 |
|---|---|
| `composer.bat test` | Aprovado: 99 testes, 461 asserções. |
| `composer.bat stan` | Aprovado: sem erros. |
| `composer.bat cs -- --report=summary` | Reprovado: 69 erros e 63 avisos em 14 arquivos. |
| `npm.cmd run lint` | Aprovado, sem erros reportados. |
| `npm.cmd run check:lines` | Reprovado: `includes/repositories.php` com 496 linhas, acima de 350. |
| `git diff --check` | Linha vazia adicional no handoff corrigida na atualização documental; verificação final aprovada. |

Testes de repositório não substituem testes HTTP de sessão/CSRF e integração dos formulários. A suíte verde não elimina os achados abaixo.

## Achados obrigatórios antes do fechamento

1. **R1 — Foto de profissionais (alta):** `salvarUploadImagem()` retorna `/uploads/profissionais/...`, mas `validarDadosProfissionalAdmin()` usa `urlFotoProfissional()`, que só aceita `/static/...`. O upload é salvo no disco e depois rejeitado pela validação; pode deixar arquivo órfão. Corrigir a política compartilhada e testar upload/seleção/persistência/renderização, sem liberar caminhos arbitrários.
2. **R2 — Cadastro de categorias (alta):** `/admin/` oferece “Adicionar categoria”, mas aponta para `/admin/servicos.php`, que só tem seleção de categorias e ainda instrui usar um campo removido. Não existe persistência/handler dedicado. Implementar o cadastro a partir do painel inicial conforme solicitado, mantendo somente seleção no serviço.
3. **R3 — Formulários aninhados (alta):** em `admin/servicos.php`, o formulário de exclusão de imagem fica dentro do formulário de cadastro/edição. HTML inválido altera a associação dos controles no navegador. Separar formulários e testar salvar serviço, selecionar imagem e excluir imagem sem uso. A confirmação inline também precisa ser compatível com a CSP vigente, que não permite scripts inline.
4. **R4 — Privacidade dos logs (alta):** `registrarErroAplicacao()` grava `Throwable::getMessage()` sem sanitização. A mensagem pode carregar dados pessoais/SQL. Adotar dados diagnósticos seguros e teste de não vazamento; configurar destino privado e retenção na preparação de deploy. O código de atendimento deve continuar correlacionável.
5. **R5 — Gates globais (obrigatório):** resolver erros/avisos de PHPCS e decompor `repositories.php` para respeitar 350 linhas, sem enfraquecer configurações nem misturar regras. Reexecutar a suíte após mudanças.
6. **R6 — Bootstrap/CSP (obrigatório):** `layout/admin.php` carrega o bundle Bootstrap de CDN enquanto `script-src` no `.htaccess` permite apenas `'self'`. O menu colapsável pode não funcionar sob Apache com a CSP ativa. Servir bundle local permitido e testar navegação mobile no ambiente com headers reais; não afrouxar CSP automaticamente.

Observações para acompanhamento: listagens administrativas ainda sem paginação; limpeza de upload após falha de validação não transacional; exclusão física de imagem e sua vinculação podem disputar em requisições concorrentes. Planejar cobertura desses caminhos antes de produção.

## Critério de encerramento e próxima ação

Corrigir R1–R6 em fatias verificáveis, executar gates completos, repetir testes visuais dos fluxos afetados e obter aceite técnico. Só então marcar Fase 5 concluída. Fase 6 permanece não iniciada: inventariar/localizar imagens externas e adotar `assets/img/` para novos ativos de design, preservando referências legadas até migração verificada.

Skills utilizadas nesta revisão: `documentation-and-adrs`, `code-review-and-quality` e inspeção do fluxo `finishing-a-development-branch`. A integração Git não foi iniciada porque há pendências técnicas; Jose continua decidindo commit/merge/push conforme o Plano Mestre.
