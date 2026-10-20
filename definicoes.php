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
?>
        <h1 class="mb-5"><i class="bi bi-sliders2"></i> Configurações</h1>

        <?php if ($erros): ?>
            <div class="alert alert-danger">
                <?php foreach ($erros as $erro): ?><div><?= e($erro) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" id="formConfig">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="guardar">

            <!-- 1. Dados Pessoais -->
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <h3 class="section-title"><i class="bi bi-person-circle me-2"></i> Dados Pessoais</h3>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="nome">Nome Completo</label><input type="text" class="form-control" id="nome" name="nome" value="<?= e($v['nome']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label" for="email">Email</label><input type="email" class="form-control" id="email" name="email" value="<?= e($v['email']) ?>" required></div>
                        <div class="col-md-4"><label class="form-label" for="data_nascimento">Data de Nascimento</label><input type="date" class="form-control" id="data_nascimento" name="data_nascimento" value="<?= e($v['data_nascimento']) ?>" max="<?= date('Y-m-d') ?>"></div>
                        <div class="col-md-4"><label class="form-label" for="sexo">Sexo</label>
                            <select class="form-select" id="sexo" name="sexo">
                                <option value="" <?= empty($v['sexo']) ? 'selected' : '' ?>>Por indicar</option>
                                <?php foreach (SEXOS as $valor => $nomeSexo): ?>
                                    <option value="<?= e($valor) ?>" <?= ($v['sexo'] ?? '') === $valor ? 'selected' : '' ?>><?= e($nomeSexo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label" for="peso_atual">Peso Atual (kg)</label><input type="number" step="0.1" class="form-control" id="peso_atual" name="peso_atual" value="<?= e($v['peso_atual']) ?>"><small class="text-muted">Ao mudar, fica no histórico de peso</small></div>
                        <div class="col-md-4"><label class="form-label" for="altura_cm">Altura (cm)</label><input type="number" class="form-control" id="altura_cm" name="altura_cm" value="<?= e($v['altura_cm']) ?>"></div>
                        <div class="col-md-4"><label class="form-label" for="massa_gorda">% Massa Gorda <small class="text-muted">(opcional)</small></label><input type="number" step="0.1" class="form-control" id="massa_gorda" name="massa_gorda" value="<?= e($v['massa_gorda']) ?>"></div>
                        <div class="col-md-4"><label class="form-label" for="peso_objetivo">Peso Objetivo (kg)</label><input type="number" step="0.1" class="form-control" id="peso_objetivo" name="peso_objetivo" value="<?= e($v['peso_objetivo']) ?>"></div>
                        <div class="col-md-4"><label class="form-label" for="massa_gorda_objetivo">% Massa Gorda Objetivo</label><input type="number" step="0.1" class="form-control" id="massa_gorda_objetivo" name="massa_gorda_objetivo" value="<?= e($v['massa_gorda_objetivo']) ?>"></div>
                    </div>
                </div>
            </div>

            <!-- 2. Preferências de Treino -->
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <h3 class="section-title"><i class="bi bi-bullseye me-2"></i> Preferências de Treino</h3>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="meta_km_semana">Meta KMs Corridos / Semana</label><input type="number" step="0.1" class="form-control" id="meta_km_semana" name="meta_km_semana" value="<?= e($v['meta_km_semana']) ?>"></div>
                        <div class="col-md-6"><label class="form-label" for="meta_treinos_semana">Treinos / Semana (meta)</label>
                            <select class="form-select" id="meta_treinos_semana" name="meta_treinos_semana">
                                <?php for ($i = 1; $i <= 7; $i++): ?>
                                    <option <?= $v['meta_treinos_semana'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label" for="objetivo">Objetivo Principal</label>
                            <select class="form-select" id="objetivo" name="objetivo">
                                <?php foreach (OBJETIVOS as $valor => $nome): ?>
                                    <option value="<?= e($valor) ?>" <?= $v['objetivo'] === $valor ? 'selected' : '' ?>><?= e($nome) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label" for="intensidade_padrao">Intensidade Padrão</label>
                            <select class="form-select" id="intensidade_padrao" name="intensidade_padrao">
                                <?php foreach (INTENSIDADES as $valor => $nome): ?>
                                    <option value="<?= e($valor) ?>" <?= $v['intensidade_padrao'] === $valor ? 'selected' : '' ?>><?= e($nome) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Notificações -->
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <h3 class="section-title"><i class="bi bi-bell me-2"></i> Notificações e Lembretes</h3>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="lembrete_treino" name="lembrete_treino" <?= $v['lembrete_treino'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="lembrete_treino">Lembretes diários de treino</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="alerta_meta" name="alerta_meta" <?= $v['alerta_meta'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="alerta_meta">Alertas quando metas semanais não são atingidas</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notif_planos" name="notif_planos" <?= $v['notif_planos'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notif_planos">Notificações de novos planos personalizados</label>
                    </div>
                </div>
            </div>

            <div class="text-center mb-5">
                <button type="submit" class="btn btn-gold btn-lg px-5">
                    <i class="bi bi-check2-all"></i> Guardar Todas as Alterações
                </button>
            </div>
        </form>

        <!-- 4. Integração com Dispositivos -->
        <div class="card card-custom mb-4">
            <div class="card-body">
                <h3 class="section-title"><i class="bi bi-watch me-2"></i> Integração com Dispositivos</h3>
                <p class="mb-2">Para importar treinos do Garmin ou do Strava, exporta a atividade como ficheiro <strong>.FIT</strong> e envia-a em
                    <a href="inserir_atividade.php">Inserir Atividade</a> (tipo Corrida).</p>
                <small class="text-muted">A ligação automática às contas Garmin Connect e Strava está prevista como trabalho futuro.</small>
            </div>
        </div>

        <!-- 5. Tema e Aparência -->
        <div class="card card-custom mb-4">
            <div class="card-body">
                <h3 class="section-title"><i class="bi bi-moon-stars me-2"></i> Tema e Aparência</h3>
                <form method="post" class="d-flex gap-3">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="acao" value="tema">
                    <button type="submit" name="tema" value="claro" class="btn <?= $u['tema'] === 'claro' ? 'btn-secondary' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-sun"></i> Modo Claro
                    </button>
                    <button type="submit" name="tema" value="escuro" class="btn <?= $u['tema'] === 'escuro' ? 'btn-secondary' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-moon"></i> Modo Escuro
                    </button>
                </form>
            </div>
        </div>

        <!-- 6. Segurança e Dados -->
        <div class="card card-custom mb-5">
            <div class="card-body">
                <h3 class="section-title"><i class="bi bi-shield-lock me-2"></i> Segurança e Dados</h3>
                <div class="row">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-outline-primary w-100 mb-3" data-bs-toggle="modal" data-bs-target="#modalPassword">Alterar Palavra-passe</button>
                        <a href="exportar.php" class="btn btn-outline-warning w-100 mb-3">Exportar Todos os Dados (CSV)</a>
                        <button type="button" class="btn btn-outline-danger w-100 mb-3" data-bs-toggle="modal" data-bs-target="#modalLimpar">
                            <i class="bi bi-trash3 me-1"></i> Apagar Todas as Atividades
                        </button>
                    </div>
                    <div class="col-md-6">
                        <a href="logout.php" class="btn btn-danger w-100 mb-3">Terminar Sessão (Logout)</a>
                        <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#modalApagar">Apagar Conta Permanentemente</button>
                    </div>
                </div>
            </div>
        </div>

    <!-- Modal - Alterar palavra-passe -->
    <div class="modal fade" id="modalPassword" tabindex="-1" aria-labelledby="modalPasswordLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="password">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPasswordLabel">Alterar Palavra-passe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <input type="password" name="password_atual" class="form-control mb-3" placeholder="Palavra-passe atual" required>
                    <input type="password" name="password_nova" class="form-control mb-3" placeholder="Nova palavra-passe (mín. 8 caracteres)" minlength="8" required>
                    <input type="password" name="password_confirmar" class="form-control" placeholder="Confirmar nova palavra-passe" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar</button>
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
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalLimparLabel">Apagar Todas as Atividades</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" data-passo="1" id="btnLimparContinuar" <?= $totalAtividades === 0 ? 'disabled' : '' ?>>Continuar</button>
                    <button type="submit" class="btn btn-danger d-none" data-passo="2" id="btnLimparConfirmar" disabled>Apagar tudo</button>
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
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalApagarLabel">Apagar Conta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <?php if ($ultimoAdmin): ?>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-shield-exclamation me-1"></i> És o único administrador ativo, por isso não podes apagar a tua conta.
                            Promove outro utilizador a administrador na página <a href="admin.php">Administração</a> e depois tenta outra vez.
                        </div>
                    <?php else: ?>
                        <p><strong>ATENÇÃO: Esta ação é irreversível!</strong> Todos os teus dados e atividades serão apagados.</p>
                        <input type="password" name="password_apagar" class="form-control" placeholder="Confirma com a tua palavra-passe" required>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger" <?= $ultimoAdmin ? 'disabled' : '' ?>>Apagar Conta</button>
                </div>
            </form>
        </div>
    </div>
<?php require 'includes/footer.php'; ?>
