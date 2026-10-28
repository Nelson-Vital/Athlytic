<?php
/**
 * Página de um plano (usada por plano_treino.php e plano_alimentar.php):
 * plano ativo e "Mudar plano", cartão de hoje, grelha da semana e planos pessoais.
 *
 * Antes de incluir, definir $cfgPlano:
 *   'url'        => ligação da página (ex.: 'plano_treino.php?modalidade=corrida')
 *   'categoria'  => 'treino' | 'alimentacao' (categoria dos planos pessoais)
 *   'modalidade' => modalidade dos treinos (null no Plano Alimentar)
 *   'tipo'       => tipo em `planos` (corrida, musculacao, alimentacao) ou null se não há planos
 *   'coluna'     => coluna do utilizador com o plano escolhido (ou null)
 *   'titulo', 'pagina', 'separadores' (HTML dos separadores, opcional)
 *
 * Planos pessoais: só de hoje até DIAS_PLANEAR dias à frente; apagados DIAS_GUARDAR dias depois do seu dia.
 */
$u = utilizador_atual();
limpar_planos_antigos();

$alimentar = $cfgPlano['categoria'] === 'alimentacao';
$urlCom = fn(string $q) => $cfgPlano['url'] . (strpos($cfgPlano['url'], '?') === false ? '?' : '&') . $q;
$hoje = date('Y-m-d');
$diasPermitidos = [];
for ($i = 0; $i <= DIAS_PLANEAR; $i++) {
    $diasPermitidos[] = date('Y-m-d', strtotime("+$i days"));
}

/** Plano pessoal do utilizador com este id (ou null). */
function plano_pessoal(int $id, int $utilizadorId): ?array
{
    $stmt = db()->prepare('SELECT * FROM planos_pessoais WHERE id = ? AND utilizador_id = ?');
    $stmt->execute([$id, $utilizadorId]);
    return $stmt->fetch() ?: null;
}

$erros = [];
$vazio = ['id' => 0, 'data' => $hoje, 'titulo' => '', 'descricao' => '', 'substitui_plano' => 0, 'refeicao' => 'dia'];
$pp = null;   // plano pessoal a mostrar no painel (com erros, ou ?editar= / ?adicionar=)

