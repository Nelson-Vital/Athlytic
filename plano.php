<?php
/**
 * Página antiga: os planos passaram para Plano de Treino (com separadores) e Plano Alimentar.
 * Fica só a redirecionar, para que ligações e favoritos antigos continuem a funcionar.
 */
require_once 'includes/funcoes.php';
requer_login();
$tipo = $_GET['tipo'] ?? 'corrida';
redirecionar($tipo === 'alimentacao' ? 'plano_alimentar.php' : 'plano_treino.php?modalidade=' . ($tipo === 'musculacao' ? 'musculacao' : 'corrida'));
