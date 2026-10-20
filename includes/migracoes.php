<?php
/**
 * Mantém a base de dados atualizada automaticamente.
 *
 * Sempre que o site abre a ligação à base de dados:
 *  - se a base de dados "athlytic" não existir, é criada com database/athlytic.sql;
 *  - se a versão guardada na tabela versao_bd for antiga, aplicam-se as atualizações em falta
 *    (sem apagar dados).
 *
 * Para mudar a estrutura no futuro: acrescentar uma função ao array em migracoes()
 * e subir VERSAO_BD. Quem já tem a base de dados recebe a alteração na próxima visita.
 */

const VERSAO_BD = 6;

/** Lista das atualizações, por número de versão. */
function migracoes(): array
{
    return [
        // Versão 1: sem meta de passos, treinos concluídos ligados a atividades, histórico de pesagens
        1 => function (PDO $pdo): void {
            if (existe_coluna($pdo, 'utilizadores', 'meta_passos')) {
                $pdo->exec('ALTER TABLE utilizadores DROP COLUMN meta_passos');
            }
            if (!existe_coluna($pdo, 'plano_concluidos', 'atividade_id')) {
                $pdo->exec('ALTER TABLE plano_concluidos ADD COLUMN atividade_id INT NULL,
                            ADD CONSTRAINT fk_concluido_atividade FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE');
            }
            if (!existe_tabela($pdo, 'pesagens')) {
                $pdo->exec('CREATE TABLE pesagens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    utilizador_id INT NOT NULL,
                    data DATE NOT NULL,
                    peso DECIMAL(5,1) NOT NULL,
                    massa_gorda DECIMAL(4,1) NULL,
                    UNIQUE KEY uk_pesagem_dia (utilizador_id, data),
                    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
                ) ENGINE=InnoDB');
                $pdo->exec('INSERT INTO pesagens (utilizador_id, data, peso, massa_gorda)
                            SELECT id, CURDATE(), peso_atual, massa_gorda FROM utilizadores WHERE peso_atual IS NOT NULL');
            }
        },
        // Versão 2: duração guardada em segundos (permite 36:22), convertendo as atividades existentes
        2 => function (PDO $pdo): void {
            if (existe_coluna($pdo, 'atividades', 'duracao_min')) {
                if (!existe_coluna($pdo, 'atividades', 'duracao_seg')) {
                    $pdo->exec('ALTER TABLE atividades ADD COLUMN duracao_seg INT NOT NULL DEFAULT 0 AFTER hora');
                }
                $pdo->exec('UPDATE atividades SET duracao_seg = duracao_min * 60');
                $pdo->exec('ALTER TABLE atividades DROP COLUMN duracao_min');
            }
        },
        // Versão 3: sexo do utilizador (para classificar a % de gordura e o FFMI)
        3 => function (PDO $pdo): void {
            if (!existe_coluna($pdo, 'utilizadores', 'sexo')) {
                $pdo->exec("ALTER TABLE utilizadores ADD COLUMN sexo ENUM('M', 'F') NULL AFTER data_nascimento");
            }
        },
        // Versão 4: planos de treino e alimentação criados pelo próprio utilizador, por dia
        4 => function (PDO $pdo): void {
            if (!existe_tabela($pdo, 'planos_pessoais')) {
                $pdo->exec("CREATE TABLE planos_pessoais (
                        id             INT AUTO_INCREMENT PRIMARY KEY,
                        utilizador_id  INT NOT NULL,
                        categoria      ENUM('treino', 'alimentacao') NOT NULL,
                        modalidade     ENUM('corrida', 'natacao', 'ciclismo', 'musculacao') NULL,
                        data           DATE NOT NULL,
                        titulo         VARCHAR(100) NOT NULL,
                        descricao      TEXT NULL,
                        concluido      TINYINT(1) NOT NULL DEFAULT 0,
                        criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_pessoal_data (utilizador_id, data),
                        INDEX idx_pessoal_limpeza (data),
                        FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB");
            }
        },
        // Versão 5: administradores e contas inativas (com histórico e motivo)
        5 => function (PDO $pdo): void {
            if (!existe_coluna($pdo, 'utilizadores', 'papel')) {
                $pdo->exec("ALTER TABLE utilizadores
                    ADD COLUMN papel ENUM('utilizador', 'admin') NOT NULL DEFAULT 'utilizador' AFTER password_hash,
                    ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER papel");
            }
            if (!existe_tabela($pdo, 'estado_contas')) {
                $pdo->exec("CREATE TABLE estado_contas (
                        id             INT AUTO_INCREMENT PRIMARY KEY,
                        utilizador_id  INT NOT NULL,
                        admin_id       INT NULL,
                        acao           ENUM('inativar', 'reativar', 'promover', 'despromover') NOT NULL,
                        motivo         VARCHAR(500) NULL,
                        data           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_estado_utilizador (utilizador_id, data),
                        FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
                        FOREIGN KEY (admin_id) REFERENCES utilizadores(id) ON DELETE SET NULL
                    ) ENGINE=InnoDB");
            }
            // Tem de existir sempre pelo menos um administrador ativo: se não houver, a conta mais antiga passa a sê-lo
            if (!$pdo->query("SELECT 1 FROM utilizadores WHERE papel = 'admin' AND ativo = 1")->fetchColumn()) {
                $pdo->exec("UPDATE utilizadores SET papel = 'admin', ativo = 1 ORDER BY id LIMIT 1");
            }
        },
        6 => function (PDO $pdo): void {
            // Conta de exemplo "utilizador normal" (ana@athlytic.pt / demo1234) para testar a administração
            // em bases de dados que já existiam antes (nas novas já vem no athlytic.sql)
            $existe = $pdo->prepare('SELECT 1 FROM utilizadores WHERE email = ?');
            $existe->execute(['ana@athlytic.pt']);
            if ($existe->fetchColumn()) {
                return;
            }
            $plano = fn (int $id) => $pdo->query("SELECT id FROM planos WHERE id = $id")->fetchColumn() ?: null;
            $pdo->prepare(
                "INSERT INTO utilizadores (nome, email, password_hash, papel, data_nascimento, sexo, altura_cm, peso_atual, massa_gorda,
                                           objetivo, intensidade_padrao, plano_corrida_id, plano_musculacao_id, plano_alimentacao_id)
                 VALUES ('Ana Costa', 'ana@athlytic.pt', ?, 'utilizador', '1999-07-11', 'F', 165, 61.0, 23.0, 'manutencao', 'moderada', ?, ?, ?)"
            )->execute([password_hash('demo1234', PASSWORD_DEFAULT), $plano(1), $plano(3), $plano(5)]);
            $id = (int) $pdo->lastInsertId();
            $pdo->prepare(
                "INSERT INTO atividades (utilizador_id, tipo, intensidade, data, hora, duracao_seg, distancia_km, notas) VALUES
                 (?, 'natacao',  'moderada', CURDATE() - INTERVAL 1 DAY, '19:00', 2520, 1.60, NULL),
                 (?, 'corrida',  'leve',     CURDATE() - INTERVAL 3 DAY, '08:00', 1950, 5.10, 'Corrida no parque.'),
                 (?, 'ciclismo', 'alta',     CURDATE() - INTERVAL 5 DAY, '10:30', 5400, 38.00, NULL)"
            )->execute([$id, $id, $id]);
            $pdo->prepare('INSERT INTO pesagens (utilizador_id, data, peso, massa_gorda) VALUES (?, CURDATE(), 61.0, 23.0)')->execute([$id]);
        },
    ];
}

function existe_tabela(PDO $pdo, string $tabela): bool
{
    return (bool) $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($tabela))->fetchColumn();
}

function existe_coluna(PDO $pdo, string $tabela, string $coluna): bool
{
    return (bool) $pdo->query("SHOW COLUMNS FROM `$tabela` LIKE " . $pdo->quote($coluna))->fetchColumn();
}

/** Executa um ficheiro .sql com várias instruções. */
function executar_ficheiro_sql(PDO $pdo, string $ficheiro): void
{
    $sql = str_replace("\r\n", "\n", file_get_contents($ficheiro));
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);  // tira comentários
    foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $instrucao) {
        $pdo->exec($instrucao);
    }
}