// ---------------- Ações ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $acao = $_POST['acao'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    // Trocar de plano
    if ($acao === 'mudar_plano' && $cfgPlano['tipo']) {
        $stmt = db()->prepare('SELECT id FROM planos WHERE id = ? AND tipo = ?');
        $stmt->execute([$_POST['plano_id'] ?? 0, $cfgPlano['tipo']]);
        if ($novo = $stmt->fetchColumn()) {
            // O nome da coluna vem da configuração da página, nunca do utilizador
            db()->prepare("UPDATE utilizadores SET {$cfgPlano['coluna']} = ? WHERE id = ?")->execute([$novo, $u['id']]);
            flash('success', 'Plano atualizado.');
        }
        redirecionar($cfgPlano['url']);
    }

    // Apagar ou marcar como feito um plano pessoal
    if ($acao === 'apagar' || $acao === 'concluir') {
        $p = plano_pessoal($id, $u['id']);
        if ($p && $acao === 'apagar') {
            db()->prepare('DELETE FROM planos_pessoais WHERE id = ?')->execute([$id]);
            flash('success', 'Plano pessoal apagado.');
        } elseif ($p) {
            db()->prepare('UPDATE planos_pessoais SET concluido = 1 - concluido WHERE id = ?')->execute([$id]);
        }
        redirecionar($cfgPlano['url'] . '#dia-' . ($p['data'] ?? ''));
    }

    // Guardar (novo ou editado)
    $pp = [
        'id'              => $id,
        'data'            => $_POST['data'] ?? '',
        'titulo'          => trim($_POST['titulo'] ?? ''),
        'descricao'       => trim($_POST['descricao'] ?? ''),
        'substitui_plano' => isset($_POST['substitui_plano']) ? 1 : 0,
        'refeicao'        => $alimentar ? ($_POST['refeicao'] ?? '') : null,
    ];
    // A regra dos 7 dias é verificada aqui, no servidor, e não só no formulário
    if (!in_array($pp['data'], $diasPermitidos, true)) {
        $erros[] = 'Só podes planear de hoje até ' . DIAS_PLANEAR . ' dias à frente.';
    }
    if ($alimentar && !isset(REFEICOES[$pp['refeicao']])) {
        $erros[] = 'Escolhe a refeição.';
    }
    if (mb_strlen($pp['titulo']) < 2 || mb_strlen($pp['titulo']) > 100) {
        $erros[] = 'O título tem de ter entre 2 e 100 caracteres.';
    }
    if (mb_strlen($pp['descricao']) > 2000) {
        $erros[] = 'Os detalhes são demasiado longos (máx. 2000 caracteres).';
    }
    if ($id && !plano_pessoal($id, $u['id'])) {
        $erros[] = 'Plano não encontrado.';
    }

    if (!$erros) {
        // O tipo e a modalidade vêm da página (e do separador), não do formulário
        $valores = [$pp['data'], $pp['titulo'], $pp['descricao'] ?: null, $pp['substitui_plano'], $pp['refeicao']];
        if ($id) {
            db()->prepare('UPDATE planos_pessoais SET data = ?, titulo = ?, descricao = ?, substitui_plano = ?, refeicao = ? WHERE id = ? AND utilizador_id = ?')
                ->execute(array_merge($valores, [$id, $u['id']]));
            flash('success', 'Plano pessoal atualizado.');
        } else {
            db()->prepare('INSERT INTO planos_pessoais (data, titulo, descricao, substitui_plano, refeicao, categoria, modalidade, utilizador_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute(array_merge($valores, [$cfgPlano['categoria'], $cfgPlano['modalidade'], $u['id']]));
            flash('success', 'Plano pessoal adicionado para ' . mb_strtolower(nome_dia($pp['data'])) . '.');
        }
        redirecionar($cfgPlano['url'] . '#dia-' . $pp['data']);
    }
} elseif (isset($_GET['editar'])) {
    $pp = plano_pessoal((int) $_GET['editar'], $u['id']);
    if (!$pp || $pp['data'] < $hoje) {
        flash('warning', 'Só podes editar planos de hoje em diante.');
        redirecionar($cfgPlano['url']);
    }
} elseif (isset($_GET['adicionar']) && in_array($_GET['adicionar'], $diasPermitidos, true)) {
    $pp = ['data' => $_GET['adicionar']] + $vazio;
}

