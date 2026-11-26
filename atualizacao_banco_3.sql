-- Rode UMA vez no banco que você já tem (criado com o SEU database.sql novo).
-- Adiciona: localização para a rota do Google Maps e os campos de baixa (recebimento).
USE notas_animais;

ALTER TABLE produtores
  ADD COLUMN localizacao VARCHAR(255) NULL;

ALTER TABLE destinatarios
  ADD COLUMN localizacao VARCHAR(255) NULL;

ALTER TABLE notas
  ADD COLUMN origem_localizacao VARCHAR(255) NULL,
  ADD COLUMN destino_localizacao VARCHAR(255) NULL,
  ADD COLUMN recebida_em DATETIME NULL,
  ADD COLUMN recebida_por INT NULL,
  ADD COLUMN recebida_quantidade INT UNSIGNED NULL,
  ADD COLUMN recebimento_obs VARCHAR(255) NULL,
  ADD CONSTRAINT fk_notas_recebida_por FOREIGN KEY (recebida_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Recuperação de senha: o link agora guarda só o "hash" do token (mais seguro).
-- Links antigos deixam de valer, então limpamos a tabela.
DELETE FROM recuperacao_senha;
