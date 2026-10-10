# Especificação — raiz Hostinger e agendamento com múltiplos serviços

**Status:** implementação parcial; refinamento de interface do agendamento aprovado para planejamento.

## Objetivo

Preparar o projeto PHP existente para o deploy Git da Hostinger sem o aninhamento `public_html/public_html`, remover da home a seção “Nosso espaço / Galeria de Tratamentos” e evoluir o agendamento para uma única reserva com um ou mais serviços, sem reescrever os fluxos que já funcionam.

O resultado deve permitir escolher serviços sem duplicidade, profissional compatível, data e horário realmente disponíveis para a duração total, persistir a reserva de forma atômica e abrir o WhatsApp configurado com um resumo completo.

## Decisões aprovadas

- Manter PHP 8 + PDO + MySQL, HTML, CSS e JavaScript vanilla; não adotar Laravel nesta fase.
- A raiz do repositório será publicada como `/public_html` pela integração Git da Hostinger; os arquivos hoje em `public_html/` serão movidos para a raiz do repositório.
- `config.php` continuará manual, ignorado pelo Git e bloqueado contra acesso HTTP pelo `.htaccess` da raiz. `config.example.php` permanece versionado sem segredos.
- A reserva continua em `agendamentos`; uma nova tabela de itens preservará cada serviço, preço e duração aplicados no momento da reserva.
- O motor existente de slots será estendido, não substituído. Disponibilidade semanal, disponibilidade por data, intervalos, bloqueios e agendamentos existentes permanecem a fonte única da verdade.
- Serviços antigos em `agendamentos.servico_id` continuam legíveis; a migração deverá inserir um item correspondente em `agendamento_servicos` para cada registro preexistente, antes de o novo fluxo depender dessa tabela.

## Estrutura e deploy

Após a migração, a raiz conterá `index.php`, `agendar.php`, `confirmacao-agendamento.php`, `.htaccess`, `admin/`, `api/`, `includes/`, `static/`, `assets/`, `uploads/` e `vendor/` quando gerado para produção. Arquivos de desenvolvimento permanecem fora do fluxo HTTP, como `docs/`, `sql/`, `tests/`, `bin/`, `composer.*` e configurações de qualidade.

O `.htaccess` raiz preservará HTTPS, `Options -Indexes`, páginas de erro e cabeçalhos existentes, e bloqueará diretamente `config.php`, `config.example.php`, `includes/`, `vendor/`, `sql/`, `tests/`, `bin/` e arquivos de ambiente. Todos os links absolutos atuais (`/static/...`, `/api/...`, `/admin/...`) continuarão válidos; inclusões PHP e testes deverão abandonar somente o prefixo físico `public_html/`.

No hPanel, o repositório deve usar a branch publicada e o campo **Install Path** vazio. A primeira configuração exige que o diretório de instalação esteja vazio, conforme a documentação da Hostinger. Uploads são conteúdo operacional: o procedimento de deploy deve preservá-los e manter backup, pois não são artefatos Git.

## Modelo de dados

Será criada a migration `005_agendamento_servicos.sql`, com a primeira instrução registrando a versão em `migracoes`.

`agendamento_servicos` terá, no mínimo:

- `id` UUID v7 como chave primária;
- `agendamento_id` FK para `agendamentos`;
- `servico_id` FK para `servicos`;
- `nome_servico`, `duracao_min` e `preco` como snapshot do catálogo;
- ordem do item na reserva;
- índice por `agendamento_id` e unicidade `(agendamento_id, servico_id)` para impedir duplicidade.

A reserva principal conservará `profissional_id`, cliente, data, `hora_inicio`, `hora_fim` e status. Durante a compatibilidade, `servico_id` em `agendamentos` continua preenchido com o primeiro serviço selecionado para não quebrar telas, filtros e registros antigos; consultas novas de detalhes, confirmação e admin usarão os itens. Uma etapa posterior, fora deste escopo, poderá remover essa coluna somente depois de toda dependência legada ser eliminada.

O fim efetivo continua sendo calculado como início + duração total arredondada ao intervalo configurado por data, tal como o motor atual já faz para um serviço. O resumo ao cliente mostrará duração real dos serviços e término reservado; se houver arredondamento de bloco, ele será aplicado apenas à reserva/disponibilidade, sem alterar a duração exibida de cada serviço.

