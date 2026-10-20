<?php
require_once 'includes/funcoes.php';

if (!empty($_SESSION['utilizador_id'])) {
    redirecionar('dashboard.php');
}

// No registo só se escolhe entre estes três objetivos (a recomposição fica nas Configurações)
const OBJETIVOS_REGISTO = ['perda_peso', 'manutencao', 'ganho_massa'];

$erros = [];
$dados = ['nome' => '', 'email' => '', 'data_nascimento' => '', 'sexo' => '', 'peso' => '', 'altura' => '', 'gordura' => '', 'objetivo' => ''];

/** Lê um número do formulário (aceita vírgula). Devolve null se vier vazio. */
function numero_registo(string $valor, float $min, float $max, string $nome, bool $obrigatorio, array &$erros): ?float
{
    $valor = str_replace(',', '.', $valor);
    if ($valor === '') {
        if ($obrigatorio) {
            $erros[] = "Indica $nome.";
        }
        return null;
    }
    if (!is_numeric($valor) || $valor < $min || $valor > $max) {
        $erros[] = ucfirst($nome) . " tem de estar entre $min e $max.";
        return null;
    }
    return (float) $valor;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    foreach ($dados as $campo => $_) {
        $dados[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    // ----- Conta -----
    if (mb_strlen($dados['nome']) < 2) {
        $erros[] = 'Indica o teu nome.';
    }
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'O email não é válido.';
    }
    if (strlen($password) < 8) {
        $erros[] = 'A palavra-passe tem de ter pelo menos 8 caracteres.';
    }
    if ($password !== $confirmar) {
        $erros[] = 'As palavras-passe não coincidem.';
    }

    // ----- Dados iniciais -----
    $nasc = DateTime::createFromFormat('!Y-m-d', $dados['data_nascimento']);
    if (!$nasc || $nasc->format('Y-m-d') !== $dados['data_nascimento']) {
        $erros[] = 'Indica a tua data de nascimento.';
    } else {
        $idade = $nasc->diff(new DateTime('today'))->y;
        if ($idade < 10 || $idade > 100) {
            $erros[] = 'A idade tem de estar entre 10 e 100 anos.';
        }
    }
    if (!isset(SEXOS[$dados['sexo']])) {
        $erros[] = 'Indica o sexo (serve para classificar a % de gordura).';
    }
    $peso    = numero_registo($dados['peso'], 30, 300, 'o peso (kg)', true, $erros);
    $altura  = numero_registo($dados['altura'], 100, 250, 'a altura (cm)', true, $erros);
    $gordura = numero_registo($dados['gordura'], 3, 60, 'a percentagem de gordura', false, $erros);
    if (!in_array($dados['objetivo'], OBJETIVOS_REGISTO, true)) {
        $erros[] = 'Escolhe o teu objetivo.';
    }

    if (!$erros) {
        $stmt = db()->prepare('SELECT 1 FROM utilizadores WHERE email = ?');
        $stmt->execute([$dados['email']]);
        if ($stmt->fetch()) {
            $erros[] = 'Já existe uma conta com este email.';
        }
    }

    if (!$erros) {
        // Novas contas começam com os planos intermédios por omissão
        $stmt = db()->prepare(
            'INSERT INTO utilizadores (nome, email, password_hash, data_nascimento, sexo, altura_cm, peso_atual, massa_gorda, objetivo,
                                       plano_corrida_id, plano_musculacao_id, plano_alimentacao_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 2, 4, 5)'
        );
        $stmt->execute([
            $dados['nome'], $dados['email'], password_hash($password, PASSWORD_DEFAULT),
            $dados['data_nascimento'], $dados['sexo'], (int) round($altura), $peso, $gordura, $dados['objetivo'],
        ]);
        $id = (int) db()->lastInsertId();

        // A primeira pesagem fica logo no histórico de peso
        registar_pesagem($id, date('Y-m-d'), $peso, $gordura);

        session_regenerate_id(true);
        $_SESSION['utilizador_id'] = $id;
        flash('success', 'Conta criada! Bem-vindo ao Athlytic.');
        redirecionar('dashboard.php');
    }
}

