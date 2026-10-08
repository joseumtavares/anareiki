# Disponibilidade por data

Implementação autorizada em 07/10/2026 na branch fase-5-painel-admin.

Aplicar sql/migrations/003_disponibilidade_datas.sql no banco antes de utilizar o calendário.
A migração adiciona disponibilidade_datas sem modificar reservas nem regras semanais existentes.

Um clique seleciona a data em verde e apresenta intervalos selecionáveis.
O padrão é 30 minutos, com opção de 60 minutos por data.
Ao trocar o intervalo, os horários selecionados são limpos para evitar ampliar a disponibilidade sem confirmação.
Serviços reservam blocos consecutivos suficientes: 45 minutos ocupam 60;
75 minutos ocupam 90 em blocos de 30 ou 120 em blocos de 60.
O horário final persistido usa o tempo arredondado, preservando a duração original do serviço.
Datas antigas permanecem em blocos de 30. A opção usa a coluna JSON existente, sem nova migração.
Dois cliques ou o botão Bloquear dia inteiro desmarcam todos os intervalos e deixam a data vermelha.
Salvar dia publica a seleção; a gravação exige sessão administrativa e CSRF.
Datas passadas e horários inválidos são rejeitados pelo servidor.

A configuração por data substitui a disponibilidade semanal apenas naquela data.
Datas não configuradas continuam seguindo as regras semanais legadas.
Intervalos contíguos são agrupados para permitir serviços de maior duração.
Reservas existentes continuam ocupando seus horários, mesmo quando a data é posteriormente bloqueada.

Verificação consolidada em 07/10/2026: 99 testes PHPUnit, 461 asserções; PHPStan aprovado.
A migration foi aplicada ao banco local na etapa anterior e Jose confirmou o funcionamento.
Jose informou a conclusão e aprovação dos testes visuais na conversa de fechamento.
Isso não equivale à aplicação da migration em produção.
O fechamento técnico da Fase 5 depende das correções registradas em [REVISAO-FASE-5.md](REVISAO-FASE-5.md).