## Regras de disponibilidade e consistência

O backend recebe somente IDs de serviços, profissional, data e hora. Ele consulta serviços ativos no banco, elimina duplicidade, verifica se o profissional ativo realiza **todos** os serviços e soma as durações confiáveis. Preços e durações enviados pelo navegador são ignorados.

`obterSlotsDisponiveis()` ou uma função compatível receberá a duração total e continuará aceitando um slot apenas quando:

1. o intervalo inteiro cabe em uma faixa de disponibilidade semanal ou configurada por data;
2. não atravessa bloqueio/intervalo configurado;
3. não se sobrepõe a qualquer agendamento não cancelado desse profissional;
4. não termina após o encerramento da faixa.

O endpoint público passará a consultar disponibilidade por conjunto de serviços. Ele deverá fornecer os horários de uma data e uma forma eficiente de o calendário descobrir os dias disponíveis do mês, sem expor dados de clientes. A mesma validação será repetida dentro de transação imediatamente antes do `INSERT`; a reserva e todos os itens serão persistidos juntas ou nenhum dado será gravado. A proteção atual por índice único de horário inicial permanece, mas a validação por interseção de intervalos é obrigatória para reservas de duração diferente.

## Fluxo público

1. A página carrega os serviços ativos com `id`, nome, preço e duração, como dados de apresentação.
2. Cada CTA “Agendar” da home passa a adicionar o serviço ao carrinho do painel, sem encaminhar para uma página. O usuário também poderá adicionar e remover serviços no fluxo. Não haverá duplicidade.
3. Profissionais aparecem em cartões com foto, nome e especialidade. A lista contém somente profissionais capazes de realizar todos os serviços do carrinho. O cartão selecionado tem estado visual e acessível distinto.
4. Após serviços e profissional, o calendário navega por mês sem recarregar. Datas passadas e dias sem slot são desabilitados; dias com pelo menos um slot recebem o destaque verde previsto, sem quebrar a paleta existente.
5. Ao escolher uma data, a página exibe apenas horários que acomodam a duração total. Qualquer alteração em carrinho ou profissional limpa data/horário incompatíveis e recalcula o estado.
6. Um resumo permanece acessível: em desktop, fixo/aderente à coluna; em telas estreitas, aderente em posição que não cubra controles. Exibe itens, total conhecido ou “Consultar valor”, duração, profissional, data, início e término.
7. Nome e telefone permanecem obrigatórios conforme as validações atuais. O botão de confirmar fica desabilitado até todos os dados obrigatórios estarem válidos.
8. Após a transação bem-sucedida, a confirmação monta a URL `wa.me` com `URLSearchParams`/codificação correta, usando exclusivamente o número configurado. A mensagem lista profissional, data, intervalo, itens, durações, preços e total.

## Painel de agendamento na home

O agendamento será realizado dentro da home, sem navegar para `agendar.php` quando o cliente usar um CTA público. Os CTAs "Agendar" dos cards de serviço passam a adicionar o serviço ao carrinho do painel; eles não redirecionam. O JavaScript intercepta o link para oferecer o fluxo enriquecido e o `href` para `agendar.php` permanece somente como contingência para JavaScript desativado ou links legados.

Em desktop, o painel é uma coluna `sticky` que acompanha a rolagem vertical da página. No estado compacto, ele mostra a quantidade/itens do carrinho, duração, total e um único botão acessível de abrir o agendamento, com ícone de calendário e texto explícito. Ao ser aberto, o mesmo painel expande sem sair da página e contém, nesta ordem:

1. serviços selecionados, com remoção individual e opção de continuar selecionando cards na home;
2. cartões de profissionais compatíveis;
3. um acordeão "Data e horário", que contém um seletor único de data e horário: em desktop, calendário compacto à esquerda e uma coluna de horários do dia à direita; em celular, calendário seguido pelos horários na mesma área rolável;
4. um acordeão "Seus dados", liberado depois do horário, com nome e WhatsApp;
5. resumo e botão de confirmação.

