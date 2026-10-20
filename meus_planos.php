<?php
/**
 * Os Meus Planos: planos de treino e de alimentação criados pelo utilizador para um dia concreto.
 * Regras: só se planeia de hoje até DIAS_PLANEAR dias à frente; cada plano é apagado
 * automaticamente DIAS_GUARDAR dias depois do seu dia (limpar_planos_antigos()).
 */
require_once 'includes/funcoes.php';
requer_login();
$u = utilizador_atual();

limpar_planos_antigos();

$hoje = date('Y-m-d');
$ultimoDia = date('Y-m-d', strtotime("+" . DIAS_PLANEAR . " days"));
$diasPermitidos = [];
for ($i = 0; $i <= DIAS_PLANEAR; $i++) {
    $diasPermitidos[] = date('Y-m-d', strtotime("+$i days"));
}

/** Plano do utilizador com este id (ou null). */
function plano_pessoal(int $id, int $utilizadorId): ?array
{
    $stmt = db()->prepare('SELECT * FROM planos_pessoais WHERE id = ? AND utilizador_id = ?');
    $stmt->execute([$id, $utilizadorId]);
    return $stmt->fetch() ?: null;
}

$erros = [];
$vazio = ['id' => 0, 'categoria' => 'treino', 'modalidade' => '', 'data' => $hoje, 'titulo' => '', 'descricao' => ''];
$d = $vazio;

