-- ============================================================
-- ATHLYTIC - Base de dados
-- Importar no phpMyAdmin (separador "Importar") ou com:
--   mysql -u root < database/athlytic.sql
-- ============================================================

SET NAMES utf8mb4;

DROP DATABASE IF EXISTS athlytic;
CREATE DATABASE athlytic CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE athlytic;

-- ------------------------------------------------------------
-- Planos (corrida, musculação, alimentação) e os seus dias
-- ------------------------------------------------------------
CREATE TABLE planos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tipo        ENUM('corrida', 'musculacao', 'alimentacao') NOT NULL,
    nome        VARCHAR(120) NOT NULL,
    descricao   VARCHAR(255) NOT NULL,
    nivel       VARCHAR(40)  NOT NULL
) ENGINE=InnoDB;

CREATE TABLE plano_dias (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    plano_id    INT NOT NULL,
    dia_semana  TINYINT NOT NULL,              -- 1 = Segunda ... 7 = Domingo
    titulo      VARCHAR(120) NOT NULL,
    conteudo    TEXT NOT NULL,                 -- uma linha por exercício / refeição
    descanso    TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uk_plano_dia (plano_id, dia_semana),
    FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Utilizadores (dados pessoais + preferências + plano escolhido)
-- ------------------------------------------------------------
CREATE TABLE utilizadores (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    nome                  VARCHAR(100) NOT NULL,
    email                 VARCHAR(150) NOT NULL UNIQUE,
    password_hash         VARCHAR(255) NOT NULL,
    papel                 ENUM('utilizador', 'admin') NOT NULL DEFAULT 'utilizador',
    ativo                 TINYINT(1) NOT NULL DEFAULT 1,     -- 0 = conta inativada por um administrador
    data_nascimento       DATE NULL,
    sexo                  ENUM('M', 'F') NULL,
    altura_cm             SMALLINT NULL,
    peso_atual            DECIMAL(5,1) NULL,
    massa_gorda           DECIMAL(4,1) NULL,
    peso_objetivo         DECIMAL(5,1) NULL,
    massa_gorda_objetivo  DECIMAL(4,1) NULL,
    meta_km_semana        DECIMAL(5,1) NOT NULL DEFAULT 20,
    meta_treinos_semana   TINYINT NOT NULL DEFAULT 4,
    objetivo              ENUM('perda_peso', 'recomposicao', 'ganho_massa', 'manutencao') NOT NULL DEFAULT 'recomposicao',
    intensidade_padrao    ENUM('leve', 'moderada', 'alta') NOT NULL DEFAULT 'moderada',
    tema                  ENUM('claro', 'escuro') NOT NULL DEFAULT 'claro',
    lembrete_treino       TINYINT(1) NOT NULL DEFAULT 1,
    alerta_meta           TINYINT(1) NOT NULL DEFAULT 1,
    notif_planos          TINYINT(1) NOT NULL DEFAULT 0,
    plano_corrida_id      INT NULL,
    plano_musculacao_id   INT NULL,
    plano_alimentacao_id  INT NULL,
    criado_em             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plano_corrida_id)     REFERENCES planos(id) ON DELETE SET NULL,
    FOREIGN KEY (plano_musculacao_id)  REFERENCES planos(id) ON DELETE SET NULL,
    FOREIGN KEY (plano_alimentacao_id) REFERENCES planos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Atividades registadas pelo utilizador
-- ------------------------------------------------------------
CREATE TABLE atividades (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id  INT NOT NULL,
    tipo           ENUM('corrida', 'musculacao', 'caminhada', 'funcional', 'ciclismo', 'natacao', 'outro') NOT NULL,
    intensidade    ENUM('leve', 'moderada', 'alta') NOT NULL,
    data           DATE NOT NULL,
    hora           TIME NOT NULL,
    duracao_seg    INT NOT NULL,          -- duração em segundos
    distancia_km   DECIMAL(6,2) NULL,
    notas          TEXT NULL,
    ficheiro_fit   VARCHAR(255) NULL,
    criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_utilizador_data (utilizador_id, data),
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE atividade_fotos (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    atividade_id  INT NOT NULL,
    caminho       VARCHAR(255) NOT NULL,
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Dias do plano marcados como concluídos
-- ------------------------------------------------------------
-- Ao concluir um treino é criada uma atividade; se a atividade for apagada, a marcação também é
CREATE TABLE plano_concluidos (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id  INT NOT NULL,
    plano_id       INT NOT NULL,
    data           DATE NOT NULL,
    atividade_id   INT NULL,
    UNIQUE KEY uk_concluido (utilizador_id, plano_id, data),
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE CASCADE,
    FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Histórico de pesagens (peso e % de massa gorda ao longo do tempo)
-- ------------------------------------------------------------
CREATE TABLE pesagens (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id  INT NOT NULL,
    data           DATE NOT NULL,
    peso           DECIMAL(5,1) NOT NULL,
    massa_gorda    DECIMAL(4,1) NULL,
    UNIQUE KEY uk_pesagem_dia (utilizador_id, data),
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Versão da estrutura (usada pelas atualizações automáticas em includes/migracoes.php)
-- Planos criados pelo utilizador para um dia concreto (até 7 dias à frente; apagados 7 dias depois)
CREATE TABLE planos_pessoais (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id  INT NOT NULL,
    categoria      ENUM('treino', 'alimentacao') NOT NULL,
    modalidade     ENUM('corrida', 'natacao', 'ciclismo', 'musculacao') NULL,
    refeicao       ENUM('pequeno_almoco', 'almoco', 'lanche', 'jantar', 'dia') NULL,
    data           DATE NOT NULL,
    titulo         VARCHAR(100) NOT NULL,
    descricao      TEXT NULL,
    substitui_plano TINYINT(1) NOT NULL DEFAULT 0,
    concluido      TINYINT(1) NOT NULL DEFAULT 0,
    criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pessoal_data (utilizador_id, data),
    INDEX idx_pessoal_limpeza (data),
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Histórico de inativações/reativações e mudanças de papel feitas pelos administradores
CREATE TABLE estado_contas (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id  INT NOT NULL,
    admin_id       INT NULL,
    acao           ENUM('inativar', 'reativar', 'promover', 'despromover') NOT NULL,
    motivo         VARCHAR(500) NULL,
    data           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_estado_utilizador (utilizador_id, data),
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES utilizadores(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE versao_bd (versao INT NOT NULL) ENGINE=InnoDB;
INSERT INTO versao_bd (versao) VALUES (7);

-- ============================================================
-- Dados iniciais: planos
-- ============================================================
INSERT INTO planos (id, tipo, nome, descricao, nivel) VALUES
(1, 'corrida',     'Plano Iniciante – 3x por semana',            'Para quem está a começar e quer correr 5 km sem parar.',             'Iniciante'),
(2, 'corrida',     'Plano Intermédio – 4x por semana',           'Para quem já corre regularmente e quer evoluir.',                     'Intermédio'),
(3, 'musculacao',  'Full Body – 3x por semana',                  'Treino de corpo inteiro, ideal para iniciantes ou pouco tempo.',      'Iniciante'),
(4, 'musculacao',  'Push / Pull / Legs – 6x por semana',         'Split clássico muito eficaz para ganhos de força e hipertrofia.',      'Intermédio'),
(5, 'alimentacao', 'Manutenção / Recomposição',                  'Calorias de manutenção. Proteína 2 g/kg, hidratos e gorduras equilibrados.', 'Todos'),
(6, 'alimentacao', 'Perda de Peso (défice ligeiro)',             'Défice de cerca de 400 kcal. Proteína alta para preservar músculo.',  'Todos');

-- Corrida Iniciante (1)
INSERT INTO plano_dias (plano_id, dia_semana, titulo, conteudo, descanso) VALUES
(1, 1, 'Corrida / Caminhada', 'Aquecimento 5 min a andar\n8× (1 min a correr / 2 min a andar)\nRetorno à calma 5 min', 0),
(1, 2, 'Descanso',            'Descanso ou alongamentos 10 min', 1),
(1, 3, 'Corrida / Caminhada', 'Aquecimento 5 min a andar\n6× (2 min a correr / 2 min a andar)\nRetorno à calma 5 min', 0),
(1, 4, 'Descanso',            'Descanso ou caminhada leve 30 min', 1),
(1, 5, 'Descanso',            'Mobilidade de ancas e tornozelos 10 min', 1),
(1, 6, 'Corrida contínua',    'Aquecimento 5 min a andar\n15–20 min de corrida leve contínua\nRetorno à calma 5 min', 0),
(1, 7, 'Descanso',            'Descanso total', 1);

-- Corrida Intermédio (2)
INSERT INTO plano_dias (plano_id, dia_semana, titulo, conteudo, descanso) VALUES
(2, 1, 'Descanso',        'Descanso ou mobilidade 15 min', 1),
(2, 2, 'Intervalado',     'Aquecimento 10 min\n6× (1 min rápido / 2 min trote)\nDesaquecimento 10 min', 0),
(2, 3, 'Rodagem leve',    '35–40 min em ritmo confortável (consegues falar)', 0),
(2, 4, 'Descanso',        'Descanso ou treino de força para pernas', 1),
(2, 5, 'Tempo run',       'Aquecimento 10 min\n20 min a ritmo de prova de 10 km\nDesaquecimento 10 min', 0),
(2, 6, 'Descanso',        'Descanso total', 1),
(2, 7, 'Corrida longa',   '60–75 min em ritmo leve\nHidratação a cada 20 min', 0);

-- Musculação Full Body (3)
INSERT INTO plano_dias (plano_id, dia_semana, titulo, conteudo, descanso) VALUES
(3, 1, 'Full Body A', 'Agachamento 4×8-10\nSupino plano 4×8-10\nRemada curvada 3×10\nPress militar 3×10\nPrancha 3×45 s', 0),
(3, 2, 'Descanso',    'Descanso ou cardio leve 20 min', 1),
(3, 3, 'Full Body B', 'Peso morto 4×6-8\nSupino inclinado halteres 3×10\nPuxada à frente 3×10-12\nElevações laterais 3×15\nAbdominais 3×15', 0),
(3, 4, 'Descanso',    'Descanso ou caminhada 30 min', 1),
(3, 5, 'Full Body C', 'Leg press 4×10-12\nFlexões 3×máx.\nRemada unilateral 3×10\nRosca direta 3×12\nTríceps na polia 3×12', 0),
(3, 6, 'Descanso',    'Descanso total', 1),
(3, 7, 'Descanso',    'Descanso total', 1);

-- Musculação PPL (4)
INSERT INTO plano_dias (plano_id, dia_semana, titulo, conteudo, descanso) VALUES
(4, 1, 'Push (Peito, Ombros, Tríceps)', 'Supino plano 4×6-8\nSupino inclinado halteres 3×8-10\nPress militar 4×8-10\nElevações laterais 3×15\nTríceps na polia 3×12\nMergulhos 3×máx.', 0),
(4, 2, 'Pull (Costas, Bíceps)',         'Barra fixa / Pulldown 4×8-12\nRemada curvada barra 4×8-10\nRemada unilateral 3×10-12\nFace pull 3×15\nRosca direta EZ 4×10-12\nRosca martelo 3×12', 0),
(4, 3, 'Legs (Pernas)',                 'Agachamento 4×6-8\nPeso morto romeno 4×8-10\nLeg press 3×10-12\nExtensão de pernas 3×12-15\nCurl femoral 3×12\nGémeos 4×15', 0),
(4, 4, 'Push (Peito, Ombros, Tríceps)', 'Supino inclinado barra 4×8\nCrucifixo máquina 3×12\nPress Arnold 3×10\nElevações laterais 4×15\nExtensão de tríceps 3×12', 0),
(4, 5, 'Pull (Costas, Bíceps)',         'Remada T 4×8-10\nPuxada pega estreita 3×10-12\nPullover polia 3×12\nRosca inclinada 3×10\nRosca concentrada 3×12', 0),
(4, 6, 'Legs (Pernas)',                 'Agachamento frontal 4×8\nHip thrust 4×10\nAfundos 3×12 por perna\nCurl femoral 3×12\nGémeos sentado 4×15', 0),
(4, 7, 'Descanso',                      'Descanso total e recuperação', 1);

-- Alimentação Manutenção (5)
INSERT INTO plano_dias (plano_id, dia_semana, titulo, conteudo, descanso) VALUES
(5, 1, 'Refeições do dia', 'Pequeno-almoço: 3 ovos inteiros + 50 g aveia + 1 banana\nLanche manhã: 150 g iogurte grego + 15 g frutos secos\nAlmoço: 180 g frango, 100 g arroz integral, legumes + 10 ml azeite\nLanche tarde: Sandes integral + 100 g fiambre de peru + 30 g queijo magro\nJantar: 180 g dourada grelhada, 200 g batata-doce, salada\nCeia: 200 g quark + 10 g chocolate 85%', 0),
(5, 2, 'Refeições do dia', 'Pequeno-almoço: Panquecas de aveia (60 g) com 2 ovos e mel\nLanche manhã: 1 maçã + 20 g manteiga de amendoim\nAlmoço: 180 g peru, 120 g massa integral, brócolos\nLanche tarde: Batido de whey + 1 banana\nJantar: 180 g salmão, 150 g arroz basmati, espargos\nCeia: 150 g iogurte grego natural', 0),
(5, 3, 'Refeições do dia', 'Pequeno-almoço: Torradas integrais + 2 ovos mexidos + abacate\nLanche manhã: 150 g requeijão + frutos vermelhos\nAlmoço: 180 g carne de vaca magra, 200 g batata, salada\nLanche tarde: Iogurte proteico + 30 g granola\nJantar: Omelete de 3 ovos com espinafres + pão integral\nCeia: 200 g quark', 0),
(5, 4, 'Refeições do dia', 'Pequeno-almoço: 50 g aveia + whey + frutos vermelhos\nLanche manhã: 2 tostas de arroz + fiambre de peru\nAlmoço: 180 g frango ao curry, 100 g arroz, legumes\nLanche tarde: 1 pera + 15 g amêndoas\nJantar: 200 g pescada, 200 g batata cozida, legumes\nCeia: 150 g iogurte grego', 0),
(5, 5, 'Refeições do dia', 'Pequeno-almoço: 3 ovos + 2 fatias pão integral + laranja\nLanche manhã: Batido de whey + aveia\nAlmoço: 150 g atum, 120 g massa, tomate e milho\nLanche tarde: 150 g iogurte grego + mel\nJantar: Hambúrguer caseiro de vaca + batata-doce no forno\nCeia: 200 g quark + canela', 0),
(5, 6, 'Refeições do dia', 'Pequeno-almoço: Panquecas de banana e aveia\nLanche manhã: Fruta da época + frutos secos\nAlmoço: Refeição livre equilibrada (prato com metade de legumes)\nLanche tarde: Iogurte proteico\nJantar: 180 g frango grelhado, quinoa, salada\nCeia: Chá + 10 g chocolate 85%', 0),
(5, 7, 'Refeições do dia', 'Pequeno-almoço: 2 ovos + abacate + pão de centeio\nLanche manhã: 150 g iogurte grego\nAlmoço: 180 g peixe assado, 200 g batata, legumes\nLanche tarde: Batido de whey + banana\nJantar: Sopa de legumes + 150 g frango desfiado\nCeia: 200 g quark', 0);

-- Alimentação Perda de Peso (6)
INSERT INTO plano_dias (plano_id, dia_semana, titulo, conteudo, descanso) VALUES
(6, 1, 'Refeições do dia', 'Pequeno-almoço: 2 ovos + 1 fatia pão integral\nAlmoço: 150 g frango, 80 g arroz, legumes à vontade\nLanche: 150 g iogurte grego magro\nJantar: 180 g pescada, legumes salteados\nCeia: Chá de ervas', 0),
(6, 2, 'Refeições do dia', 'Pequeno-almoço: 40 g aveia + whey\nAlmoço: 150 g peru, salada grande + 80 g grão\nLanche: 1 maçã + 10 g amêndoas\nJantar: Omelete de 2 ovos + 3 claras com legumes\nCeia: 150 g quark', 0),
(6, 3, 'Refeições do dia', 'Pequeno-almoço: Iogurte grego + frutos vermelhos\nAlmoço: 150 g atum, 100 g batata-doce, salada\nLanche: Batido de whey com água\nJantar: Sopa de legumes + 150 g frango\nCeia: Chá de ervas', 0),
(6, 4, 'Refeições do dia', 'Pequeno-almoço: 2 ovos + tomate + 1 tosta\nAlmoço: 150 g frango, 80 g massa integral, brócolos\nLanche: 150 g requeijão\nJantar: 180 g salmão, espargos\nCeia: 150 g iogurte magro', 0),
(6, 5, 'Refeições do dia', 'Pequeno-almoço: 40 g aveia + 1 banana pequena\nAlmoço: 150 g carne magra, salada + 100 g batata\nLanche: 1 pera + whey\nJantar: 200 g dourada, legumes no forno\nCeia: Chá de ervas', 0),
(6, 6, 'Refeições do dia', 'Pequeno-almoço: Panqueca de claras e aveia\nAlmoço: Refeição livre moderada\nLanche: 150 g iogurte grego magro\nJantar: Salada de frango grelhado\nCeia: 150 g quark', 0),
(6, 7, 'Refeições do dia', 'Pequeno-almoço: 2 ovos + abacate (1/4)\nAlmoço: 150 g peixe, 100 g batata, legumes\nLanche: Fruta + 10 g frutos secos\nJantar: Sopa + 120 g peru\nCeia: Chá de ervas', 0);

-- ============================================================
-- Utilizador de demonstração
--   email: demo@athlytic.pt   palavra-passe: demo1234
-- ============================================================
INSERT INTO utilizadores (id, nome, email, password_hash, papel, data_nascimento, sexo, altura_cm, peso_atual, massa_gorda,
                          peso_objetivo, massa_gorda_objetivo, meta_km_semana, meta_treinos_semana,
                          objetivo, intensidade_padrao, plano_corrida_id, plano_musculacao_id, plano_alimentacao_id)
VALUES (1, 'Nelson Vital', 'demo@athlytic.pt', '$2y$10$RKQKmvvCvD1xCxX1ZpAEPOt3Tq4oZeoKMsEPzXoY/v28AlLdyNSfa', 'admin', '2003-02-20', 'M', 176, 78.0, 15.0,
        72.0, 10.0, 20, 5, 'recomposicao', 'moderada', 2, 4, 5);

-- Segunda conta de exemplo (utilizador normal), útil para testar a área de administração
--   email: ana@athlytic.pt   palavra-passe: demo1234
INSERT INTO utilizadores (id, nome, email, password_hash, papel, data_nascimento, sexo, altura_cm, peso_atual, massa_gorda,
                          objetivo, intensidade_padrao, plano_corrida_id, plano_musculacao_id, plano_alimentacao_id)
VALUES (2, 'Ana Costa', 'ana@athlytic.pt', '$2y$10$RKQKmvvCvD1xCxX1ZpAEPOt3Tq4oZeoKMsEPzXoY/v28AlLdyNSfa', 'utilizador', '1999-07-11', 'F', 165, 61.0, 23.0,
        'manutencao', 'moderada', 1, 3, 5);

INSERT INTO atividades (utilizador_id, tipo, intensidade, data, hora, duracao_seg, distancia_km, notas) VALUES
(2, 'natacao',  'moderada', CURDATE() - INTERVAL 1 DAY, '19:00', 2520, 1.60, NULL),
(2, 'corrida',  'leve',     CURDATE() - INTERVAL 3 DAY, '08:00', 1950, 5.10, 'Corrida no parque.'),
(2, 'ciclismo', 'alta',     CURDATE() - INTERVAL 5 DAY, '10:30', 5400, 38.00, NULL);

-- Algumas atividades de exemplo (datas relativas a hoje)
INSERT INTO atividades (utilizador_id, tipo, intensidade, data, hora, duracao_seg, distancia_km, notas) VALUES
(1, 'corrida',    'alta',     CURDATE() - INTERVAL 1 DAY, '07:30', 2700, 8.20, 'Boas sensações, último km mais rápido.'),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 2 DAY, '18:00', 3900, NULL, 'Push. Novo máximo no supino.'),
(1, 'natacao',    'leve',     CURDATE() - INTERVAL 4 DAY, '19:00', 2400, 1.50, NULL);

-- Mais atividades de exemplo (últimas 12 semanas) para os gráficos do Histórico
INSERT INTO atividades (utilizador_id, tipo, intensidade, data, hora, duracao_seg, distancia_km, notas) VALUES
(1, 'corrida', 'alta', CURDATE() - INTERVAL 8 DAY, '07:30', 2520, 7.79, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 10 DAY, '07:30', 1980, 6.18, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 13 DAY, '07:30', 4620, 14.04, NULL),
(1, 'natacao', 'leve', CURDATE() - INTERVAL 9 DAY, '19:00', 2040, 1.8, NULL),
(1, 'ciclismo', 'moderada', CURDATE() - INTERVAL 12 DAY, '10:00', 5100, 40.5, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 7 DAY, '18:30', 3540, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 9 DAY, '18:30', 3180, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 11 DAY, '18:30', 3180, NULL, NULL),
(1, 'corrida', 'alta', CURDATE() - INTERVAL 15 DAY, '07:30', 2640, 7.73, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 17 DAY, '07:30', 1800, 5.46, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 20 DAY, '07:30', 4680, 13.79, NULL),
(1, 'natacao', 'leve', CURDATE() - INTERVAL 16 DAY, '19:00', 2340, 1.74, NULL),
(1, 'ciclismo', 'moderada', CURDATE() - INTERVAL 19 DAY, '10:00', 5580, 39.3, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 14 DAY, '18:30', 3180, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 16 DAY, '18:30', 3960, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 18 DAY, '18:30', 3420, NULL, NULL),
(1, 'corrida', 'alta', CURDATE() - INTERVAL 22 DAY, '07:30', 2400, 7.21, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 24 DAY, '07:30', 1920, 5.59, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 27 DAY, '07:30', 4380, 12.89, NULL),
(1, 'natacao', 'leve', CURDATE() - INTERVAL 23 DAY, '19:00', 2340, 1.7, NULL),
(1, 'ciclismo', 'moderada', CURDATE() - INTERVAL 26 DAY, '10:00', 5220, 38.1, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 21 DAY, '18:30', 3180, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 23 DAY, '18:30', 3180, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 25 DAY, '18:30', 3300, NULL, NULL),
(1, 'corrida', 'alta', CURDATE() - INTERVAL 29 DAY, '07:30', 2580, 7.67, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 31 DAY, '07:30', 1860, 5.5, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 34 DAY, '07:30', 4320, 12.85, NULL),
(1, 'natacao', 'leve', CURDATE() - INTERVAL 30 DAY, '19:00', 2400, 1.73, NULL),
(1, 'ciclismo', 'moderada', CURDATE() - INTERVAL 33 DAY, '10:00', 4920, 36.9, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 28 DAY, '18:30', 3660, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 30 DAY, '18:30', 3600, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 32 DAY, '18:30', 3960, NULL, NULL),
(1, 'corrida', 'alta', CURDATE() - INTERVAL 36 DAY, '07:30', 2580, 7.55, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 38 DAY, '07:30', 2100, 6.21, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 41 DAY, '07:30', 4320, 12.45, NULL),
(1, 'natacao', 'leve', CURDATE() - INTERVAL 37 DAY, '19:00', 2100, 1.65, NULL),
(1, 'ciclismo', 'moderada', CURDATE() - INTERVAL 40 DAY, '10:00', 4680, 35.7, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 35 DAY, '18:30', 3780, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 37 DAY, '18:30', 3840, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 39 DAY, '18:30', 3660, NULL, NULL),
(1, 'corrida', 'alta', CURDATE() - INTERVAL 43 DAY, '07:30', 2580, 7.54, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 45 DAY, '07:30', 2040, 5.78, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 48 DAY, '07:30', 4260, 12.28, NULL),
(1, 'natacao', 'leve', CURDATE() - INTERVAL 44 DAY, '19:00', 2460, 1.71, NULL),
(1, 'ciclismo', 'moderada', CURDATE() - INTERVAL 47 DAY, '10:00', 4860, 34.6, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 42 DAY, '18:30', 3780, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 44 DAY, '18:30', 3180, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 46 DAY, '18:30', 3780, NULL, NULL),
(1, 'corrida', 'alta', CURDATE() - INTERVAL 50 DAY, '07:30', 2520, 7.09, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 52 DAY, '07:30', 2040, 5.84, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 55 DAY, '07:30', 4140, 11.68, NULL),
(1, 'natacao', 'leve', CURDATE() - INTERVAL 51 DAY, '19:00', 2040, 1.57, NULL),
(1, 'ciclismo', 'moderada', CURDATE() - INTERVAL 54 DAY, '10:00', 4560, 33.4, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 49 DAY, '18:30', 3240, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 51 DAY, '18:30', 3180, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 53 DAY, '18:30', 3840, NULL, NULL),
(1, 'corrida', 'alta', CURDATE() - INTERVAL 57 DAY, '07:30', 2160, 6.28, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 59 DAY, '07:30', 1860, 5.23, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 62 DAY, '07:30', 3840, 10.95, NULL),
(1, 'natacao', 'leve', CURDATE() - INTERVAL 58 DAY, '19:00', 2280, 1.62, NULL),
(1, 'ciclismo', 'moderada', CURDATE() - INTERVAL 61 DAY, '10:00', 4800, 32.3, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 56 DAY, '18:30', 3960, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 58 DAY, '18:30', 3360, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 60 DAY, '18:30', 3540, NULL, NULL),
(1, 'corrida', 'alta', CURDATE() - INTERVAL 64 DAY, '07:30', 2340, 6.38, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 66 DAY, '07:30', 2040, 5.82, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 69 DAY, '07:30', 3780, 10.7, NULL),
(1, 'natacao', 'leve', CURDATE() - INTERVAL 65 DAY, '19:00', 2160, 1.51, NULL),
(1, 'ciclismo', 'moderada', CURDATE() - INTERVAL 68 DAY, '10:00', 4560, 31.2, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 63 DAY, '18:30', 3360, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 65 DAY, '18:30', 3120, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 67 DAY, '18:30', 3540, NULL, NULL),
(1, 'corrida', 'alta', CURDATE() - INTERVAL 71 DAY, '07:30', 2220, 6.21, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 73 DAY, '07:30', 2100, 5.73, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 76 DAY, '07:30', 3900, 10.75, NULL),
(1, 'natacao', 'leve', CURDATE() - INTERVAL 72 DAY, '19:00', 2340, 1.38, NULL),
(1, 'ciclismo', 'moderada', CURDATE() - INTERVAL 75 DAY, '10:00', 4680, 30.1, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 70 DAY, '18:30', 3840, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 72 DAY, '18:30', 3960, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 74 DAY, '18:30', 3900, NULL, NULL),
(1, 'corrida', 'alta', CURDATE() - INTERVAL 78 DAY, '07:30', 2160, 6.05, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 80 DAY, '07:30', 1680, 4.62, NULL),
(1, 'corrida', 'moderada', CURDATE() - INTERVAL 83 DAY, '07:30', 3480, 9.84, NULL),
(1, 'natacao', 'leve', CURDATE() - INTERVAL 79 DAY, '19:00', 2160, 1.37, NULL),
(1, 'ciclismo', 'moderada', CURDATE() - INTERVAL 82 DAY, '10:00', 4200, 29.1, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 77 DAY, '18:30', 3180, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 79 DAY, '18:30', 3120, NULL, NULL),
(1, 'musculacao', 'moderada', CURDATE() - INTERVAL 81 DAY, '18:30', 3240, NULL, NULL);

-- Pesagens de exemplo (últimas 8 semanas)
INSERT INTO pesagens (utilizador_id, data, peso, massa_gorda) VALUES
(2, CURDATE(), 61.0, 23.0),
(1, CURDATE() - INTERVAL 49 DAY, 81.2, 17.0),
(1, CURDATE() - INTERVAL 42 DAY, 80.6, 16.8),
(1, CURDATE() - INTERVAL 35 DAY, 80.1, 16.5),
(1, CURDATE() - INTERVAL 28 DAY, 79.8, 16.1),
(1, CURDATE() - INTERVAL 21 DAY, 79.2, 15.9),
(1, CURDATE() - INTERVAL 14 DAY, 78.9, 15.6),
(1, CURDATE() - INTERVAL 7 DAY,  78.4, 15.3),
(1, CURDATE(),                   78.0, 15.0);
