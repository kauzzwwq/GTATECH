-- Execute uma vez no banco notas_animais do GTATECH.
-- Usa tabelas próprias para não alterar nem sobrescrever tabelas do sistema GTA antigo.
USE notas_animais;

CREATE TABLE IF NOT EXISTS `rfid_cartoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `uid` VARCHAR(64) NOT NULL,
  `descricao` VARCHAR(150) NULL,
  `motorista_id` INT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_rfid_cartoes_uid` (`uid`),
  UNIQUE KEY `uq_rfid_cartoes_motorista` (`motorista_id`),
  CONSTRAINT `fk_rfid_cartoes_motorista` FOREIGN KEY (`motorista_id`)
    REFERENCES `motoristas`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rfid_leituras` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tag_uid` VARCHAR(64) NOT NULL,
  `cartao_id` INT NULL,
  `motorista_id` INT NULL,
  `nota_id` INT NULL,
  `resultado` VARCHAR(30) NOT NULL,
  `ip_leitor` VARCHAR(45) NULL,
  `data_hora` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_rfid_leituras_data` (`data_hora`),
  KEY `idx_rfid_leituras_cartao` (`cartao_id`),
  KEY `idx_rfid_leituras_motorista` (`motorista_id`),
  KEY `idx_rfid_leituras_nota` (`nota_id`),
  CONSTRAINT `fk_rfid_leituras_cartao` FOREIGN KEY (`cartao_id`)
    REFERENCES `rfid_cartoes`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rfid_leituras_motorista` FOREIGN KEY (`motorista_id`)
    REFERENCES `motoristas`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rfid_leituras_nota` FOREIGN KEY (`nota_id`)
    REFERENCES `notas`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
