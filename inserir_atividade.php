<?php
/**
 * Página antiga: o registo de atividades passou para a página Atividades (painel "Registar atividade").
 * Fica só a redirecionar, para que ligações e favoritos antigos continuem a funcionar.
 */
require_once 'includes/funcoes.php';
requer_login();
redirecionar('atividades.php?registar=1');
