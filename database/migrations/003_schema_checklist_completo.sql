-- =============================================================================
-- OneCheck — Migração 003: módulo completo (checklist, locatário, endereços, RNFs)
-- Execute APÓS 001_schema.sql (e opcionalmente 002_checklist.sql)
--
-- MySQL 8+ recomendado. Porta XAMPP comum: 3307
-- mysql -h 127.0.0.1 -P 3307 -u root -p onecheck < database/migrations/003_schema_checklist_completo.sql
-- =============================================================================

USE onecheck;

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- RF01 / RF02 / RNF02 — Usuários: perfil locatário + MFA
-- -----------------------------------------------------------------------------
ALTER TABLE usuarios
  MODIFY COLUMN perfil ENUM(
    'admin', 'gestor', 'vistoriador', 'visualizador', 'locatario'
  ) NOT NULL DEFAULT 'vistoriador';

ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS uuid CHAR(36) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS mfa_secret VARCHAR(64) NULL COMMENT 'TOTP base32' AFTER senha_hash,
  ADD COLUMN IF NOT EXISTS mfa_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER mfa_secret,
  ADD COLUMN IF NOT EXISTS mfa_obrigatorio TINYINT(1) NOT NULL DEFAULT 0 AFTER mfa_enabled;

-- UUID para registros existentes (MySQL 8)
UPDATE usuarios SET uuid = UUID() WHERE uuid IS NULL;

ALTER TABLE usuarios
  MODIFY COLUMN uuid CHAR(36) NOT NULL,
  ADD UNIQUE KEY uk_usuarios_uuid (uuid);

-- -----------------------------------------------------------------------------
-- RF03 / RF04 / RF07 — Imóveis: tamanho, garagem, novos status
-- -----------------------------------------------------------------------------
ALTER TABLE imoveis
  ADD COLUMN IF NOT EXISTS uuid CHAR(36) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS tamanho_m2 DECIMAL(10, 2) NULL AFTER tipo,
  ADD COLUMN IF NOT EXISTS garagem ENUM(
    'nenhuma', 'uma_vaga', 'duas_vagas', 'coberta', 'descoberta', 'garagem_fixa'
  ) NOT NULL DEFAULT 'nenhuma' AFTER tamanho_m2;

UPDATE imoveis SET uuid = UUID() WHERE uuid IS NULL;

ALTER TABLE imoveis
  MODIFY COLUMN uuid CHAR(36) NOT NULL,
  ADD UNIQUE KEY uk_imoveis_uuid (uuid);

-- Mapeia status antigos → novos (ajuste se necessário)
UPDATE imoveis SET status = 'locado' WHERE status = 'ocupado';

ALTER TABLE imoveis
  MODIFY COLUMN status ENUM(
    'disponivel', 'locado', 'em_vistoria', 'manutencao', 'inativo'
  ) NOT NULL DEFAULT 'disponivel';

-- -----------------------------------------------------------------------------
-- RF04 / RNF04 / RNF05 — Endereço com geolocalização (1 principal por imóvel)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS enderecos (
  id CHAR(36) NOT NULL PRIMARY KEY,
  imovel_id INT UNSIGNED NOT NULL,
  logradouro VARCHAR(255) NOT NULL,
  numero VARCHAR(20) NULL,
  complemento VARCHAR(100) NULL,
  bairro VARCHAR(100) NULL,
  cidade VARCHAR(100) NOT NULL,
  estado CHAR(2) NOT NULL,
  cep VARCHAR(10) NULL,
  latitude DECIMAL(10, 8) NULL,
  longitude DECIMAL(11, 8) NULL,
  principal TINYINT(1) NOT NULL DEFAULT 1,
  geocodificado_em DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_enderecos_imovel FOREIGN KEY (imovel_id) REFERENCES imoveis(id) ON DELETE CASCADE,
  INDEX idx_enderecos_imovel (imovel_id),
  INDEX idx_enderecos_geo (latitude, longitude)
) ENGINE=InnoDB;

