<?php
require_once 'includes/funcoes.php';

$_SESSION = [];
session_destroy();
redirecionar('index.php');
