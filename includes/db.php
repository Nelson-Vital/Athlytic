<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/migracoes.php';

/**
 * Devolve a ligação PDO (criada uma única vez por pedido).
 * Na primeira ligação de cada pedido confirma que a base de dados está criada e atualizada.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Erro de ligação à base de dados. Confirma que o MySQL está ligado no XAMPP.');
        }

        try {
            preparar_base_dados($pdo);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Não foi possível criar ou atualizar a base de dados: ' . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}
