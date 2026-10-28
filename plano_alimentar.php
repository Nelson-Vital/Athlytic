<?php
/**
 * Plano Alimentar: plano ativo, refeições de hoje, a semana e as refeições pessoais.
 */
require_once 'includes/funcoes.php';
requer_login();

$cfgPlano = [
    'url'        => 'plano_alimentar.php',
    'categoria'  => 'alimentacao',
    'modalidade' => null,
    'tipo'       => 'alimentacao',
    'coluna'     => 'plano_alimentacao_id',
    'titulo'     => 'Plano alimentar',
    'pagina'     => 'plano_alimentar',
];
require 'includes/pagina_plano.php';