// Abrir um plano para editar (só planos de hoje em diante)
if (isset($_GET['editar'])) {
    $p = plano_pessoal((int) $_GET['editar'], $u['id']);
    if ($p && $p['data'] >= $hoje) {
        $d = $p;
    } else {
        flash('warning', 'Só podes editar planos de hoje em diante.');
        redirecionar('meus_planos.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $acao = $_POST['acao'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($acao === 'apagar' || $acao === 'concluir') {
        $p = plano_pessoal($id, $u['id']);
        if ($p && $acao === 'apagar') {
            db()->prepare('DELETE FROM planos_pessoais WHERE id = ?')->execute([$id]);
            flash('success', 'Plano apagado.');
        } elseif ($p) {
            db()->prepare('UPDATE planos_pessoais SET concluido = 1 - concluido WHERE id = ?')->execute([$id]);
        }
        redirecionar('meus_planos.php#dia-' . ($p['data'] ?? ''));
    }

    // Guardar (novo ou editado)
    $d = [
        'id'         => $id,
        'categoria'  => $_POST['categoria'] ?? '',
        'modalidade' => $_POST['modalidade'] ?? '',
        'data'       => $_POST['data'] ?? '',
        'titulo'     => trim($_POST['titulo'] ?? ''),
        'descricao'  => trim($_POST['descricao'] ?? ''),
    ];
    if (!isset(CATEGORIAS_PLANO[$d['categoria']])) {
        $erros[] = 'Escolhe se é um plano de treino ou de alimentação.';
    }
    if ($d['categoria'] === 'treino' && !isset(TIPOS_ATIVIDADE[$d['modalidade']])) {
        $erros[] = 'Escolhe a modalidade do treino.';
    }
    if ($d['categoria'] !== 'treino') {
        $d['modalidade'] = '';
    }
    // A regra dos 7 dias é verificada aqui, no servidor, e não só no formulário
    if (!in_array($d['data'], $diasPermitidos, true)) {
        $erros[] = 'Só podes planear de hoje até ' . DIAS_PLANEAR . ' dias à frente.';
    }
    if (mb_strlen($d['titulo']) < 2 || mb_strlen($d['titulo']) > 100) {
        $erros[] = 'O título tem de ter entre 2 e 100 caracteres.';
    }
    if (mb_strlen($d['descricao']) > 2000) {
        $erros[] = 'A descrição é demasiado longa (máx. 2000 caracteres).';
    }
    if ($id && !plano_pessoal($id, $u['id'])) {
        $erros[] = 'Plano não encontrado.';
    }

    if (!$erros) {
        $valores = [$d['categoria'], $d['modalidade'] ?: null, $d['data'], $d['titulo'], $d['descricao'] ?: null];
        if ($id) {
            db()->prepare('UPDATE planos_pessoais SET categoria = ?, modalidade = ?, data = ?, titulo = ?, descricao = ? WHERE id = ? AND utilizador_id = ?')
                ->execute(array_merge($valores, [$id, $u['id']]));
            flash('success', 'Plano atualizado.');
        } else {
            db()->prepare('INSERT INTO planos_pessoais (categoria, modalidade, data, titulo, descricao, utilizador_id) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute(array_merge($valores, [$u['id']]));
            flash('success', 'Plano adicionado para ' . mb_strtolower(nome_dia($d['data'])) . '.');
        }
        redirecionar('meus_planos.php#dia-' . $d['data']);
    }
}

// Planos agrupados por dia: próximos (hoje em diante) e da última semana
$stmt = db()->prepare('SELECT * FROM planos_pessoais WHERE utilizador_id = ? ORDER BY data, categoria DESC, id');
$stmt->execute([$u['id']]);
$proximos = $passados = [];
foreach ($stmt as $p) {
    if ($p['data'] >= $hoje) {
        $proximos[$p['data']][] = $p;
    } else {
        $passados[$p['data']][] = $p;
    }
}
krsort($passados);

/** Cartão de um plano. */
function cartao_plano(array $p, bool $passado): void
{
    [$nomeCat, $iconeCat] = CATEGORIAS_PLANO[$p['categoria']];
    [$nomeMod, $iconeMod, $corMod] = $p['modalidade'] ? tipo_info($p['modalidade']) : [$nomeCat, $iconeCat, 'text-success'];
    ?>
    <div class="plano-pessoal <?= $p['concluido'] ? 'concluido' : '' ?> <?= $passado ? 'passado' : '' ?>">
        <form method="post" class="m-0">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="concluir">
            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <button type="submit" class="btn-check-plano" title="<?= $p['concluido'] ? 'Marcar como por fazer' : 'Marcar como feito' ?>" aria-label="<?= $p['concluido'] ? 'Marcar como por fazer' : 'Marcar como feito' ?>">
                <i class="bi <?= $p['concluido'] ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i>
            </button>
        </form>
        <div class="flex-grow-1 min-w-0">
            <div class="small text-muted"><i class="bi <?= e($iconeMod) ?> <?= e($corMod) ?>"></i> <?= e($nomeMod) ?></div>
            <div class="fw-bold titulo"><?= e($p['titulo']) ?></div>
            <?php if ($p['descricao']): ?>
                <div class="small mt-1"><?= lista_conteudo($p['descricao']) ?></div>
            <?php endif; ?>
        </div>
        <div class="d-flex flex-shrink-0">
            <?php if (!$passado): ?>
                <a href="meus_planos.php?editar=<?= (int) $p['id'] ?>#formPlano" class="btn btn-sm btn-outline-primary border-0" title="Editar" aria-label="Editar"><i class="bi bi-pencil"></i></a>
            <?php endif; ?>
            <form method="post" class="m-0" onsubmit="return confirm('Apagar este plano?');">
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="apagar">
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Apagar" aria-label="Apagar"><i class="bi bi-trash"></i></button>
            </form>
        </div>
    </div>
    <?php
}

$titulo = 'Os Meus Planos';
$pagina = 'meus_planos';
require 'includes/header.php';
?>
        <h1 class="mb-1"><i class="bi bi-calendar2-week text-primary"></i> Os Meus Planos</h1>
        <p class="text-muted mb-4">Planeia os teus treinos e refeições até <?= DIAS_PLANEAR ?> dias à frente. Os planos são apagados automaticamente <?= DIAS_GUARDAR ?> dias depois do seu dia.</p>

        <div class="row g-4">
            <!-- Formulário: novo plano / editar -->
            <div class="col-lg-5">
                <div class="card card-custom" id="formPlano">
                    <div class="card-body">
                        <h4 class="mb-3"><?= $d['id'] ? '<i class="bi bi-pencil"></i> Editar plano' : '<i class="bi bi-plus-circle"></i> Novo plano' ?></h4>

                        <?php if ($erros): ?>
                            <div class="alert alert-danger py-2">
                                <?php foreach ($erros as $erro): ?><div><?= e($erro) ?></div><?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="acao" value="guardar">
                            <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">

                            <label class="form-label fw-bold">Tipo de plano</label>
                            <div class="escolha-grupo mb-3">
                                <?php foreach (CATEGORIAS_PLANO as $valor => [$nome, $icone]): ?>
                                    <input type="radio" class="btn-check" name="categoria" id="cat_<?= e($valor) ?>" value="<?= e($valor) ?>" <?= $d['categoria'] === $valor ? 'checked' : '' ?>>
                                    <label class="escolha" for="cat_<?= e($valor) ?>"><i class="bi <?= e($icone) ?>"></i> <?= e($nome) ?></label>
                                <?php endforeach; ?>
                            </div>

                            <div class="mb-3" id="campoModalidade">
                                <label for="modalidade" class="form-label fw-bold">Modalidade</label>
                                <select class="form-select" id="modalidade" name="modalidade">
                                    <option value="">Escolhe...</option>
                                    <?php foreach (TIPOS_ATIVIDADE as $valor => [$nome]): ?>
                                        <option value="<?= e($valor) ?>" <?= $d['modalidade'] === $valor ? 'selected' : '' ?>><?= e($nome) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <label class="form-label fw-bold">Dia</label>
                            <div class="dias-grupo mb-3">
                                <?php foreach ($diasPermitidos as $dia): $t = strtotime($dia); ?>
                                    <input type="radio" class="btn-check" name="data" id="dia_<?= e($dia) ?>" value="<?= e($dia) ?>" <?= $d['data'] === $dia ? 'checked' : '' ?>>
                                    <label class="dia" for="dia_<?= e($dia) ?>">
                                        <small><?= $dia === $hoje ? 'Hoje' : e(mb_substr(DIAS_SEMANA[(int) date('N', $t)], 0, 3)) ?></small>
                                        <strong><?= date('j', $t) ?></strong>
                                        <small><?= e(MESES_CURTOS[(int) date('n', $t)]) ?></small>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <div class="mb-3">
                                <label for="titulo" class="form-label fw-bold">Título</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" value="<?= e($d['titulo']) ?>" maxlength="100" placeholder="ex: Rodagem leve 8 km / Dia de hidratos" required>
                            </div>
                            <div class="mb-3">
                                <label for="descricao" class="form-label fw-bold">Detalhes <small class="text-muted">(opcional, uma linha por item)</small></label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="5" maxlength="2000" placeholder="Aquecimento: 10 min&#10;Séries: 6x400 m&#10;Pequeno-almoço: aveia com banana"><?= e($d['descricao'] ?? '') ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-check2"></i> <?= $d['id'] ? 'Guardar alterações' : 'Adicionar plano' ?></button>
                                <?php if ($d['id']): ?><a href="meus_planos.php" class="btn btn-outline-secondary">Cancelar</a><?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista de planos por dia -->
            <div class="col-lg-7">
                <div class="card card-custom mb-4">
                    <div class="card-body">
                        <h4 class="mb-3"><i class="bi bi-calendar-check"></i> Próximos dias</h4>
                        <?php if (!$proximos): ?>
                            <p class="text-muted mb-0">Ainda não tens planos para os próximos dias.</p>
                        <?php endif; ?>
                        <?php foreach ($proximos as $dia => $planos): ?>
                            <div class="dia-planos" id="dia-<?= e($dia) ?>">
                                <h6 class="dia-titulo <?= $dia === $hoje ? 'hoje' : '' ?>"><?= e(nome_dia($dia)) ?></h6>
                                <?php foreach ($planos as $p) cartao_plano($p, false); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ($passados): ?>
                    <div class="card card-custom mb-4">
                        <div class="card-body">
                            <h4 class="mb-1"><i class="bi bi-clock-history"></i> Última semana</h4>
                            <p class="small text-muted mb-3">Estes planos desaparecem sozinhos <?= DIAS_GUARDAR ?> dias depois do seu dia.</p>
                            <?php foreach ($passados as $dia => $planos):
                                $restam = DIAS_GUARDAR - (int) round((strtotime($hoje) - strtotime($dia)) / 86400); ?>
                                <div class="dia-planos" id="dia-<?= e($dia) ?>">
                                    <h6 class="dia-titulo"><?= e(nome_dia($dia)) ?> <small class="text-muted fw-normal">· apagado <?= $restam <= 1 ? 'amanhã' : 'daqui a ' . $restam . ' dias' ?></small></h6>
                                    <?php foreach ($planos as $p) cartao_plano($p, true); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
            // A modalidade só faz sentido nos planos de treino
            (function () {
                const campo = document.getElementById('campoModalidade');
                const atualizar = () => campo.classList.toggle('d-none', !document.getElementById('cat_treino').checked);
                document.querySelectorAll('input[name="categoria"]').forEach(r => r.addEventListener('change', atualizar));
                atualizar();
            })();
        </script>
<?php require 'includes/footer.php'; ?>
