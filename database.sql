-- ==============================================================================
-- SISTEMA DE NOTAS, TRANSPORTE, PESAGEM E CONTROL DE ABATE ANIMAL
-- Arquivo completo de criação do Banco de Dados (Do Zero)
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `notas_animais` 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `notas_animais`;

-- ------------------------------------------------------------------------------
-- 1. USUÁRIOS E PERMISSÕES (Expandido para Produtor, Motorista e Destinatário)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `senha_hash` VARCHAR(255) NOT NULL,
  `perfil` ENUM('admin', 'trabalhador', 'produtor', 'motorista', 'destinatario') NOT NULL DEFAULT 'trabalhador',
  `telefone` VARCHAR(20) NULL,
  `cpf_cnpj` VARCHAR(20) NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. RECUPERAÇÃO DE SENHA
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `recuperacao_senha` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `expira_em` DATETIME NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. CADASTROS REUTILIZÁVEIS E CADASTRO DE MOTORISTAS
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `produtores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NULL, -- Vincula à conta de login se o produtor acessar o sistema
  `produtor` VARCHAR(150) NOT NULL,
  `cpf_cnpj` VARCHAR(20),
  `uep` VARCHAR(150),
  `codigo_uep` VARCHAR(50),
  `numero_uep` VARCHAR(50),
  `localizacao` VARCHAR(255) NULL, -- Endereço ou coordenadas (usado na rota do Google Maps)
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `destinatarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NULL, -- Vincula à conta de login para o destinatário/frigorífico visualizar
  `estabelecimento` VARCHAR(150) NOT NULL,
  `codigo_oficial` VARCHAR(50),
  `proprietario` VARCHAR(150),
  `cpf_cnpj` VARCHAR(20),
  `localidade` VARCHAR(150),
  `municipio` VARCHAR(100),
  `localizacao` VARCHAR(255) NULL, -- Endereço ou coordenadas (usado na rota do Google Maps)
  `is_frigorifico` TINYINT(1) DEFAULT 0, -- Identifica se é frigorífico/abatedouro
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `motoristas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NULL, -- Vincula à conta de login para o motorista acessar suas corridas
  `nome` VARCHAR(150) NOT NULL,
  `cpf` VARCHAR(20) NULL,
  `cnh` VARCHAR(20) NULL,
  `telefone` VARCHAR(20) NULL,
  `placa_veiculo` VARCHAR(10) NULL,
  `modelo_veiculo` VARCHAR(50) NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. NOTAS E LOGÍSTICA DE TRANSPORTE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `numero_ano` SMALLINT NOT NULL,       -- Número oficial: ANO/SEQUÊNCIA
  `numero_seq` INT NOT NULL,
  `usuario_id` INT NOT NULL,            -- Quem criou a nota
  `trabalhador_id` INT NULL,            -- Trabalhador atribuído (se houver)
  `motorista_id` INT NULL,              -- Motorista responsável pelo transporte
  `produtor_id` INT NULL,               -- Origem (Produtor)
  `destinatario_id` INT NULL,           -- Destino (Propriedade ou Frigorífico)
  
  `status` ENUM('rascunho','emitida','em_transito','entregue','concluida','cancelada') NOT NULL DEFAULT 'emitida',
  `tipo_operacao` ENUM('transporte','comercializacao','abate') NOT NULL DEFAULT 'transporte',

  -- Destino (Snapshots / Dados preenchidos na emissão)
  `destino_evento` VARCHAR(150),
  `destino_estabelecimento` VARCHAR(150),
  `destino_codigo_oficial` VARCHAR(50),
  `destino_proprietario` VARCHAR(150),
  `destino_cpf_cnpj` VARCHAR(20),
  `destino_localidade` VARCHAR(150),
  `destino_municipio` VARCHAR(100),
  `destino_localizacao` VARCHAR(255) NULL,

  -- Origem (Snapshots / Dados preenchidos na emissão)
  `origem_uep` VARCHAR(150),
  `origem_codigo_uep` VARCHAR(50),
  `origem_numero_uep` VARCHAR(50),
  `origem_produtor` VARCHAR(150),
  `origem_cpf_cnpj` VARCHAR(20),
  `origem_localizacao` VARCHAR(255) NULL,

  -- Transporte & Carga
  `especie` VARCHAR(80) NULL,
  `quantidade` INT UNSIGNED NULL,
  `finalidade` VARCHAR(50) NULL,
  `data_saida` DATETIME NULL,
  `placa` VARCHAR(8) NULL,
  `motorista` VARCHAR(120) NULL, -- Nome digitado ou espelhado do motorista cadastrado
  `validade` DATE NULL,
  `gta_numero` VARCHAR(50) NULL, -- Número da Guia de Trânsito Animal

  -- Baixa (recebimento pelo destinatário)
  `recebida_em` DATETIME NULL,
  `recebida_por` INT NULL,
  `recebida_quantidade` INT UNSIGNED NULL,
  `recebimento_obs` VARCHAR(255) NULL,
  
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  UNIQUE KEY `uq_numero` (`numero_ano`, `numero_seq`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notas_trabalhador` FOREIGN KEY (`trabalhador_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_notas_motorista` FOREIGN KEY (`motorista_id`) REFERENCES `motoristas`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_notas_produtor` FOREIGN KEY (`produtor_id`) REFERENCES `produtores`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_notas_destinatario` FOREIGN KEY (`destinatario_id`) REFERENCES `destinatarios`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_notas_recebida_por` FOREIGN KEY (`recebida_por`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. CONTROLE RFID
-- ------------------------------------------------------------------------------
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

-- ------------------------------------------------------------------------------
-- 6. ANEXOS / FOTOS DAS NOTAS (Tabela vinda dos arquivos anteriores do projeto)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `nota_fotos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nota_id` INT NOT NULL,
  `caminho_arquivo` VARCHAR(255) NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`nota_id`) REFERENCES `notas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 7. MÓDULO DE CONTAGEM E PESAGEM DE ANIMAIS
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `animais_pesagem` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nota_id` INT NULL,                    -- Opcional: vincula a pesagem a uma nota específica
  `produtor_id` INT NULL,                 -- Origem do lote
  `lote_codigo` VARCHAR(50) NOT NULL,     -- Código ou nome do lote
  `categoria` VARCHAR(50) NOT NULL,       -- Ex: Bezerro, Vaca, Boi Gordo, Novilha
  `quantidade_cabecas` INT NOT NULL,
  `peso_total_kg` DECIMAL(10,2) NOT NULL,
  `peso_medio_kg` DECIMAL(10,2) GENERATED ALWAYS AS (`peso_total_kg` / `quantidade_cabecas`) STORED,
  `data_pesagem` DATETIME NOT NULL,
  `observacoes` TEXT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`nota_id`) REFERENCES `notas`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`produtor_id`) REFERENCES `produtores`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 8. MÓDULO DE REGISTRO E CONTROLE DE ABATE (Frigorífico)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `abates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nota_id` INT NOT NULL,                 -- Nota fiscal / GTA que deu entrada no abate
  `destinatario_id` INT NOT NULL,         -- Frigorífico / Abatedouro
  `data_agendada` DATE NOT NULL,
  `data_realizada` DATETIME NULL,
  `quantidade_recebida` INT UNSIGNED NOT NULL,
  `quantidade_abatida` INT UNSIGNED NULL,
  `peso_carcaca_total_kg` DECIMAL(10,2) NULL, -- Peso total obtido após o abate
  `rendimento_medio_perc` DECIMAL(5,2) NULL,  -- Rendimento de carcaça em %
  `status_abate` ENUM('agendado','em_processamento','concluido','rejeitado') DEFAULT 'agendado',
  `observacoes_sanitarias` TEXT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`nota_id`) REFERENCES `notas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`destinatario_id`) REFERENCES `destinatarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;