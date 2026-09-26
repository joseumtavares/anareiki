-- Migração 001 — schema inicial + seed (Fase 1)
-- Aplicar em um banco VAZIO (local: anareiki; Hostinger: o banco criado no hPanel).
-- Compatível com MySQL 8 e MariaDB 10.4+. Ver docs/ARCHITECTURE.md §4.
-- IDs: UUID v7 em CHAR(36), gerados no PHP (o seed usa valores fixos).
-- Não contém administrador: ele é criado na Fase 2 por script local (senha nunca versionada).

SET NAMES utf8mb4;

-- ---------------------------------------------------------------
-- Controle de migrações
-- Toda migração começa registrando a própria versão: reaplicar falha
-- nesta linha (chave duplicada) antes de alterar qualquer tabela.
-- ---------------------------------------------------------------

CREATE TABLE migracoes (
  versao      VARCHAR(100) NOT NULL PRIMARY KEY,
  aplicada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migracoes (versao) VALUES ('001_schema_inicial');

-- ---------------------------------------------------------------
-- Tabelas
-- ---------------------------------------------------------------

-- Papel único: todo registro aqui é admin.
CREATE TABLE administradores (
  id          CHAR(36) NOT NULL PRIMARY KEY,
  nome        VARCHAR(100) NOT NULL,
  email       VARCHAR(190) NOT NULL,
  senha_hash  VARCHAR(255) NOT NULL,
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_administradores_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2FA por e-mail: só o hash do código é gravado; uso único, validade curta.
CREATE TABLE codigos_2fa (
  id                CHAR(36) NOT NULL PRIMARY KEY,
  administrador_id  CHAR(36) NOT NULL,
  codigo_hash       VARCHAR(255) NOT NULL,
  expira_em         DATETIME NOT NULL,
  tentativas        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  usado_em          DATETIME NULL,
  criado_em         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_2fa_admin_criado (administrador_id, criado_em),
  CONSTRAINT fk_2fa_administrador FOREIGN KEY (administrador_id) REFERENCES administradores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE profissionais (
  id             CHAR(36) NOT NULL PRIMARY KEY,
  nome           VARCHAR(100) NOT NULL,
  especialidade  VARCHAR(150) NULL,
  bio            TEXT NULL,
  foto_url       VARCHAR(255) NULL,
  ativo          TINYINT(1) NOT NULL DEFAULT 1,
  criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- preco NULL = "Consultar valor" no site.
-- imagem_url/icone/cor/tag: dados de apresentação do card (DESIGN-SYSTEM §10).
CREATE TABLE servicos (
  id           CHAR(36) NOT NULL PRIMARY KEY,
  nome         VARCHAR(100) NOT NULL,
  descricao    TEXT NOT NULL,
  duracao_min  SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  preco        DECIMAL(8,2) NULL,
  categoria    VARCHAR(50) NOT NULL,
  imagem_url   VARCHAR(255) NULL,
  icone        VARCHAR(50) NULL,
  cor          VARCHAR(20) NULL,
  tag          VARCHAR(50) NULL,
  ativo        TINYINT(1) NOT NULL DEFAULT 1,
  ordem        SMALLINT NOT NULL DEFAULT 0,
  CONSTRAINT ck_servicos_duracao CHECK (duracao_min > 0),
  CONSTRAINT ck_servicos_preco CHECK (preco IS NULL OR preco >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE profissional_servico (
  profissional_id  CHAR(36) NOT NULL,
  servico_id       CHAR(36) NOT NULL,
  PRIMARY KEY (profissional_id, servico_id),
  CONSTRAINT fk_ps_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais (id),
  CONSTRAINT fk_ps_servico FOREIGN KEY (servico_id) REFERENCES servicos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- dia_semana: 0 = domingo … 6 = sábado (mesma convenção do PHP date('w')).
CREATE TABLE disponibilidade (
  id               CHAR(36) NOT NULL PRIMARY KEY,
  profissional_id  CHAR(36) NOT NULL,
  dia_semana       TINYINT UNSIGNED NOT NULL,
  hora_inicio      TIME NOT NULL,
  hora_fim         TIME NOT NULL,
  KEY ix_disp_prof_dia (profissional_id, dia_semana),
  CONSTRAINT fk_disp_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais (id),
  CONSTRAINT ck_disp_dia CHECK (dia_semana BETWEEN 0 AND 6),
  CONSTRAINT ck_disp_faixa CHECK (hora_fim > hora_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- uq_agend_slot: rede de segurança contra overbooking (RULES §10).
CREATE TABLE agendamentos (
  id                CHAR(36) NOT NULL PRIMARY KEY,
  servico_id        CHAR(36) NOT NULL,
  profissional_id   CHAR(36) NOT NULL,
  cliente_nome      VARCHAR(100) NOT NULL,
  cliente_telefone  VARCHAR(20) NOT NULL,
  cliente_email     VARCHAR(190) NULL,
  data              DATE NOT NULL,
  hora_inicio       TIME NOT NULL,
  hora_fim          TIME NOT NULL,
  status            ENUM('pendente','confirmado','cancelado','concluido') NOT NULL DEFAULT 'pendente',
  observacao        TEXT NULL,
  criado_em         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_agend_slot (profissional_id, data, hora_inicio),
  KEY ix_agend_data_status (data, status),
  CONSTRAINT fk_agend_servico FOREIGN KEY (servico_id) REFERENCES servicos (id),
  CONSTRAINT fk_agend_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais (id),
  CONSTRAINT ck_agend_faixa CHECK (hora_fim > hora_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- Seed — conteúdo atual do site
-- ---------------------------------------------------------------

INSERT INTO profissionais (id, nome, especialidade, bio) VALUES
('01a0db02-f800-76df-a6eb-97a169b3083f', 'Ana', 'Massoterapeuta',
 'Sou massoterapeuta apaixonada pelo bem-estar e pela cura natural. Com técnicas cuidadosamente aplicadas, ofereço um espaço seguro e acolhedor para que você encontre o equilíbrio entre corpo e mente.');

-- imagem_url: ainda hotlink do genspark; troca para static/img/ na Fase 6.
INSERT INTO servicos (id, nome, descricao, duracao_min, preco, categoria, imagem_url, icone, cor, tag, ordem) VALUES
('01a0db02-f801-752e-811c-921ba4595ebb', 'Massagem Relaxante',   'Alivia tensões e proporciona profundo relaxamento muscular e mental. Ideal para quem precisa descansar e renovar as energias.', 60, 75.00,  'Massagens', 'https://www.genspark.ai/api/files/s/JxWMZCMW', NULL, NULL, 'Mais Pedida', 1),
('01a0db02-f802-7ad2-ae14-3374b133abbe', 'Massagem Terapêutica', 'Focada em tratar dores musculares específicas, tensões e desconfortos. Trabalha pontos de tensão com técnicas profissionais.', 60, 80.00,  'Massagens', NULL, 'fa-hands', NULL, NULL, 2),
('01a0db02-f803-7481-8c64-693c404ead75', 'Drenagem Linfática',   'Ativa o sistema linfático, reduzindo inchaços e retenção de líquidos. Proporciona leveza e bem-estar natural.', 60, 80.00,  'Massagens', NULL, 'fa-droplet', 'purple', NULL, 3),
('01a0db02-f804-7b53-a32e-8922dd41135b', 'Massagem Modeladora',  'Modela e define o corpo, melhora a circulação e combate a celulite. Para uma silhueta mais harmoniosa.', 60, 100.00, 'Massagens', NULL, 'fa-person', 'green', NULL, 4),
('01a0db02-f805-7bd1-a116-12e1702a2193', 'Pedras Quentes',       'Pedras vulcânicas aquecidas que promovem relaxamento muscular profundo, ação anti-inflamatória e liberam serotonina. Benefícios: relaxamento muscular, anti-inflamatório, libera serotonina, alivia dores menstruais.', 60, NULL, 'Massagens', 'https://www.genspark.ai/api/files/s/B5ujCiDj', NULL, NULL, NULL, 5),
('01a0db02-f806-779e-8312-ef70e735df12', 'Ventosaterapia',       'Técnica da medicina chinesa com copos de sucção. Estimula circulação sanguínea, alivia dores musculares e reduz tensões.', 60, 80.00,  'Massagens', 'https://www.genspark.ai/api/files/s/sM02mKkO', NULL, NULL, NULL, 6),
('01a0db02-f807-7c40-ac1f-a406b335abce', 'Reiki',                'Terapia energética que equilibra os chakras, reduz o estresse e promove cura holística do corpo e da mente.', 60, 80.00,  'Terapias', NULL, 'fa-hands-praying', 'gold', NULL, 7),
('01a0db02-f808-76cf-be32-c401d8423bcc', 'Cone Hindú',           'Terapia auricular com cones de ervas que promove limpeza do canal auditivo e equilíbrio energético.', 60, 60.00,  'Terapias', NULL, 'fa-fire', 'rose', NULL, 8),
('01a0db02-f809-7058-8544-5c0d74195a34', 'Auriculoterapia',      'Estimulação de pontos no pavilhão auricular que correspondem a órgãos e sistemas do corpo. Equilibra e trata diversas condições.', 60, 75.00,  'Terapias', NULL, 'fa-brain', 'teal', NULL, 9),
('01a0db02-f80a-7d1b-aa14-c378a963ff46', 'Reflexologia Podal',   'Massagem nos pés que estimula pontos reflexos conectados a órgãos e sistemas do corpo, promovendo equilíbrio global.', 60, 75.00,  'Terapias', 'https://www.genspark.ai/api/files/s/ZfRuIuK5', NULL, NULL, NULL, 10),
('01a0db02-f80b-7cb9-a11e-c6279af5e5fd', 'Escalda Pés',          'Reduz dores musculares, ativa a circulação e diminui inchaço. Perfeito para descansar após dias agitados.', 60, NULL, 'Terapias', 'https://www.genspark.ai/api/files/s/n8UWPNbC', NULL, NULL, NULL, 11),
('01a0db02-f80c-74fe-80dd-83fe5dbefeb4', 'Limpeza de Pele',      'Tratamento facial completo para remover impurezas, hidratar e renovar a pele, deixando-a mais saudável e luminosa.', 60, 100.00, 'Estética', NULL, 'fa-spa', 'pink', NULL, 12);

INSERT INTO profissional_servico (profissional_id, servico_id)
SELECT '01a0db02-f800-76df-a6eb-97a169b3083f', id FROM servicos;

-- Seg–Qua 18h–22h, Sex 18h–22h, Sáb 9h–18h
INSERT INTO disponibilidade (id, profissional_id, dia_semana, hora_inicio, hora_fim) VALUES
('01a0db02-f80d-7ae4-b9f6-e3a131c768e9', '01a0db02-f800-76df-a6eb-97a169b3083f', 1, '18:00', '22:00'),
('01a0db02-f80e-777e-8394-4d4917f0ece8', '01a0db02-f800-76df-a6eb-97a169b3083f', 2, '18:00', '22:00'),
('01a0db02-f80f-7946-a9b3-d47269fca860', '01a0db02-f800-76df-a6eb-97a169b3083f', 3, '18:00', '22:00'),
('01a0db02-f810-7307-8413-54ce4ab6646c', '01a0db02-f800-76df-a6eb-97a169b3083f', 5, '18:00', '22:00'),
('01a0db02-f811-7ea8-b40a-51639a39f7ce', '01a0db02-f800-76df-a6eb-97a169b3083f', 6, '09:00', '18:00');
