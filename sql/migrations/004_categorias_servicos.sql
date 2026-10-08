-- Cadastro independente; mantém categorias legadas dos serviços.
CREATE TABLE categorias_servicos (
  nome VARCHAR(50) NOT NULL PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categorias_servicos (nome)
SELECT DISTINCT categoria FROM servicos WHERE categoria <> '';

INSERT INTO migracoes (versao) VALUES ('004_categorias_servicos');
