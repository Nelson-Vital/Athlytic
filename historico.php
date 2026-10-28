<?php
/**
 * Página antiga: o histórico passou para a página Atividades.
 * Redireciona mantendo o período e o filtro (semanas, tipo, p).
 */
require_once 'includes/funcoes.php';
requer_login();
$q = array_intersect_key($_GET, array_flip(['semanas', 'tipo', 'p']));
redirecionar('atividades.php' . ($q ? '?' . http_build_query($q) : ''));