-- Migra endereço legado da tabela imoveis (executa uma vez)
INSERT INTO enderecos (id, imovel_id, logradouro, cidade, estado, cep, principal)
SELECT UUID(), i.id, i.endereco, i.cidade, i.estado, i.cep, 1
FROM imoveis i
WHERE NOT EXISTS (SELECT 1 FROM enderecos e WHERE e.imovel_id = i.id);

-- -----------------------------------------------------------------------------
-- RF05 — Cômodos cadastrados por imóvel (reutilizados nos checklists)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS imovel_comodos (
  id CHAR(36) NOT NULL PRIMARY KEY,
  imovel_id INT UNSIGNED NOT NULL,
  tipo VARCHAR(50) NOT NULL COMMENT 'sala, cozinha, quarto, banheiro, ...',
  descricao VARCHAR(255) NULL,
  ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comodos_imovel FOREIGN KEY (imovel_id) REFERENCES imoveis(id) ON DELETE CASCADE,
  UNIQUE KEY uk_imovel_tipo (imovel_id, tipo, descricao),
  INDEX idx_comodos_imovel (imovel_id)
) ENGINE=InnoDB;

-- Itens padrão por tipo de cômodo (templates globais — RF18)
CREATE TABLE IF NOT EXISTS checklist_item_templates (
  id CHAR(36) NOT NULL PRIMARY KEY,
  comodo_tipo VARCHAR(50) NOT NULL,
  codigo VARCHAR(40) NOT NULL COMMENT 'pintura, piso, janela, ...',
  rotulo VARCHAR(120) NOT NULL,
  ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uk_template_comodo_codigo (comodo_tipo, codigo)
) ENGINE=InnoDB;

INSERT IGNORE INTO checklist_item_templates (id, comodo_tipo, codigo, rotulo, ordem) VALUES
(UUID(), 'sala', 'pintura', 'Pintura', 1),
(UUID(), 'sala', 'piso', 'Piso', 2),
(UUID(), 'sala', 'janela', 'Janelas', 3),
(UUID(), 'sala', 'porta', 'Portas', 4),
(UUID(), 'cozinha', 'pintura', 'Pintura', 1),
(UUID(), 'cozinha', 'piso', 'Piso', 2),
(UUID(), 'cozinha', 'armarios', 'Armários', 3),
(UUID(), 'cozinha', 'torneira', 'Torneira / pia', 4),
(UUID(), 'banheiro', 'pintura', 'Pintura', 1),
(UUID(), 'banheiro', 'piso', 'Piso', 2),
(UUID(), 'banheiro', 'vaso', 'Vaso sanitário', 3),
(UUID(), 'banheiro', 'chuveiro', 'Chuveiro', 4),
(UUID(), 'quarto', 'pintura', 'Pintura', 1),
(UUID(), 'quarto', 'piso', 'Piso', 2),
(UUID(), 'quarto', 'janela', 'Janelas', 3);

-- -----------------------------------------------------------------------------
-- RF08 / RF09 / RF10 — Contrato com locatário como usuário
-- -----------------------------------------------------------------------------
ALTER TABLE contratos
  ADD COLUMN IF NOT EXISTS uuid CHAR(36) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS locatario_usuario_id INT UNSIGNED NULL AFTER imovel_id;

UPDATE contratos SET uuid = UUID() WHERE uuid IS NULL;

ALTER TABLE contratos
  MODIFY COLUMN uuid CHAR(36) NOT NULL,
  ADD UNIQUE KEY uk_contratos_uuid (uuid);