O seletor mostra o mês/ano, botões anterior/próximo, dias indisponíveis desabilitados e o dia selecionado. O cabeçalho da coluna de horários informa a data selecionada; até que exista uma data válida, ela exibe uma orientação em vez de botões vazios. Cada horário é um botão com estado selecionado textual e visual. Calendário, horários e dados do cliente não abrem uma página independente. Os dados do acordeão são a mesma fonte usada na confirmação PHP: IDs de serviço, profissional, data, hora, nome e telefone; preço, duração, término e disponibilidade continuam sendo recalculados no servidor.

Em celular e telas estreitas, o painel compacto vira um botão fixo, com ícone e rótulo de agendamento. Depois de haver ao menos um serviço selecionado, o toque abre o fluxo como painel de tela cheia, com fechar visível e sem conteúdo lateral comprimido. O painel preserva o estado ao alternar entre aberto/fechado e não impede voltar à lista de serviços. A confirmação continua redirecionando somente após o POST válido para a página de confirmação/WhatsApp.

O componente usará elementos `button` e `details`/controles equivalentes acessíveis: foco visível, `aria-expanded`, rótulos, `aria-live` para disponibilidade e erros, Escape para fechar o painel de tela cheia e bloqueio de rolagem de fundo apenas enquanto ele estiver aberto no celular. A mudança não incorpora o texto, imagens, cores ou marca do exemplo de referência; mantém os tokens visuais existentes de Reiki Ana.

## Painel administrativo e compatibilidade

O painel atual continua responsável por cadastrar serviços, profissionais, associações profissional-serviço, disponibilidade por data e status. Não haverá segundo cadastro de horários. A lista de agendamentos e o dashboard serão adaptados para apresentar itens múltiplos de uma reserva sem vazar dados pessoais fora do admin; filtros e transições de status existentes devem continuar funcionando.

As interfaces e endpoints atuais que recebem um único `servico` terão uma janela de compatibilidade documentada. O frontend novo usará parâmetros/POST de lista; se necessário, o endpoint aceitará o formato anterior como lista de um item até todos os consumidores serem atualizados. `API.md`, `ARCHITECTURE.md`, `RULES.md` e `HANDOFF.md` serão atualizados junto da implementação.

## Interface, acessibilidade e responsividade

A interface reutilizará os tokens roxo, rosa, verde, creme, tipografia e raios existentes. Não serão incluídos Bootstrap, jQuery, plugins ou bibliotecas externas no site público. Cartões, carrinho, calendário, slots e resumo terão foco visível, semântica de botão, estados `aria-live` para carregamento/erros e funcionamento por teclado. Os layouts serão conferidos em desktop, tablet e 360–430 px.

## Segurança

- Prepared statements, UUIDs validados, CSRF em POST, rate limit e mensagens de erro sem dados sensíveis permanecem obrigatórios.
- Nenhuma confiança em preço, duração, profissional, disponibilidade ou término calculados em JavaScript.
- Inserção da reserva e dos itens ocorre em transação; falha ou colisão retorna instrução para recarregar horários.
- Arquivos e diretórios internos são bloqueados pelo Apache após a migração para a raiz pública.
- Dados de cliente não aparecem em JSON público, logs ou WhatsApp além da interação iniciada pelo próprio cliente.

## Critérios de aceite

Além dos do pedido, a entrega deve provar: migração sem referência física residual a `public_html/`; home sem a galeria removida; CTAs da home sem redirecionamento e com adição ao carrinho; painel de desktop aderente à rolagem; calendário, horários e dados dentro do painel; painel de tela cheia em celular; agendamento antigo convertido em item; carrinho sem duplicidade e com totais corretos; profissional incompatível ausente; fim de atendimento dentro da disponibilidade; bloqueios e reservas impedindo sobreposição; revalidação concorrente; mensagem WhatsApp codificada; regressão do admin; e validação visual responsiva.

Os gates são `composer test`, `composer stan`, `composer cs`, `npm run lint`, `npm run check:lines`, `git diff --check`, sintaxe PHP dos arquivos alterados e revisão de referências quebradas. A aplicação da migration e o teste ponta a ponta na Hostinger serão passos explícitos de deploy, não presumidos como concluídos localmente.

## Fora de escopo

- Migração para Laravel ou outro framework;
- pagamentos, notificações automáticas, novos papéis ou login de profissional;
- redesenho completo da identidade visual;
- redirecionar o CTA público para uma página de agendamento como fluxo principal;
- exclusão da coluna legada `agendamentos.servico_id`;
- publicação em produção sem checklist e aprovação final.
