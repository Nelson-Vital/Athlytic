-- ============================================================
-- ATHLYTIC - Atualização 1 (para quem já importou athlytic.sql antes)
-- Mantém os dados existentes. Importar no phpMyAdmin com a BD "athlytic" selecionada.
-- (Quem preferir começar do zero pode simplesmente voltar a importar athlytic.sql.)
-- ============================================================
SET NAMES utf8mb4;
USE athlytic;

-- 1. Meta de passos removida
ALTER TABLE utilizadores DROP COLUMN meta_passos;

-- 2. Concluir um treino do plano passa a criar uma atividade
ALTER TABLE plano_concluidos
    ADD COLUMN atividade_id INT NULL,
    ADD CONSTRAINT fk_concluido_atividade FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE;

-- 3. Histórico de pesagens
CREATE TABLE pesagens (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id  INT NOT NULL,
    data           DATE NOT NULL,
    peso           DECIMAL(5,1) NOT NULL,
    massa_gorda    DECIMAL(4,1) NULL,
    UNIQUE KEY uk_pesagem_dia (utilizador_id, data),
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- O peso atual de cada utilizador passa a ser a primeira pesagem do histórico
INSERT INTO pesagens (utilizador_id, data, peso, massa_gorda)
SELECT id, CURDATE(), peso_atual, massa_gorda FROM utilizadores WHERE peso_atual IS NOT NULL;
