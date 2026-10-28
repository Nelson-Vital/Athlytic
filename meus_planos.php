<?php
/**
 * Página antiga: os planos pessoais passaram para dentro do Plano de Treino e do Plano Alimentar
 * (botão "Adicionar" em cada dia). Fica só a redirecionar.
 */
require_once 'includes/funcoes.php';
requer_login();
redirecionar('plano_treino.php');
