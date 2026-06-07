-- Opcional: checklist por cômodo na vistoria
USE onecheck;

CREATE TABLE IF NOT EXISTS vistoria_checklist (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vistoria_id INT UNSIGNED NOT NULL,
  comodo VARCHAR(80) NOT NULL,
  situacao ENUM('ok', 'atencao', 'problema') NOT NULL DEFAULT 'ok',
  observacao VARCHAR(500) NULL,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_vistoria_comodo (vistoria_id, comodo),
  CONSTRAINT fk_checklist_vistoria FOREIGN KEY (vistoria_id) REFERENCES vistorias(id) ON DELETE CASCADE
) ENGINE=InnoDB;
