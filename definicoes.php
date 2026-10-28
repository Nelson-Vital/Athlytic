<?php
require_once 'includes/funcoes.php';
requer_login();
$u = utilizador_atual();

$erros = [];

/** Converte um campo numérico opcional ("" -> null). Aceita vírgula decimal. */
function num_opcional(string $campo, float $min, float $max, string $nome, array &$erros): ?float
{
    $v = str_replace(',', '.', trim($_POST[$campo] ?? ''));
    if ($v === '') {
        return null;
    }
    if (!is_numeric($v) || $v < $min || $v > $max) {
        $erros[] = "$nome tem de estar entre $min e $max.";
        return null;
    }
    return (float) $v;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $acao = $_POST['acao'] ?? '';

    switch ($acao) {
        // -------------------------------------------------------
        case 'guardar':
            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $nascimento = trim($_POST['data_nascimento'] ?? '');

            if (mb_strlen($nome) < 2) {
                $erros[] = 'Indica o teu nome.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erros[] = 'O email não é válido.';
            } else {
                $stmt = db()->prepare('SELECT 1 FROM utilizadores WHERE email = ? AND id <> ?');
                $stmt->execute([$email, $u['id']]);
                if ($stmt->fetch()) {
                    $erros[] = 'Esse email já está a ser usado por outra conta.';
                }
            }
            if ($nascimento !== '' && (!($dt = DateTime::createFromFormat('Y-m-d', $nascimento)) || $nascimento > date('Y-m-d'))) {
                $erros[] = 'A data de nascimento não é válida.';
            }

            $altura       = num_opcional('altura_cm', 100, 250, 'A altura', $erros);
            $peso         = num_opcional('peso_atual', 30, 300, 'O peso atual', $erros);
            $gordura      = num_opcional('massa_gorda', 3, 60, 'A % de massa gorda', $erros);
            $pesoObj      = num_opcional('peso_objetivo', 30, 300, 'O peso objetivo', $erros);
            $gorduraObj   = num_opcional('massa_gorda_objetivo', 3, 60, 'A % de massa gorda objetivo', $erros);
            $kmSemana     = num_opcional('meta_km_semana', 0, 500, 'A meta de km', $erros);
            $treinos      = (int) ($_POST['meta_treinos_semana'] ?? 4);
            $objetivo     = $_POST['objetivo'] ?? '';
            $sexo         = $_POST['sexo'] ?? '';
            if ($sexo !== '' && !isset(SEXOS[$sexo])) {
                $erros[] = 'Escolhe o sexo.';
            }
            $intensidade  = $_POST['intensidade_padrao'] ?? '';

            if ($treinos < 1 || $treinos > 14) {
                $erros[] = 'A meta de treinos por semana não é válida.';
            }
            if (!isset(OBJETIVOS[$objetivo])) {
                $erros[] = 'Escolhe um objetivo.';
            }
            if (!isset(INTENSIDADES[$intensidade])) {
                $erros[] = 'Escolhe uma intensidade.';
            }

            if (!$erros) {
                $stmt = db()->prepare(
                    'UPDATE utilizadores SET nome = ?, email = ?, data_nascimento = ?, sexo = ?, altura_cm = ?, peso_atual = ?, massa_gorda = ?,
                        peso_objetivo = ?, massa_gorda_objetivo = ?, meta_km_semana = ?, meta_treinos_semana = ?,
                        objetivo = ?, intensidade_padrao = ?, lembrete_treino = ?, alerta_meta = ?, notif_planos = ?
                     WHERE id = ?'
                );
                $stmt->execute([
                    $nome, $email, $nascimento ?: null, $sexo ?: null, $altura, $peso, $gordura, $pesoObj, $gorduraObj,
                    $kmSemana ?? 20, $treinos, $objetivo, $intensidade,
                    isset($_POST['lembrete_treino']) ? 1 : 0,
                    isset($_POST['alerta_meta']) ? 1 : 0,
                    isset($_POST['notif_planos']) ? 1 : 0,
                    $u['id'],
                ]);
                // Se o peso mudou, fica registado no histórico de pesagens de hoje
                if ($peso !== null && ($u['peso_atual'] === null || (float) $u['peso_atual'] !== $peso || ($u['massa_gorda'] === null ? null : (float) $u['massa_gorda']) !== $gordura)) {
                    registar_pesagem($u['id'], date('Y-m-d'), $peso, $gordura);
                }
                flash('success', 'Todas as configurações guardadas com sucesso! ✅');
                redirecionar('definicoes.php');
            }
            break;

        // -------------------------------------------------------
        case 'tema':
            $tema = ($_POST['tema'] ?? '') === 'escuro' ? 'escuro' : 'claro';
            db()->prepare('UPDATE utilizadores SET tema = ? WHERE id = ?')->execute([$tema, $u['id']]);
            guardar_tema_cookie($tema);
            redirecionar('definicoes.php');

        // -------------------------------------------------------
        case 'password':
            $atual = $_POST['password_atual'] ?? '';
            $nova = $_POST['password_nova'] ?? '';
            if (!password_verify($atual, $u['password_hash'])) {
                flash('danger', 'A palavra-passe atual está errada.');
            } elseif (strlen($nova) < 8) {
                flash('danger', 'A nova palavra-passe tem de ter pelo menos 8 caracteres.');
            } elseif ($nova !== ($_POST['password_confirmar'] ?? '')) {
                flash('danger', 'As novas palavras-passe não coincidem.');
            } else {
                db()->prepare('UPDATE utilizadores SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($nova, PASSWORD_DEFAULT), $u['id']]);
                flash('success', 'Palavra-passe alterada.');
            }
            redirecionar('definicoes.php');

        // -------------------------------------------------------
        case 'limpar':
            // 2.º passo da confirmação, verificado também no servidor (o JavaScript pode ser contornado)
            if (($_POST['confirmacao'] ?? '') !== 'APAGAR') {
                flash('warning', 'As atividades não foram apagadas: escreve APAGAR para confirmar.');
                redirecionar('definicoes.php');
            }
            $stmt = db()->prepare('SELECT f.caminho FROM atividade_fotos f JOIN atividades a ON a.id = f.atividade_id WHERE a.utilizador_id = ?
                                   UNION SELECT ficheiro_fit FROM atividades WHERE utilizador_id = ? AND ficheiro_fit IS NOT NULL');
            $stmt->execute([$u['id'], $u['id']]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $f) {
                @unlink(__DIR__ . '/' . $f);
            }
            db()->prepare('DELETE FROM atividades WHERE utilizador_id = ?')->execute([$u['id']]);
            db()->prepare('DELETE FROM plano_concluidos WHERE utilizador_id = ?')->execute([$u['id']]);
            flash('success', 'Todas as atividades foram apagadas.');
            redirecionar('definicoes.php');

        // -------------------------------------------------------
        case 'apagar_conta':
            // Regra: o último administrador ativo não pode apagar a própria conta
            if (deixa_sem_admins($u)) {
                flash('danger', 'És o único administrador ativo: promove outro utilizador a administrador antes de apagares a tua conta.');
                redirecionar('definicoes.php');
            }
            if (!password_verify($_POST['password_apagar'] ?? '', $u['password_hash'])) {
                flash('danger', 'Palavra-passe errada. A conta não foi apagada.');
                redirecionar('definicoes.php');
            }
            $stmt = db()->prepare('SELECT f.caminho FROM atividade_fotos f JOIN atividades a ON a.id = f.atividade_id WHERE a.utilizador_id = ?
                                   UNION SELECT ficheiro_fit FROM atividades WHERE utilizador_id = ? AND ficheiro_fit IS NOT NULL');
            $stmt->execute([$u['id'], $u['id']]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $f) {
                @unlink(__DIR__ . '/' . $f);
            }
            // As atividades, fotos e marcações são apagadas em cascata (ON DELETE CASCADE)
            db()->prepare('DELETE FROM utilizadores WHERE id = ?')->execute([$u['id']]);
            $_SESSION = [];
            session_destroy();
            session_start();
            flash('info', 'A tua conta foi apagada.');
            redirecionar('index.php');
    }
}

// Valores a mostrar: os enviados (se houve erro) ou os da base de dados
$v = $erros ? array_merge($u, $_POST) : $u;
$v['lembrete_treino'] = $erros ? isset($_POST['lembrete_treino']) : $u['lembrete_treino'];
$v['alerta_meta']     = $erros ? isset($_POST['alerta_meta']) : $u['alerta_meta'];
$v['notif_planos']    = $erros ? isset($_POST['notif_planos']) : $u['notif_planos'];

$stmt = db()->prepare('SELECT COUNT(*) FROM atividades WHERE utilizador_id = ?');
$stmt->execute([$u['id']]);
$totalAtividades = (int) $stmt->fetchColumn();
$ultimoAdmin = deixa_sem_admins($u);

$titulo = 'Configurações';
$pagina = 'definicoes';
require 'includes/header.php';
topo_pagina('Configurações', 'Os teus dados, objetivos e preferências.');

/** Campo numérico com a unidade dentro, à direita. */
function campo_unidade(string $id, string $rotulo, $valor, string $unidade, string $extra = '', string $ajuda = ''): void
{
    echo '<label class="form-label" for="' . e($id) . '">' . $rotulo . '</label>'
        . '<div class="campo-unidade"><input type="number" class="form-control" id="' . e($id) . '" name="' . e($id) . '" value="' . e($valor) . '" ' . $extra . '>'
        . '<span class="unidade">' . e($unidade) . '</span></div>'
        . ($ajuda !== '' ? '<p class="campo-ajuda">' . e($ajuda) . '</p>' : '');
}
?>
        <?php if ($erros): ?>
            <div class="alert alert-danger" role="alert">
                <?php foreach ($erros as $erro): ?><div><?= e($erro) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-xl-8">
                <form method="post" id="formConfig" class="d-flex flex-column gap-3">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="acao" value="guardar">

                    <section class="cartao" aria-labelledby="secDados">
                        <h2 class="cartao-titulo mb-3" id="secDados">Dados pessoais</h2>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label" for="nome">Nome completo</label><input type="text" class="form-control" id="nome" name="nome" value="<?= e($v['nome']) ?>" autocomplete="name" required></div>
                            <div class="col-md-6"><label class="form-label" for="email">Email</label><input type="email" class="form-control" id="email" name="email" value="<?= e($v['email']) ?>" autocomplete="email" required></div>
                            <div class="col-md-4"><label class="form-label" for="data_nascimento">Data de nascimento</label><input type="date" class="form-control" id="data_nascimento" name="data_nascimento" value="<?= e($v['data_nascimento']) ?>" max="<?= date('Y-m-d') ?>"></div>
                            <div class="col-md-4"><label class="form-label" for="sexo">Sexo</label>
                                <select class="form-select" id="sexo" name="sexo">
                                    <option value="" <?= empty($v['sexo']) ? 'selected' : '' ?>>Por indicar</option>
                                    <?php foreach (SEXOS as $valor => $nomeSexo): ?>
                                        <option value="<?= e($valor) ?>" <?= ($v['sexo'] ?? '') === $valor ? 'selected' : '' ?>><?= e($nomeSexo) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4"><?php campo_unidade('altura_cm', 'Altura', $v['altura_cm'], 'cm'); ?></div>
                        </div>
                    </section>

                    <section class="cartao" aria-labelledby="secCorpo">
                        <h2 class="cartao-titulo mb-3" id="secCorpo">Corpo e objetivos</h2>
                        <div class="row g-3">
                            <div class="col-md-6"><?php campo_unidade('peso_atual', 'Peso atual', $v['peso_atual'], 'kg', 'step="0.1"', 'Cada alteração fica no histórico de peso'); ?></div>
                            <div class="col-md-6"><?php campo_unidade('peso_objetivo', 'Peso objetivo', $v['peso_objetivo'], 'kg', 'step="0.1"'); ?></div>
                            <div class="col-md-6"><?php campo_unidade('massa_gorda', 'Massa gorda <span class="text-muted fw-normal">(opcional)</span>', $v['massa_gorda'], '%', 'step="0.1"'); ?></div>
                            <div class="col-md-6"><?php campo_unidade('massa_gorda_objetivo', 'Massa gorda objetivo', $v['massa_gorda_objetivo'], '%', 'step="0.1"'); ?></div>
                        </div>
                    </section>

                    <section class="cartao" aria-labelledby="secTreino">
                        <h2 class="cartao-titulo mb-3" id="secTreino">Preferências de treino</h2>
                        <div class="row g-3">
                            <div class="col-md-6"><?php campo_unidade('meta_km_semana', 'Meta de corrida por semana', $v['meta_km_semana'], 'km', 'step="0.1"'); ?></div>
                            <div class="col-md-6"><label class="form-label" for="meta_treinos_semana">Treinos por semana</label>
                                <select class="form-select" id="meta_treinos_semana" name="meta_treinos_semana">
                                    <?php for ($i = 1; $i <= 7; $i++): ?>
                                        <option <?= $v['meta_treinos_semana'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-12"><label class="form-label" for="objetivo">Objetivo principal</label>
                                <select class="form-select" id="objetivo" name="objetivo">
                                    <?php foreach (OBJETIVOS as $valor => $nome): ?>
                                        <option value="<?= e($valor) ?>" <?= $v['objetivo'] === $valor ? 'selected' : '' ?>><?= e($nome) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <fieldset class="col-12">
                                <legend class="form-label">Intensidade padrão</legend>
                                <div class="segmentado">
                                    <?php foreach (INTENSIDADES as $valor => $nome): ?>
                                        <input type="radio" class="btn-check" name="intensidade_padrao" id="int_<?= e($valor) ?>" value="<?= e($valor) ?>" <?= $v['intensidade_padrao'] === $valor ? 'checked' : '' ?>>
                                        <label for="int_<?= e($valor) ?>"><?= e($nome) ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        </div>
                    </section>

                    <section class="cartao" aria-labelledby="secNotif">
                        <h2 class="cartao-titulo mb-2" id="secNotif">Notificações e lembretes</h2>
                        <?php foreach ([
                            'lembrete_treino' => 'Lembretes diários de treino',
                            'alerta_meta'     => 'Alertas quando as metas semanais não são atingidas',
                            'notif_planos'    => 'Novos planos personalizados',
                        ] as $campo => $texto): ?>
                            <div class="interruptor-linha">
                                <label for="<?= e($campo) ?>"><?= e($texto) ?></label>
                                <input type="checkbox" class="interruptor" role="switch" id="<?= e($campo) ?>" name="<?= e($campo) ?>" <?= $v[$campo] ? 'checked aria-checked="true"' : 'aria-checked="false"' ?>>
                            </div>
                        <?php endforeach; ?>
                    </section>

                    <!-- Só aparece quando há alterações por guardar (sem JavaScript fica sempre visível) -->
                    <div class="barra-alteracoes" id="barraAlteracoes" role="region" aria-label="Alterações por guardar">
                        <span>Tens alterações por guardar</span>
                        <div class="d-flex gap-2">
                            <button type="reset" class="btn btn-secundario">Descartar</button>
                            <button type="submit" class="btn btn-principal">Guardar alterações</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Coluna lateral -->
            <div class="col-xl-4 d-flex flex-column gap-3">
                <section class="cartao" aria-labelledby="secTema">
                    <h2 class="cartao-titulo mb-3" id="secTema">Tema</h2>
                    <form method="post" class="tema-opcoes form-tema-opcoes">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="acao" value="tema">
                        <?php foreach (['claro' => 'Claro', 'escuro' => 'Escuro'] as $valor => $nome): $ativo = $u['tema'] === $valor; ?>
                            <button type="submit" name="tema" value="<?= $valor ?>" data-tema="<?= $valor ?>" class="tema-opcao<?= $ativo ? ' ativo' : '' ?>" aria-pressed="<?= $ativo ? 'true' : 'false' ?>">
                                <span class="amostra <?= $valor === 'claro' ? 'clara' : 'escura' ?>" aria-hidden="true"></span><?= $nome ?>
                            </button>
                        <?php endforeach; ?>
                    </form>
                </section>

                <section class="cartao" aria-labelledby="secConta">
                    <h2 class="cartao-titulo mb-3" id="secConta">Conta e dados</h2>
                    <div class="d-flex flex-column gap-2">
                        <button type="button" class="btn btn-opcao" data-bs-toggle="modal" data-bs-target="#modalPassword"><i class="bi bi-lock" aria-hidden="true"></i> Alterar palavra-passe</button>
                        <a href="exportar.php" class="btn btn-opcao"><i class="bi bi-download" aria-hidden="true"></i> Exportar os meus dados (CSV)</a>
                        <?php if (e_admin($u)): ?>
                            <a href="admin.php" class="btn btn-opcao"><i class="bi bi-shield" aria-hidden="true"></i> Administração</a>
                        <?php endif; ?>
                        <a href="logout.php" class="btn btn-opcao"><i class="bi bi-box-arrow-left" aria-hidden="true"></i> Terminar sessão</a>
                    </div>
                </section>

                <section class="cartao zona-perigo" aria-labelledby="secPerigo">
                    <h2 class="cartao-titulo mb-1" id="secPerigo">Zona de perigo</h2>
                    <p class="small mb-3">Estas ações não podem ser desfeitas.</p>
                    <div class="d-flex flex-column gap-2">
                        <button type="button" class="btn btn-perigo" data-bs-toggle="modal" data-bs-target="#modalLimpar"><i class="bi bi-trash3" aria-hidden="true"></i> Apagar todas as atividades</button>
                        <button type="button" class="btn btn-perigo-final" data-bs-toggle="modal" data-bs-target="#modalApagar"><i class="bi bi-person-x" aria-hidden="true"></i> Apagar conta permanentemente</button>
                    </div>
                </section>
            </div>
        </div>

    <!-- Modal - Alterar palavra-passe -->
    <div class="modal fade" id="modalPassword" tabindex="-1" aria-labelledby="modalPasswordLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="password">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPasswordLabel">Alterar palavra-passe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="password_atual">Palavra-passe atual</label>
                    <input type="password" name="password_atual" id="password_atual" class="form-control mb-3" autocomplete="current-password" required>
                    <label class="form-label" for="password_nova">Nova palavra-passe <span class="text-muted fw-normal">(mín. 8 caracteres)</span></label>
                    <input type="password" name="password_nova" id="password_nova" class="form-control mb-3" minlength="8" autocomplete="new-password" required>
                    <label class="form-label" for="password_confirmar">Confirmar nova palavra-passe</label>
                    <input type="password" name="password_confirmar" id="password_confirmar" class="form-control" autocomplete="new-password" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secundario" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-principal">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal - Apagar todas as atividades (confirmação em 2 passos) -->
    <div class="modal fade" id="modalLimpar" tabindex="-1" aria-labelledby="modalLimparLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" class="modal-content" id="formLimpar">
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="limpar">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLimparLabel">Apagar todas as atividades</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <!-- Passo 1 -->
                <div class="modal-body" data-passo="1">
                    <p class="small text-muted mb-2">Passo 1 de 2</p>
                    <p>Vais apagar <strong><?= $totalAtividades ?> <?= $totalAtividades === 1 ? 'atividade' : 'atividades' ?></strong>, com as fotos e ficheiros .FIT, e os treinos marcados como concluídos nos planos.</p>
                    <p class="mb-0">O teu perfil, os planos e o histórico de peso <strong>não</strong> são apagados.</p>
                </div>
                <!-- Passo 2 -->
                <div class="modal-body d-none" data-passo="2">
                    <p class="small text-muted mb-2">Passo 2 de 2</p>
                    <p><strong>Esta ação não pode ser desfeita.</strong> Para confirmar, escreve <code>APAGAR</code> na caixa abaixo.</p>
                    <input type="text" name="confirmacao" id="confirmacaoLimpar" class="form-control" placeholder="APAGAR" autocomplete="off" autocapitalize="characters">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secundario" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-perigo" data-passo="1" id="btnLimparContinuar" <?= $totalAtividades === 0 ? 'disabled' : '' ?>>Continuar</button>
                    <button type="submit" class="btn btn-perigo-final d-none" data-passo="2" id="btnLimparConfirmar" disabled>Apagar tudo</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        // Passa do passo 1 para o 2 e só ativa o botão final quando se escreve APAGAR
        (function () {
            const modal = document.getElementById('modalLimpar');
            const campo = document.getElementById('confirmacaoLimpar');
            const confirmar = document.getElementById('btnLimparConfirmar');
            function mostrarPasso(n) {
                modal.querySelectorAll('[data-passo]').forEach(el => el.classList.toggle('d-none', el.dataset.passo !== String(n)));
            }
            document.getElementById('btnLimparContinuar').addEventListener('click', function () {
                mostrarPasso(2);
                campo.focus();
            });
            campo.addEventListener('input', function () {
                confirmar.disabled = campo.value.trim().toUpperCase() !== 'APAGAR';
                if (!confirmar.disabled) campo.value = 'APAGAR';
            });
            modal.addEventListener('hidden.bs.modal', function () {
                mostrarPasso(1);
                campo.value = '';
                confirmar.disabled = true;
            });
        })();
    </script>

    <!-- Modal - Apagar conta -->
    <div class="modal fade" id="modalApagar" tabindex="-1" aria-labelledby="modalApagarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="apagar_conta">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalApagarLabel">Apagar conta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <?php if ($ultimoAdmin): ?>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-shield-exclamation me-1"></i> És o único administrador ativo, por isso não podes apagar a tua conta.
                            Promove outro utilizador a administrador na página <a href="admin.php">Administração</a> e depois tenta outra vez.
                        </div>
                    <?php else: ?>
                        <p><strong>ATENÇÃO: Esta ação é irreversível!</strong> Todos os teus dados e atividades serão apagados.</p>
                        <label class="form-label" for="password_apagar">Confirma com a tua palavra-passe</label>
                        <input type="password" name="password_apagar" id="password_apagar" class="form-control" autocomplete="current-password" required>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secundario" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-perigo-final" <?= $ultimoAdmin ? 'disabled' : '' ?>>Apagar conta</button>
                </div>
            </form>
        </div>
    </div>
<?php
$scripts = '<script src="assets/js/definicoes.js"></script>';
require 'includes/footer.php';