// ---------------- Plano ativo ----------------
$planos = [];
$plano = null;
$diasPlano = [];   // dia da semana (1-7) => dia do plano
$concluidas = [];  // datas desta semana marcadas como concluídas
if ($cfgPlano['tipo']) {
    $stmt = db()->prepare('SELECT * FROM planos WHERE tipo = ? ORDER BY id');
    $stmt->execute([$cfgPlano['tipo']]);
    $planos = $stmt->fetchAll();
    foreach ($planos as $p) {
        if ((int) $p['id'] === (int) $u[$cfgPlano['coluna']]) {
            $plano = $p;
        }
    }
}
[$inicioSemana] = semana_atual();
$ultimoDia = max(date('Y-m-d', strtotime('sunday this week')), end($diasPermitidos));
if ($plano) {
    $stmt = db()->prepare('SELECT * FROM plano_dias WHERE plano_id = ? ORDER BY dia_semana');
    $stmt->execute([$plano['id']]);
    foreach ($stmt as $dia) {
        $diasPlano[(int) $dia['dia_semana']] = $dia;
    }
    $stmt = db()->prepare('SELECT data FROM plano_concluidos WHERE utilizador_id = ? AND plano_id = ? AND data BETWEEN ? AND ?');
    $stmt->execute([$u['id'], $plano['id'], $inicioSemana, date('Y-m-d', strtotime('sunday this week'))]);
    $concluidas = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
$diasComTreino = count(array_filter($diasPlano, fn($d) => !$d['descanso']));

// ---------------- Planos pessoais da semana ----------------
$sqlModalidade = $alimentar ? '' : ' AND modalidade = ?';
$stmt = db()->prepare("SELECT * FROM planos_pessoais WHERE utilizador_id = ? AND categoria = ?$sqlModalidade AND data BETWEEN ? AND ? ORDER BY data, id");
$stmt->execute(array_merge([$u['id'], $cfgPlano['categoria']], $alimentar ? [] : [$cfgPlano['modalidade']], [$inicioSemana, $ultimoDia]));
$pessoaisPorDia = [];
foreach ($stmt as $p) {
    $pessoaisPorDia[$p['data']][] = $p;
}

/**
 * A linha do plano fica riscada? (um plano pessoal desse dia substitui-a)
 * Nos treinos substitui o treino todo; nas refeições só a refeição escolhida (ou o dia inteiro).
 */
function linha_substituida(string $linha, array $pessoais, bool $alimentar): bool
{
    foreach ($pessoais as $p) {
        if (!$p['substitui_plano']) {
            continue;
        }
        if (!$alimentar || $p['refeicao'] === 'dia') {
            return true;
        }
        $nome = mb_strtolower(partir_linha($linha, 'refeicao')[0]);
        $alvo = ['pequeno_almoco' => 'pequeno-almoço', 'almoco' => 'almoço', 'lanche' => 'lanche', 'jantar' => 'jantar'][$p['refeicao']] ?? '';
        if ($alvo !== '' && ($nome === $alvo || ($alvo === 'lanche' && strpos($nome, 'lanche') === 0))) {
            return true;
        }
    }
    return false;
}

/** Lista das linhas de um dia do plano (refeições com o nome à esquerda), com as substituídas riscadas. */
function lista_dia(string $conteudo, array $pessoais, bool $alimentar): string
{
    $html = '<ul class="itens-dia">';
    foreach (linhas_plano($conteudo) as $linha) {
        $riscada = linha_substituida($linha, $pessoais, $alimentar);
        $html .= '<li' . ($riscada ? ' class="riscado"' : '') . '>';
        [$nome, $resto] = partir_linha($linha, 'refeicao');
        if ($alimentar && $resto !== '') {
            $html .= '<span class="refeicao-nome">' . e($nome) . '</span> ' . e($resto);
        } else {
            $html .= e($linha);
        }
        $html .= $riscada ? ' <span class="visually-hidden">(substituído)</span></li>' : '</li>';
    }
    return $html . '</ul>';
}

// ---------------- Hoje ----------------
$diaHoje = $diasPlano[(int) date('N')] ?? null;
$pessoaisHoje = $pessoaisPorDia[$hoje] ?? [];
$treinoHoje = $alimentar ? null : treino_de_hoje($u, $cfgPlano['modalidade']);
$feitoHoje = in_array($hoje, $concluidas, true);

/** Texto do aviso "O plano sugere" para um dia. */
function sugestao_plano(?array $dia, bool $alimentar): string
{
    if (!$dia) {
        return '';
    }
    return $dia['descanso'] ? 'Descanso' : ($alimentar ? implode(', ', array_map(fn($l) => partir_linha($l, 'refeicao')[0], linhas_plano($dia['conteudo']))) : $dia['titulo']);
}

$titulo = $cfgPlano['titulo'];
$pagina = $cfgPlano['pagina'];
require __DIR__ . '/header.php';

topo_pagina($cfgPlano['titulo'], 'Hoje é ' . mb_strtolower(data_extenso()), $cfgPlano['separadores'] ?? '');
?>
        <div class="row g-3 mb-4">
            <!-- Plano ativo -->
            <div class="col-xl-6">
                <section class="cartao h-100" aria-labelledby="tituloPlano">
                    <?php if ($plano): ?>
                        <p class="rotulo mb-1">Plano ativo<?= $alimentar ? '' : ' · ' . e(TIPOS_ATIVIDADE[$cfgPlano['modalidade']][0]) ?></p>
                        <h2 class="titulo-plano" id="tituloPlano"><?= e($plano['nome']) ?></h2>
                        <p class="text-muted mb-2"><?= e($plano['descricao']) ?></p>
                        <p class="d-flex flex-wrap gap-2 mb-3">
                            <span class="etiqueta etiqueta-cinza">Nível <?= e(mb_strtolower($plano['nivel'])) ?></span>
                            <?php if (!$alimentar): ?>
                                <span class="etiqueta etiqueta-cinza"><?= count($concluidas) ?> de <?= $diasComTreino ?> concluídos esta semana</span>
                            <?php endif; ?>
                        </p>
                    <?php elseif ($planos): ?>
                        <p class="rotulo mb-1">Plano</p>
                        <h2 class="titulo-plano" id="tituloPlano">Ainda não escolheste um plano</h2>
                        <p class="text-muted mb-3">Escolhe um plano para começares.</p>
                    <?php else: ?>
                        <p class="rotulo mb-1">Plano · <?= e(TIPOS_ATIVIDADE[$cfgPlano['modalidade']][0]) ?></p>
                        <h2 class="titulo-plano" id="tituloPlano">Os teus treinos</h2>
                        <p class="text-muted mb-0">Ainda não há planos de <?= e(mb_strtolower(TIPOS_ATIVIDADE[$cfgPlano['modalidade']][0])) ?>. Usa "Adicionar" num dia da semana para planeares os teus treinos.</p>
                    <?php endif; ?>
                    <?php if ($planos): ?>
                        <form method="post" action="<?= e($cfgPlano['url']) ?>" class="d-flex gap-2">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="acao" value="mudar_plano">
                            <select name="plano_id" class="form-select" aria-label="Escolher plano">
                                <?php foreach ($planos as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>" <?= $plano && $p['id'] == $plano['id'] ? 'selected' : '' ?>><?= e($p['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-secundario text-nowrap">Mudar plano</button>
                        </form>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Hoje -->
            <div class="col-xl-6">
                <section class="cartao cartao-escuro h-100" aria-labelledby="tituloHoje">
                    <p class="rotulo mb-2">Hoje · <?= e(mb_strtolower(DIAS_SEMANA[(int) date('N')])) ?></p>
                    <?php if ($alimentar): ?>
                        <h2 class="visually-hidden" id="tituloHoje">Refeições de hoje</h2>
                        <?php if ($diaHoje || $pessoaisHoje): ?>
                            <ul class="refeicoes-hoje mb-3">
                                <?php foreach (linhas_plano($diaHoje['conteudo'] ?? '') as $linha): [$nome, $resto] = partir_linha($linha, 'refeicao'); ?>
                                    <li class="<?= linha_substituida($linha, $pessoaisHoje, true) ? 'riscado' : '' ?>"><span><?= e($resto !== '' ? $nome : 'Refeição') ?></span><strong><?= e($resto !== '' ? $resto : $linha) ?></strong></li>
                                <?php endforeach; ?>
                                <?php foreach ($pessoaisHoje as $p): ?>
                                    <li><span><?= e(REFEICOES[$p['refeicao']] ?? 'Pessoal') ?> · pessoal</span><strong><?= e($p['titulo']) ?></strong></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">Sem refeições planeadas para hoje.</p>
                        <?php endif; ?>
                        <?php if ($plano): ?>
                            <form method="post" action="concluir.php" class="d-flex flex-wrap align-items-center gap-2">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="tipo" value="alimentacao">
                                <input type="hidden" name="voltar" value="plano_alimentar.php">
                                <?php if ($feitoHoje): ?>
                                    <span class="etiqueta etiqueta-feito"><i class="bi bi-check-lg" aria-hidden="true"></i> Refeições de hoje feitas</span>
                                    <button type="submit" name="desmarcar" value="1" class="btn btn-secundario btn-sm">Desmarcar</button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-principal"><i class="bi bi-check-lg" aria-hidden="true"></i> Marcar refeições de hoje como feitas</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    <?php elseif ($treinoHoje): ?>
                        <h2 class="titulo-hoje" id="tituloHoje"><?= e($treinoHoje['titulo']) ?></h2>
                        <?php if ($treinoHoje['conteudo'] !== ''): ?>
                            <p class="sub mb-3"><?= e(implode(' · ', linhas_plano($treinoHoje['conteudo']))) ?></p>
                        <?php endif; ?>
                        <?php if ($treinoHoje['feito']): ?>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="etiqueta etiqueta-feito"><i class="bi bi-check-lg" aria-hidden="true"></i> Treino de hoje feito</span>
                                <form method="post" action="<?= isset($treinoHoje['plano_id']) ? 'concluir.php' : e($cfgPlano['url']) ?>" class="m-0">
                                    <?= csrf_campo() ?>
                                    <?php if (isset($treinoHoje['plano_id'])): ?>
                                        <input type="hidden" name="tipo" value="<?= e($cfgPlano['modalidade']) ?>">
                                        <input type="hidden" name="voltar" value="plano_treino.php">
                                        <button type="submit" name="desmarcar" value="1" class="btn btn-secundario btn-sm"
                                                onclick="return confirm('Desmarcar o treino de hoje? A atividade registada com ele também é apagada.');">Desmarcar</button>
                                    <?php else: ?>
                                        <input type="hidden" name="acao" value="concluir">
                                        <input type="hidden" name="id" value="<?= (int) $treinoHoje['pessoal_id'] ?>">
                                        <button type="submit" class="btn btn-secundario btn-sm">Desmarcar</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        <?php else: ?>
                            <a href="atividades.php?registar=hoje&amp;modalidade=<?= e($cfgPlano['modalidade']) ?>" class="btn btn-principal">
                                <i class="bi bi-check-lg" aria-hidden="true"></i> Registar este treino
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <h2 class="titulo-hoje" id="tituloHoje"><?= $diaHoje && $diaHoje['descanso'] ? 'Descanso' : 'Sem treino para hoje' ?></h2>
                        <p class="sub mb-0"><?= $diaHoje ? e(implode(' · ', linhas_plano($diaHoje['conteudo']))) : 'Podes planear um treino teu com "Adicionar".' ?></p>
                    <?php endif; ?>
                </section>
            </div>
        </div>

        <div class="cartao-cabecalho">
            <h2 class="titulo-seccao">Esta semana</h2>
            <span class="text-muted small">Podes personalizar os próximos <?= DIAS_PLANEAR ?> dias</span>
        </div>

        <div class="grelha-dias">
            <?php for ($t = strtotime($inicioSemana); date('Y-m-d', $t) <= $ultimoDia; $t = strtotime('+1 day', $t)):
                $data = date('Y-m-d', $t);
                $diaPlano = $diasPlano[(int) date('N', $t)] ?? null;
                $pessoais = $pessoaisPorDia[$data] ?? [];
                $passado = $data < $hoje;
                $podeAdicionar = in_array($data, $diasPermitidos, true);
                $nomeDia = DIAS_SEMANA[(int) date('N', $t)];
                $nomeCurto = explode('-', $nomeDia)[0] . ', ' . date('j', $t) . ' ' . mb_strtolower(MESES_CURTOS[(int) date('n', $t)]);
                $classes = ($passado ? ' passado' : '') . ($data === $hoje ? ' hoje' : '') . ($diaPlano && $diaPlano['descanso'] ? ' descanso' : ''); ?>
                <article class="cartao-dia<?= $classes ?>" id="dia-<?= e($data) ?>" aria-label="<?= e($nomeDia . ', ' . data_curta($data)) ?>">
                    <div class="dia-nome">
                        <span><?= e($nomeCurto) ?></span>
                        <?php if ($data === $hoje): ?><span class="etiqueta etiqueta-hoje">Hoje</span>
                        <?php elseif (in_array($data, $concluidas, true)): ?><span class="etiqueta etiqueta-feito"><i class="bi bi-check-lg" aria-hidden="true"></i> Feito</span><?php endif; ?>
                    </div>
                    <?php if ($diaPlano): ?>
                        <?php $riscado = !$alimentar && linha_substituida('', $pessoais, false); ?>
                        <div class="<?= $riscado ? 'riscado' : '' ?>">
                            <?php if (!$alimentar): ?><div class="dia-titulo"><?= e($diaPlano['titulo']) ?></div><?php endif; ?>
                            <?= lista_dia($diaPlano['conteudo'], $pessoais, $alimentar) ?>
                        </div>
                    <?php elseif (!$pessoais): ?>
                        <p class="text-muted small mb-0"><?= $plano || !$cfgPlano['tipo'] ? 'Nada planeado.' : 'Escolhe um plano.' ?></p>
                    <?php endif; ?>

                    <?php foreach ($pessoais as $p): ?>
                        <div class="pessoal<?= $p['concluido'] ? ' concluido' : '' ?>">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <span class="etiqueta etiqueta-pessoal">Pessoal<?= $alimentar && isset(REFEICOES[$p['refeicao']]) ? ' · ' . e(REFEICOES[$p['refeicao']]) : '' ?></span>
                                <div class="pessoal-acoes">
                                    <form method="post" action="<?= e($cfgPlano['url']) ?>" class="m-0">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="acao" value="concluir">
                                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                        <button type="submit" class="btn btn-icone" aria-label="<?= $p['concluido'] ? 'Marcar como por fazer' : 'Marcar como feito' ?>: <?= e($p['titulo']) ?>">
                                            <i class="bi <?= $p['concluido'] ? 'bi-check-circle-fill' : 'bi-circle' ?>" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    <?php if (!$passado): ?>
                                        <a href="<?= e($urlCom('editar=' . (int) $p['id'])) ?>" class="btn btn-icone" data-editar-pessoal
                                           data-plano="<?= e(json_encode(['id' => (int) $p['id'], 'data' => $p['data'], 'titulo' => $p['titulo'], 'descricao' => (string) $p['descricao'], 'substitui_plano' => (int) $p['substitui_plano'], 'refeicao' => $p['refeicao']])) ?>"
                                           data-dia="<?= e($nomeCurto) ?>" data-sugestao="<?= e(sugestao_plano($diaPlano, $alimentar)) ?>"
                                           aria-label="Editar: <?= e($p['titulo']) ?>"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    <?php endif; ?>
                                    <form method="post" action="<?= e($cfgPlano['url']) ?>" class="m-0" onsubmit="return confirm('Apagar este plano pessoal?');">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="acao" value="apagar">
                                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                        <button type="submit" class="btn btn-icone perigo" aria-label="Apagar: <?= e($p['titulo']) ?>"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </div>
                            <div class="titulo-pessoal"><?= e($p['titulo']) ?></div>
                            <?php if ($p['substitui_plano']): ?>
                                <div class="small text-muted">Substitui <?= $alimentar ? (($p['refeicao'] ?? 'dia') === 'dia' ? 'as refeições do plano' : 'o ' . e(mb_strtolower(REFEICOES[$p['refeicao']])) . ' do plano') : 'o treino do plano' ?></div>
                            <?php endif; ?>
                            <?php if ($p['descricao']): ?>
                                <ul class="itens-dia mt-1"><?php foreach (linhas_plano($p['descricao']) as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($podeAdicionar): ?>
                        <a href="<?= e($urlCom('adicionar=' . $data)) ?>" class="btn-adicionar w-100 justify-content-center mt-auto" data-adicionar-pessoal
                           data-data="<?= e($data) ?>" data-dia="<?= e($nomeCurto) ?>" data-sugestao="<?= e(sugestao_plano($diaPlano, $alimentar)) ?>">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar
                        </a>
                    <?php endif; ?>
                </article>
            <?php endfor; ?>
        </div>

        <!-- Personalizar um dia: painel lateral (computador) ou folha que sobe (telemóvel) -->
        <?php $pp = $pp ?? $vazio; $diaPainel = $pp['data'] ?: $hoje; ?>
        <div class="painel-fundo" aria-hidden="true"></div>
        <section class="painel<?= isset($_GET['editar']) || isset($_GET['adicionar']) || $erros ? ' aberto' : '' ?>" id="painelPessoal" role="dialog" aria-labelledby="tituloPessoal"
                 <?= isset($_GET['editar']) || isset($_GET['adicionar']) || $erros ? '' : 'aria-hidden="true"' ?>>
            <div class="painel-topo">
                <div>
                    <p class="rotulo rotulo-azul mb-0" id="pessoalModo"><?= $pp['id'] ? 'Editar' : 'Personalizar' ?></p>
                    <h2 id="tituloPessoal"><?= e(explode('-', DIAS_SEMANA[(int) date('N', strtotime($diaPainel))])[0] . ', ' . date('j', strtotime($diaPainel)) . ' ' . mb_strtolower(MESES_CURTOS[(int) date('n', strtotime($diaPainel))])) ?></h2>
                </div>
                <button type="button" class="btn btn-icone" data-fechar-painel aria-label="Fechar"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
            <form method="post" action="<?= e($cfgPlano['url']) ?>" class="painel-form">
                <div class="painel-corpo d-flex flex-column gap-3">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="acao" value="guardar">
                    <input type="hidden" name="id" id="pessoalId" value="<?= (int) $pp['id'] ?>">
                    <input type="hidden" name="data" id="pessoalData" value="<?= e($diaPainel) ?>">
                    <?php if ($erros): ?>
                        <div class="alert alert-danger mb-0" role="alert"><?php foreach ($erros as $erro): ?><div><?= e($erro) ?></div><?php endforeach; ?></div>
                    <?php endif; ?>
                    <?php $sug = sugestao_plano($diasPlano[(int) date('N', strtotime($diaPainel))] ?? null, $alimentar); ?>
                    <p class="aviso mb-0" id="pessoalSugestao" <?= $sug === '' ? 'hidden' : '' ?>><span>O plano sugere: <strong><?= e($sug) ?></strong></span></p>

                    <?php if ($alimentar): ?>
                        <div>
                            <label for="pessoalRefeicao" class="form-label">Refeição</label>
                            <select class="form-select" id="pessoalRefeicao" name="refeicao">
                                <?php foreach (REFEICOES as $valor => $nome): ?>
                                    <option value="<?= e($valor) ?>" <?= ($pp['refeicao'] ?? 'dia') === $valor ? 'selected' : '' ?>><?= e($nome) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div>
                        <label for="pessoalTitulo" class="form-label">Título</label>
                        <input type="text" class="form-control" id="pessoalTitulo" name="titulo" value="<?= e($pp['titulo']) ?>" maxlength="100" required data-foco-inicial
                               placeholder="<?= $alimentar ? 'ex: Jantar fora' : 'ex: Rodagem leve 8 km' ?>">
                    </div>
                    <div>
                        <label for="pessoalDescricao" class="form-label">Detalhes <span class="text-muted fw-normal">(um item por linha)</span></label>
                        <textarea class="form-control" id="pessoalDescricao" name="descricao" rows="4" maxlength="2000"
                                  placeholder="<?= $alimentar ? "Arroz de pato&#10;Salada verde" : "Aquecimento: 10 min&#10;Séries: 6×400 m" ?>"><?= e($pp['descricao']) ?></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="pessoalSubstitui" name="substitui_plano" value="1" <?= $pp['substitui_plano'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pessoalSubstitui"><?= $alimentar ? 'Substituir esta refeição do plano' : 'Substituir o treino do plano' ?></label>
                    </div>
                </div>
                <div class="painel-rodape">
                    <button type="button" class="btn btn-secundario" data-fechar-painel>Cancelar</button>
                    <button type="submit" class="btn btn-principal">Guardar</button>
                </div>
            </form>
        </section>
<?php
$scripts = '<script src="assets/js/planos_pessoais.js"></script>';
require __DIR__ . '/footer.php';
