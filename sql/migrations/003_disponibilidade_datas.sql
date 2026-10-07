-- Aplicar uma vez; preserva disponibilidade semanal e agendamentos existentes.
CREATE TABLE disponibilidade_datas (
  profissional_id CHAR(36) NOT NULL,
  data DATE NOT NULL,
  horarios TEXT NOT NULL,
  PRIMARY KEY (profissional_id, data),
  CONSTRAINT fk_disp_data_prof FOREIGN KEY (profissional_id) REFERENCES profissionais (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migracoes (versao) VALUES ('003_disponibilidade_datas');
