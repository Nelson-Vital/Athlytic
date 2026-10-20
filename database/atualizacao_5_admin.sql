-- ============================================================
-- ATHLYTIC - Atualização 5: administradores e contas inativas
-- Não é preciso importar: o site aplica-a sozinho (includes/migracoes.php, versão 5).
-- Fica aqui como documentação da alteração à base de dados.
-- ============================================================
SET NAMES utf8mb4;
USE athlytic;

-- 1. Papel e estado de cada conta
ALTER TABLE utilizadores
    ADD COLUMN papel ENUM('utilizador', 'admin') NOT NULL DEFAULT 'utilizador' AFTER password_hash,
    ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER papel;   -- 0 = inativada por um administrador

-- 2. Histórico das ações dos administradores (inativar exige motivo, validado no PHP)
CREATE TABLE estado_contas (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id  INT NOT NULL,                 -- conta afetada
    admin_id       INT NULL,                     -- quem fez a ação (NULL se essa conta for apagada)
    acao           ENUM('inativar', 'reativar', 'promover', 'despromover') NOT NULL,
    motivo         VARCHAR(500) NULL,
    data           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_estado_utilizador (utilizador_id, data),
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES utilizadores(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. Tem de existir sempre pelo menos um administrador ativo:
--    se ainda não houver nenhum, a conta mais antiga passa a ser administradora.
UPDATE utilizadores SET papel = 'admin', ativo = 1
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM utilizadores WHERE papel = 'admin' AND ativo = 1) AS a)
ORDER BY id LIMIT 1;

UPDATE versao_bd SET versao = 5;
