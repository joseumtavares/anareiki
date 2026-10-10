-- Itens imutáveis de cada reserva; aplicar uma única vez após a migration 004.
-- O registro é intencionalmente a primeira operação para impedir reaplicação parcial.
INSERT INTO migracoes (versao) VALUES ('005_agendamento_servicos');

CREATE TABLE agendamento_servicos (
  id               CHAR(36) NOT NULL PRIMARY KEY,
  agendamento_id   CHAR(36) NOT NULL,
  servico_id       CHAR(36) NOT NULL,
  nome_servico     VARCHAR(100) NOT NULL,
  duracao_min      SMALLINT UNSIGNED NOT NULL,
  preco            DECIMAL(8,2) NULL,
  ordem            SMALLINT UNSIGNED NOT NULL,
  KEY ix_agendamento_servicos_agendamento (agendamento_id),
  UNIQUE KEY uq_agendamento_servico (agendamento_id, servico_id),
  CONSTRAINT fk_agendamento_servicos_agendamento
    FOREIGN KEY (agendamento_id) REFERENCES agendamentos (id),
  CONSTRAINT fk_agendamento_servicos_servico
    FOREIGN KEY (servico_id) REFERENCES servicos (id),
  CONSTRAINT ck_agendamento_servicos_duracao CHECK (duracao_min > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mantém todos os agendamentos antigos como reservas de um item, com snapshot
-- do catálogo no instante desta migração.
INSERT INTO agendamento_servicos
  (id, agendamento_id, servico_id, nome_servico, duracao_min, preco, ordem)
SELECT
  a.id, a.id, s.id, s.nome, s.duracao_min, s.preco, 1
FROM agendamentos a
INNER JOIN servicos s ON s.id = a.servico_id;
