# Disponibilidade por data

Implementação autorizada em 07/10/2026 na branch fase-5-painel-admin.

Aplicar sql/migrations/003_disponibilidade_datas.sql no banco antes de utilizar o calendário.
A migração adiciona disponibilidade_datas sem modificar reservas nem regras semanais existentes.

Um clique seleciona a data em verde e apresenta 48 intervalos de 30 minutos.
Dois cliques ou o botão Bloquear dia inteiro desmarcam todos os intervalos e deixam a data vermelha.
Salvar dia publica a seleção; a gravação exige sessão administrativa e CSRF.
Datas passadas e horários inválidos são rejeitados pelo servidor.

A configuração por data substitui a disponibilidade semanal apenas naquela data.
Datas não configuradas continuam seguindo as regras semanais legadas.
Intervalos contíguos são agrupados para permitir serviços de maior duração.
Reservas existentes continuam ocupando seus horários, mesmo quando a data é posteriormente bloqueada.

Verificação automatizada: 91 testes PHPUnit, 428 asserções; PHPStan aprovado.
A aplicação da migração e o aceite visual autenticado continuam pendentes.
