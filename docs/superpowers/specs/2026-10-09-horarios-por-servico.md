# Horários individuais por serviço

**Data:** 09/10/2026
**Status:** aprovado para detalhamento do plano

## Contexto

O agendamento atual soma a duração de todos os serviços e procura um único intervalo contínuo. Isso impede reservas válidas quando o mesmo profissional pode realizar os serviços em horários separados na mesma data. A reserva já possui um registro pai e itens em `agendamento_servicos`, mas cada item ainda não guarda o próprio intervalo.

## Decisões aprovadas

- Todos os serviços de uma reserva são realizados pelo **mesmo profissional** e na **mesma data**.
- Cada serviço terá seu próprio horário de início e término; os intervalos podem ter lacunas entre si e não podem se sobrepor.
- A reserva continua sendo um único agendamento, uma única confirmação e uma única mensagem de WhatsApp.
- Não há serviços duplicados no carrinho, preservando a restrição atual de um item por serviço.
- Os dados exibidos ao visitante não identificam outros clientes.

## Modelo de dados e compatibilidade

Uma migração `006_horarios_por_servico.sql` adicionará `hora_inicio` e `hora_fim` a `agendamento_servicos`.

1. As colunas serão inicialmente adicionadas como anuláveis.
2. Itens históricos serão preenchidos com os horários do agendamento pai. A migração `005` só gerou itens históricos de um serviço, portanto não há perda de semântica.
3. Depois do preenchimento, as colunas se tornarão obrigatórias.
4. Para novas reservas, `agendamentos.hora_inicio` será o menor início e `agendamentos.hora_fim` será o maior término dos itens. Esses campos permanecem por compatibilidade com o painel e registros legados; a disponibilidade passa a usar os intervalos dos itens.

O painel administrativo continuará exibindo a reserva pai, acrescida da lista de serviços e seus horários. Assim, uma lacuna entre serviços não será interpretada como ocupação.

## Motor de disponibilidade

Será criada uma única fonte de verdade no PHP para:

- gerar a grade base a partir de funcionamento, exceções, bloqueios e intervalo do profissional;
- carregar intervalos já ocupados de cada item de agendamento ativo; agendamentos legados sem itens usam o intervalo pai;
- verificar colisão por intervalo, não apenas por horário inicial;
- considerar os demais horários que o visitante já escolheu como bloqueios provisórios;
- retornar os horários de um serviço com estado `disponivel`, `selecionado`, `agendado` ou `indisponivel`;
- verificar se existe uma combinação sem sobreposição para todos os serviços em uma data. A busca escolhe os serviços com menos alternativas primeiro e retrocede quando uma escolha bloqueia as demais.

O mesmo motor será chamado por APIs públicas, pela gravação e pelo painel administrativo. Não haverá cálculo paralelo no JavaScript.

## APIs públicas

`GET /api/disponibilidade.php` continuará recebendo serviços e profissional, mas devolverá o estado de cada dia do mês:

```json
{
  "mes": "2026-11",
  "dias": ["2026-11-10"],
  "estados": {
    "2026-11-10": { "disponivel": true, "possui_ocupacao": true }
  }
}
```

`dias` continua como lista para compatibilidade; `estados` adiciona os metadados visuais. Um dia é disponível somente quando existe uma combinação válida para todos os serviços escolhidos. `possui_ocupacao` apenas informa que há horários ocupados, sem expor dados de terceiros.

`GET /api/slots.php` passará a receber um serviço-alvo e as seleções provisórias dos demais serviços. Retornará toda a grade relevante, com o estado de cada horário e o término calculado pelo servidor. O cliente só poderá escolher itens retornados como `disponivel`.

No `POST /agendar.php`, o campo único `hora_inicio` será substituído por um mapa `horarios[servico_id] = HH:MM`. O endpoint preserva CSRF, valida os IDs e revalida a combinação completa dentro da transação com bloqueio do profissional. A resposta continua sendo `whatsapp_url` após a gravação.

## Interface pública

Após selecionar profissional e data, o acordeão exibirá um cartão para cada serviço selecionado:

- nome, duração e horário atualmente escolhido;
- grade de horários daquele serviço;
- escolha de um horário por cartão;
- atualização imediata das grades restantes e do resumo.

As cores e textos serão consistentes e terão legenda visível:

| Estado | Cor | Significado |
| --- | --- | --- |
| Disponível | Verde | Pode ser selecionado para o serviço atual. |
| Selecionado | Roxo | Escolhido pelo visitante. |
| Agendado/indisponível | Vermelho | Já reservado, bloqueado ou incompatível com outra seleção. |

No calendário, dias com uma combinação possível ficam verdes; a data ativa fica roxa; dias sem combinação ficam desabilitados. Dias que contêm ocupações terão indicador vermelho e rótulo acessível, sem revelar nome, serviço ou telefone de outro cliente. Nas grades de horários, o estado vermelho é mostrado diretamente em cada horário indisponível ou agendado.

O botão de confirmação só é habilitado quando todos os serviços tiverem um horário válido, além de profissional, data, nome e telefone. O resumo e a mensagem do WhatsApp exibirão cada serviço com início, término, duração e preço, além dos totais.

Em telas pequenas, os cartões e as grades permanecem em uma coluna dentro do painel de tela cheia. A legenda continua visível antes dos horários.

## Segurança e privacidade

- Preço, duração, profissional, data e cada horário são obtidos e validados no servidor.
- O navegador não envia término, preço ou disponibilidade como fonte de verdade.
- O endpoint nunca expõe identidade de clientes ou detalhes de reservas de terceiros.
- Revalidação e gravação ocorrem em uma transação; qualquer conflito encerra a operação sem reserva parcial.

## Verificação

Os testes deverão cobrir, no mínimo:

1. dois serviços em horários separados válidos para o mesmo profissional e data;
2. rejeição de serviços sobrepostos entre si;
3. rejeição de seleção que cruza reserva, bloqueio ou fechamento;
4. dia verde apenas quando existe combinação completa;
5. estados verde, roxo e vermelho e legenda no painel;
6. gravação e leitura dos horários individuais, incluindo dados legados;
7. resumo e URL de WhatsApp com horários por serviço;
8. regressão de disponibilidade, painel administrativo e confirmação atômica.

## Fora de escopo

- Profissionais diferentes dentro da mesma reserva.
- Serviços em datas diferentes na mesma reserva.
- Exposição pública de dados de outros clientes.
- Bibliotecas, plugins, frameworks ou dependências novas.
