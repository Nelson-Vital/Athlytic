<?php
require_once 'includes/funcoes.php';

// Quem já tem sessão iniciada vai direto ao dashboard
if (!empty($_SESSION['utilizador_id'])) {
    redirecionar('dashboard.php');
}

$erro = '';
$email = '';
$contaInativa = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT id, password_hash, tema, ativo FROM utilizadores WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    // Só se diz que a conta está inativa depois de a palavra-passe estar certa
    // (assim ninguém descobre o estado de uma conta só por saber o email)
    if ($u && password_verify($password, $u['password_hash']) && !$u['ativo']) {
        $contaInativa = true;
    } elseif ($u && password_verify($password, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['utilizador_id'] = $u['id'];
        guardar_tema_cookie($u['tema']);
        redirecionar('dashboard.php');
    }
    if (!$contaInativa) {
        $erro = 'Email ou palavra-passe incorretos.';
    }
}

$titulo = 'Login';
require 'includes/auth_layout.php';
?>
            <?php if ($erro): ?>
                <div class="alert alert-danger py-2"><?= e($erro) ?></div>
            <?php endif; ?>
            <?php mostrar_flash(); ?>
            <?php if ($contaInativa): ?>
                <div class="alert alert-warning text-start py-2">
                    <strong><i class="bi bi-person-lock me-1"></i> Conta inativa</strong><br>
                    <?= e(MENSAGEM_CONTA_INATIVA) ?>
                    <ul class="mb-0 mt-2 small">
                        <?php foreach (contactos_admins() as $admin): ?>
                            <li><?= e($admin['nome']) ?>: <a href="mailto:<?= e($admin['email']) ?>"><?= e($admin['email']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?= csrf_campo() ?>
                <div class="mb-4">
                    <input type="email" name="email" class="form-control" placeholder="Email" value="<?= e($email) ?>" required autofocus>
                </div>
                <div class="mb-4">
                    <input type="password" name="password" class="form-control" placeholder="Palavra-passe" required>
                </div>
                <button type="submit" class="btn-entrar">
                    <i class="bi bi-box-arrow-in-right me-2"></i> ENTRAR
                </button>
            </form>

            <div class="mt-4">
                <small class="text-secondary">Ainda não tens conta?
                    <a href="registo.php" class="text-gold text-decoration-none fw-bold">Regista-te</a>
                </small>
            </div>
        </div>
    </div>
</body>
</html>
