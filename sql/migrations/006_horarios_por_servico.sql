-- Horários próprios por item; aplicar uma única vez após a migration 005.
-- O registro antecipado impede que uma execução parcialmente aplicada seja repetida.
INSERT INTO migracoes (versao) VALUES ('006_horarios_por_servico');

ALTER TABLE agendamento_servicos
  ADD COLUMN hora_inicio TIME NULL AFTER ordem,
  ADD COLUMN hora_fim TIME NULL AFTER hora_inicio;

-- A migration 005 criou um item para cada reserva legada, portanto o intervalo pai
-- representa exatamente o intervalo desse único item.
UPDATE agendamento_servicos i
INNER JOIN agendamentos a ON a.id = i.agendamento_id
SET i.hora_inicio = a.hora_inicio,
    i.hora_fim = a.hora_fim;

ALTER TABLE agendamento_servicos
  MODIFY hora_inicio TIME NOT NULL,
  MODIFY hora_fim TIME NOT NULL;
