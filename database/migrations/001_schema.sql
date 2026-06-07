-- OneCheck - schema inicial
-- Execute no MySQL: mysql -u root -p < database/migrations/001_schema.sql

CREATE DATABASE IF NOT EXISTS onecheck
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE onecheck;

-- Usuários do painel web
CREATE TABLE usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  perfil ENUM('admin', 'gestor', 'vistoriador', 'visualizador') NOT NULL DEFAULT 'vistoriador',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Imóveis
CREATE TABLE imoveis (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  titulo VARCHAR(200) NOT NULL,
  endereco VARCHAR(255) NOT NULL,
  cidade VARCHAR(100) NOT NULL,
  estado CHAR(2) NOT NULL,
  cep VARCHAR(10) NULL,
  tipo ENUM('apartamento', 'casa', 'comercial', 'galpao', 'outro') NOT NULL DEFAULT 'apartamento',
  status ENUM('disponivel', 'ocupado', 'manutencao', 'inativo') NOT NULL DEFAULT 'disponivel',
  observacoes TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_imoveis_status (status),
  INDEX idx_imoveis_cidade (cidade)
) ENGINE=InnoDB;

-- Vistorias (entrada, saída, periódica)
CREATE TABLE vistorias (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  imovel_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NOT NULL,
  tipo ENUM('entrada', 'saida', 'periodica', 'extra') NOT NULL DEFAULT 'entrada',
  status ENUM('rascunho', 'em_andamento', 'concluida', 'cancelada') NOT NULL DEFAULT 'rascunho',
  data_vistoria DATE NOT NULL,
  observacoes TEXT NULL,
  sincronizado_mobile TINYINT(1) NOT NULL DEFAULT 0,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_vistorias_imovel FOREIGN KEY (imovel_id) REFERENCES imoveis(id) ON DELETE RESTRICT,
  CONSTRAINT fk_vistorias_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
  INDEX idx_vistorias_imovel (imovel_id),
  INDEX idx_vistorias_data (data_vistoria)
) ENGINE=InnoDB;

-- Fotos enviadas pelo APK ou pelo painel
CREATE TABLE vistoria_fotos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vistoria_id INT UNSIGNED NOT NULL,
  comodo VARCHAR(80) NOT NULL,
  arquivo_nome VARCHAR(255) NOT NULL,
  arquivo_path VARCHAR(500) NOT NULL,
  mime_type VARCHAR(80) NULL,
  tamanho_bytes INT UNSIGNED NULL,
  latitude DECIMAL(10, 8) NULL,
  longitude DECIMAL(11, 8) NULL,
  origem ENUM('mobile', 'web') NOT NULL DEFAULT 'mobile',
  observacao VARCHAR(500) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fotos_vistoria FOREIGN KEY (vistoria_id) REFERENCES vistorias(id) ON DELETE CASCADE,
  INDEX idx_fotos_vistoria (vistoria_id),
  INDEX idx_fotos_comodo (comodo)
) ENGINE=InnoDB;

-- Contratos
CREATE TABLE contratos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  imovel_id INT UNSIGNED NOT NULL,
  numero VARCHAR(50) NOT NULL UNIQUE,
  locatario_nome VARCHAR(200) NOT NULL,
  locatario_documento VARCHAR(20) NULL,
  valor_aluguel DECIMAL(12, 2) NOT NULL,
  data_inicio DATE NOT NULL,
  data_fim DATE NULL,
  status ENUM('rascunho', 'ativo', 'encerrado', 'cancelado') NOT NULL DEFAULT 'rascunho',
  observacoes TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_contratos_imovel FOREIGN KEY (imovel_id) REFERENCES imoveis(id) ON DELETE RESTRICT,
  INDEX idx_contratos_status (status)
) ENGINE=InnoDB;

CREATE TABLE contrato_anexos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contrato_id INT UNSIGNED NOT NULL,
  titulo VARCHAR(200) NOT NULL,
  arquivo_nome VARCHAR(255) NOT NULL,
  arquivo_path VARCHAR(500) NOT NULL,
  tipo ENUM('contrato', 'aditivo', 'comprovante', 'outro') NOT NULL DEFAULT 'contrato',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_anexos_contrato FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Problemas / pendências encontradas na vistoria
CREATE TABLE problemas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  imovel_id INT UNSIGNED NOT NULL,
  vistoria_id INT UNSIGNED NULL,
  titulo VARCHAR(200) NOT NULL,
  descricao TEXT NULL,
  prioridade ENUM('baixa', 'media', 'alta', 'urgente') NOT NULL DEFAULT 'media',
  status ENUM('aberto', 'em_analise', 'resolvido', 'cancelado') NOT NULL DEFAULT 'aberto',
  criado_por INT UNSIGNED NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolvido_em DATETIME NULL,
  CONSTRAINT fk_problemas_imovel FOREIGN KEY (imovel_id) REFERENCES imoveis(id) ON DELETE RESTRICT,
  CONSTRAINT fk_problemas_vistoria FOREIGN KEY (vistoria_id) REFERENCES vistorias(id) ON DELETE SET NULL,
  CONSTRAINT fk_problemas_usuario FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Tokens para o app Kotlin (API Bearer)
CREATE TABLE api_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  dispositivo VARCHAR(120) NULL,
  expira_em DATETIME NULL,
  ultimo_uso DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  revogado TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_tokens_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Após importar, acesse uma vez: /public/install.php (cria admin@onecheck.local / admin123)
