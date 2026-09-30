-- Migração 002 — limites de taxa (Fase 2)
-- Contador por chave em janela de tempo: login do admin (por IP e por e-mail),
-- verificação do 2FA e, na Fase 4, api/slots.php e POST de agendamento.
-- Guardado no banco porque limite só na sessão é contornado apagando o cookie.
-- chave é a PK natural (ex.: 'login:ip:203.0.113.5'); não há id UUID nesta tabela.

SET NAMES utf8mb4;

INSERT INTO migracoes (versao) VALUES ('002_limites_taxa');

CREATE TABLE limites_taxa (
  chave          VARCHAR(120) NOT NULL PRIMARY KEY,
  contador       INT UNSIGNED NOT NULL DEFAULT 0,
  janela_inicio  DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