ALTER TABLE contratos
  ADD CONSTRAINT fk_contratos_locatario_usuario
    FOREIGN KEY (locatario_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL;

-- RF09: apenas um contrato ativo por imóvel (trigger abaixo; MySQL não tem índice parcial)

-- Trigger: impede segundo contrato ativo no mesmo imóvel (RF09)
DROP TRIGGER IF EXISTS trg_contrato_um_ativo_insert;
DELIMITER //
CREATE TRIGGER trg_contrato_um_ativo_insert
BEFORE INSERT ON contratos
FOR EACH ROW
BEGIN
  IF NEW.status = 'ativo' AND EXISTS (
    SELECT 1 FROM contratos c
    WHERE c.imovel_id = NEW.imovel_id AND c.status = 'ativo'
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Imóvel já possui contrato ativo (RF09)';
  END IF;
END//
DELIMITER ;

DROP TRIGGER IF EXISTS trg_contrato_um_ativo_update;
DELIMITER //
CREATE TRIGGER trg_contrato_um_ativo_update
BEFORE UPDATE ON contratos
FOR EACH ROW
BEGIN
  IF NEW.status = 'ativo' AND NEW.id != OLD.id AND EXISTS (
    SELECT 1 FROM contratos c
    WHERE c.imovel_id = NEW.imovel_id AND c.status = 'ativo' AND c.id != NEW.id
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Imóvel já possui contrato ativo (RF09)';
  END IF;
END//
DELIMITER ;

-- Ao encerrar contrato → imóvel disponível (RF10)
DROP TRIGGER IF EXISTS trg_contrato_encerrado_imovel;
DELIMITER //
CREATE TRIGGER trg_contrato_encerrado_imovel
AFTER UPDATE ON contratos
FOR EACH ROW
BEGIN
  IF NEW.status IN ('encerrado', 'cancelado') AND OLD.status = 'ativo' THEN
    UPDATE imoveis SET status = 'disponivel' WHERE id = NEW.imovel_id;
  END IF;
  IF NEW.status = 'ativo' AND OLD.status != 'ativo' THEN
    UPDATE imoveis SET status = 'locado' WHERE id = NEW.imovel_id;
  END IF;
END//
DELIMITER ;

-- -----------------------------------------------------------------------------
-- RF15 — Agendamento de vistoria vinculado ao contrato
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS agendamentos_vistoria (
  id CHAR(36) NOT NULL PRIMARY KEY,
  contrato_id INT UNSIGNED NOT NULL,
  imovel_id INT UNSIGNED NOT NULL,
  vistoriador_id INT UNSIGNED NOT NULL,
  tipo ENUM('inicial', 'encerramento') NOT NULL,
  data_agendada DATE NOT NULL,
  hora_agendada TIME NULL,
  status ENUM('agendada', 'em_andamento', 'realizada', 'cancelada') NOT NULL DEFAULT 'agendada',
  observacoes TEXT NULL,
  criado_por INT UNSIGNED NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_agend_contrato FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE RESTRICT,
  CONSTRAINT fk_agend_imovel FOREIGN KEY (imovel_id) REFERENCES imoveis(id) ON DELETE RESTRICT,
  CONSTRAINT fk_agend_vistoriador FOREIGN KEY (vistoriador_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
  CONSTRAINT fk_agend_criador FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE RESTRICT,
  INDEX idx_agend_data (data_agendada),
  INDEX idx_agend_vistoriador (vistoriador_id, status)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- RF11–RF15 / RF21–RF24 — Checklist vinculado ao contrato (fluxo completo)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS checklists (
  id CHAR(36) NOT NULL PRIMARY KEY,
  uuid_publico CHAR(36) NOT NULL COMMENT 'ID externo / PDF',
  contrato_id INT UNSIGNED NOT NULL,
  imovel_id INT UNSIGNED NOT NULL,
  agendamento_id CHAR(36) NULL,
  tipo ENUM('inicial', 'encerramento') NOT NULL,
  status ENUM(
    'em_preenchimento',
    'pendente_envio_locatario',
    'pendente_aceite',
    'aceito',
    'rejeitado',
    'pendente_revisao'
  ) NOT NULL DEFAULT 'em_preenchimento',
  vistoriador_id INT UNSIGNED NULL,
  preenchido_por INT UNSIGNED NULL,
  enviado_locatario_em DATETIME NULL,
  aceito_em DATETIME NULL,
  rejeitado_em DATETIME NULL,
  motivo_rejeicao TEXT NULL,
  revisado_por INT UNSIGNED NULL,
  revisado_em DATETIME NULL,
  observacoes_admin TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_checklist_contrato FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE RESTRICT,
  CONSTRAINT fk_checklist_imovel FOREIGN KEY (imovel_id) REFERENCES imoveis(id) ON DELETE RESTRICT,
  CONSTRAINT fk_checklist_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos_vistoria(id) ON DELETE SET NULL,
  CONSTRAINT fk_checklist_vistoriador FOREIGN KEY (vistoriador_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  UNIQUE KEY uk_checklist_uuid_publico (uuid_publico),
  INDEX idx_checklist_status (status),
  INDEX idx_checklist_contrato (contrato_id)
) ENGINE=InnoDB;

-- Histórico de mudanças de status do checklist
CREATE TABLE IF NOT EXISTS checklist_status_historico (
  id CHAR(36) NOT NULL PRIMARY KEY,
  checklist_id CHAR(36) NOT NULL,
  status_anterior VARCHAR(40) NULL,
  status_novo VARCHAR(40) NOT NULL,
  usuario_id INT UNSIGNED NULL,
  observacao TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_hist_checklist FOREIGN KEY (checklist_id) REFERENCES checklists(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Cômodos instanciados no checklist (cópia dos imovel_comodos no momento da criação)
CREATE TABLE IF NOT EXISTS checklist_comodos (
  id CHAR(36) NOT NULL PRIMARY KEY,
  checklist_id CHAR(36) NOT NULL,
  imovel_comodo_id CHAR(36) NULL,
  tipo VARCHAR(50) NOT NULL,
  descricao VARCHAR(255) NULL,
  ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_cl_comodo_checklist FOREIGN KEY (checklist_id) REFERENCES checklists(id) ON DELETE CASCADE,
  INDEX idx_cl_comodos_checklist (checklist_id)
) ENGINE=InnoDB;

-- Itens por cômodo: pintura, piso, janela… (RF18)
CREATE TABLE IF NOT EXISTS checklist_itens (
  id CHAR(36) NOT NULL PRIMARY KEY,
  checklist_comodo_id CHAR(36) NOT NULL,
  codigo VARCHAR(40) NOT NULL,
  rotulo VARCHAR(120) NOT NULL,
  estado ENUM('otimo', 'bom', 'regular', 'ruim') NULL,
  observacao TEXT NULL,
  ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  preenchido_em DATETIME NULL,
  CONSTRAINT fk_cl_item_comodo FOREIGN KEY (checklist_comodo_id) REFERENCES checklist_comodos(id) ON DELETE CASCADE,
  INDEX idx_cl_itens_comodo (checklist_comodo_id)
) ENGINE=InnoDB;

-- Fotos por item (RF19)
CREATE TABLE IF NOT EXISTS checklist_item_fotos (
  id CHAR(36) NOT NULL PRIMARY KEY,
  checklist_item_id CHAR(36) NOT NULL,
  arquivo_nome VARCHAR(255) NOT NULL,
  arquivo_path VARCHAR(500) NOT NULL,
  mime_type VARCHAR(80) NULL,
  tamanho_bytes INT UNSIGNED NULL,
  origem ENUM('mobile', 'web') NOT NULL DEFAULT 'mobile',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cl_foto_item FOREIGN KEY (checklist_item_id) REFERENCES checklist_itens(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Aceite / rejeição explícito do locatário (RF22 / RF23)
CREATE TABLE IF NOT EXISTS checklist_aceites (
  id CHAR(36) NOT NULL PRIMARY KEY,
  checklist_id CHAR(36) NOT NULL,
  locatario_usuario_id INT UNSIGNED NOT NULL,
  acao ENUM('aceito', 'rejeitado') NOT NULL,
  motivo TEXT NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_aceite_checklist FOREIGN KEY (checklist_id) REFERENCES checklists(id) ON DELETE CASCADE,
  CONSTRAINT fk_aceite_locatario FOREIGN KEY (locatario_usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- RF25–RF28 — Problemas: fotos + histórico de atualizações
-- -----------------------------------------------------------------------------
ALTER TABLE problemas
  ADD COLUMN IF NOT EXISTS uuid CHAR(36) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS comodo VARCHAR(80) NULL AFTER vistoria_id;

UPDATE problemas SET uuid = UUID() WHERE uuid IS NULL;

ALTER TABLE problemas
  MODIFY COLUMN uuid CHAR(36) NOT NULL,
  ADD UNIQUE KEY uk_problemas_uuid (uuid);

CREATE TABLE IF NOT EXISTS problema_fotos (
  id CHAR(36) NOT NULL PRIMARY KEY,
  problema_id INT UNSIGNED NOT NULL,
  arquivo_nome VARCHAR(255) NOT NULL,
  arquivo_path VARCHAR(500) NOT NULL,
  mime_type VARCHAR(80) NULL,
  criado_por INT UNSIGNED NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_prob_foto_problema FOREIGN KEY (problema_id) REFERENCES problemas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS problema_atualizacoes (
  id CHAR(36) NOT NULL PRIMARY KEY,
  problema_id INT UNSIGNED NOT NULL,
  descricao TEXT NOT NULL,
  arquivo_path VARCHAR(500) NULL,
  criado_por INT UNSIGNED NOT NULL,
  visivel_locatario TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_prob_atual_problema FOREIGN KEY (problema_id) REFERENCES problemas(id) ON DELETE CASCADE,
  CONSTRAINT fk_prob_atual_usuario FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Fila de e-mails (RF28 — processar via cron ou job PHP)
CREATE TABLE IF NOT EXISTS notificacoes_email (
  id CHAR(36) NOT NULL PRIMARY KEY,
  destinatario_email VARCHAR(180) NOT NULL,
  assunto VARCHAR(255) NOT NULL,
  corpo TEXT NOT NULL,
  relacionado_tipo VARCHAR(50) NULL COMMENT 'problema, checklist, ...',
  relacionado_id VARCHAR(36) NULL,
  status ENUM('pendente', 'enviado', 'erro') NOT NULL DEFAULT 'pendente',
  tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0,
  erro_msg TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  enviado_em DATETIME NULL,
  INDEX idx_notif_status (status, criado_em)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- RNF01 — JWT refresh tokens (API / app futuro)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auth_refresh_tokens (
  id CHAR(36) NOT NULL PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expira_em DATETIME NOT NULL,
  revogado TINYINT(1) NOT NULL DEFAULT 0,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_refresh_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  UNIQUE KEY uk_refresh_hash (token_hash)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- RNF03 — Log de operações
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS log_operacao (
  id CHAR(36) NOT NULL PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  acao VARCHAR(20) NOT NULL COMMENT 'create, update, delete, login, ...',
  entidade VARCHAR(80) NOT NULL,
  entidade_id VARCHAR(36) NULL,
  payload_anterior JSON NULL,
  payload_novo JSON NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_log_usuario (usuario_id, criado_em),
  INDEX idx_log_entidade (entidade, entidade_id),
  INDEX idx_log_data (criado_em)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- RNF06 — API keys para integração externa
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS api_keys (
  id CHAR(36) NOT NULL PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  key_hash CHAR(64) NOT NULL COMMENT 'SHA-256 da chave',
  key_prefix VARCHAR(12) NOT NULL COMMENT 'primeiros chars para identificação',
  permissoes JSON NULL COMMENT '["imoveis:read","checklists:read"]',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_uso DATETIME NULL,
  expira_em DATETIME NULL,
  criado_por INT UNSIGNED NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_api_key_hash (key_hash)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- Fim da migração 003
-- Próximo passo: docs/ROADMAP_IMPLEMENTACAO.md
-- =============================================================================
