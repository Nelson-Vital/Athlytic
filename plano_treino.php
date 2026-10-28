<?php
/**
 * Plano de Treino: separadores Corrida · Musculação · Ciclismo · Natação.
 * Corrida e Musculação usam os planos que já existem; Ciclismo e Natação começam só com planos pessoais.
 * (Substitui "Plano de Corrida", "Musculação" e "Os Meus Planos".)
 */
require_once 'includes/funcoes.php';
requer_login();

$modalidade = $_GET['modalidade'] ?? 'corrida';
if (!isset(TIPOS_ATIVIDADE[$modalidade])) {
    $modalidade = 'corrida';
}

ob_start(); ?>
<nav class="segmentado segmentado-compacto separadores-plano" aria-label="Modalidade">
    <?php foreach (['corrida', 'musculacao', 'ciclismo', 'natacao'] as $valor): $nome = TIPOS_ATIVIDADE[$valor][0]; ?>
        <a href="plano_treino.php?modalidade=<?= e($valor) ?>" class="<?= $valor === $modalidade ? 'ativo' : '' ?>"<?= $valor === $modalidade ? ' aria-current="page"' : '' ?>><?= e($nome) ?></a>
    <?php endforeach; ?>
</nav>
<?php
$cfgPlano = [
    'url'         => 'plano_treino.php?modalidade=' . $modalidade,
    'categoria'   => 'treino',
    'modalidade'  => $modalidade,
    'tipo'        => isset(PLANOS_TREINO[$modalidade]) ? $modalidade : null,
    'coluna'      => PLANOS_TREINO[$modalidade] ?? null,
    'titulo'      => 'Plano de treino',
    'pagina'      => 'plano_treino',
    'separadores' => ob_get_clean(),
];
require 'includes/pagina_plano.php';