$titulo = 'Criar conta';
require 'includes/auth_layout.php';
?>
            <?php if ($erros): ?>
                <div class="alert alert-danger py-2">
                    <?php foreach ($erros as $erro): ?><div><?= e($erro) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" novalidate class="text-start">
                <?= csrf_campo() ?>

                <h6 class="registo-secao"><i class="bi bi-person-circle me-1"></i> A tua conta</h6>
                <div class="mb-3">
                    <input type="text" name="nome" class="form-control" placeholder="Nome" value="<?= e($dados['nome']) ?>" required>
                </div>
                <div class="mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Email" value="<?= e($dados['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Palavra-passe (mín. 8 caracteres)" required minlength="8">
                </div>
                <div class="mb-3">
                    <input type="password" name="confirmar" class="form-control" placeholder="Confirmar palavra-passe" required>
                </div>

                <h6 class="registo-secao mt-4"><i class="bi bi-clipboard-heart me-1"></i> Os teus dados iniciais</h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label for="data_nascimento" class="form-label">Data de nascimento <span id="idadeTexto" class="registo-idade"></span></label>
                        <input type="date" id="data_nascimento" name="data_nascimento" class="form-control" value="<?= e($dados['data_nascimento']) ?>" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-12">
                        <span class="form-label d-block">Sexo</span>
                        <div class="registo-sexo" role="radiogroup" aria-label="Sexo">
                            <?php foreach (SEXOS as $valor => $nomeSexo): ?>
                                <input type="radio" class="btn-check" name="sexo" id="sexo_<?= e($valor) ?>" value="<?= e($valor) ?>" <?= $dados['sexo'] === $valor ? 'checked' : '' ?> required>
                                <label for="sexo_<?= e($valor) ?>"><i class="bi <?= $valor === 'M' ? 'bi-gender-male' : 'bi-gender-female' ?>"></i> <?= e($nomeSexo) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <label for="peso" class="form-label">Peso (kg)</label>
                        <input type="number" id="peso" name="peso" class="form-control" value="<?= e($dados['peso']) ?>" placeholder="72,5" step="0.1" min="30" max="300" inputmode="decimal" required>
                    </div>
                    <div class="col-6">
                        <label for="altura" class="form-label">Altura (cm)</label>
                        <input type="number" id="altura" name="altura" class="form-control" value="<?= e($dados['altura']) ?>" placeholder="175" min="100" max="250" inputmode="numeric" required>
                    </div>
                    <div class="col-12">
                        <label for="gordura" class="form-label">Gordura corporal (%) <small class="opacity-75">(opcional)</small></label>
                        <input type="number" id="gordura" name="gordura" class="form-control" value="<?= e($dados['gordura']) ?>" placeholder="18" step="0.1" min="3" max="60" inputmode="decimal">
                    </div>
                    <div class="col-12">
                        <label for="objetivo" class="form-label">Objetivo</label>
                        <select id="objetivo" name="objetivo" class="form-select" required>
                            <option value="" disabled <?= $dados['objetivo'] === '' ? 'selected' : '' ?>>Escolhe o teu objetivo</option>
                            <?php foreach (OBJETIVOS_REGISTO as $valor): ?>
                                <option value="<?= e($valor) ?>" <?= $dados['objetivo'] === $valor ? 'selected' : '' ?>><?= e(OBJETIVOS[$valor]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Indicadores calculados no momento pelo JavaScript (assets/js/registo.js) -->
                <div class="registo-indicadores mt-3" aria-live="polite">
                    <div class="registo-imc" id="caixaImc">
                        <span>IMC</span><strong id="valorImc">—</strong><small id="classeImc">Indica o peso e a altura</small>
                    </div>
                    <div class="registo-imc d-none" id="caixaGordura">
                        <span>Gordura</span><strong id="valorGordura">—</strong><small id="classeGordura"></small>
                    </div>
                    <div class="registo-imc d-none" id="caixaFfmi">
                        <span>FFMI</span><strong id="valorFfmi">—</strong><small id="classeFfmi"></small>
                    </div>
                    <p class="registo-nota d-none" id="notaIndicadores">Com a % de gordura, o FFMI (índice de massa magra) mostra quanto do teu peso é músculo, coisa que o IMC não distingue.</p>
                </div>

                <button type="submit" class="btn-entrar mt-4">
                    <i class="bi bi-person-plus me-2"></i> CRIAR CONTA
                </button>
            </form>

            <div class="mt-4">
                <small class="text-secondary">Já tens conta?
                    <a href="index.php" class="text-gold text-decoration-none fw-bold">Entrar</a>
                </small>
            </div>
        </div>
    </div>
    <script src="assets/js/registo.js"></script>
</body>
</html>