/**
 * Garante que a base de dados existe e está na versão atual.
 * Devolve a lista do que foi feito (vazia se já estava tudo em dia).
 */
function preparar_base_dados(PDO $pdo): array
{
    $feito = [];

    $existe = $pdo->query('SELECT 1 FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ' . $pdo->quote(DB_NAME))->fetchColumn();
    if ($existe) {
        $pdo->exec('USE `' . DB_NAME . '`');
    }
    if (!$existe || !existe_tabela($pdo, 'utilizadores')) {
        executar_ficheiro_sql($pdo, __DIR__ . '/../database/athlytic.sql');
        $pdo->exec('USE `' . DB_NAME . '`');
        return ['Base de dados criada com os planos, um utilizador de teste e dados de exemplo.'];
    }

    // Bases de dados criadas antes de existir a tabela de versão contam como versão 0
    if (!existe_tabela($pdo, 'versao_bd')) {
        $pdo->exec('CREATE TABLE versao_bd (versao INT NOT NULL) ENGINE=InnoDB');
        $pdo->exec('INSERT INTO versao_bd (versao) VALUES (0)');
    }
    $versao = (int) $pdo->query('SELECT versao FROM versao_bd')->fetchColumn();

    foreach (migracoes() as $numero => $migracao) {
        if ($numero > $versao) {
            $migracao($pdo);
            $pdo->prepare('UPDATE versao_bd SET versao = ?')->execute([$numero]);
            $feito[] = "Atualização $numero aplicada.";
        }
    }
    return $feito;
}
