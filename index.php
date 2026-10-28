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

$titulo = 'Entrar';
$authBoasVindas = 'Bem-vindo de volta';
$authSubtitulo = 'Entra para veres o treino e o plano alimentar de hoje.';
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
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="nome@email.com" value="<?= e($email) ?>" autocomplete="email" required autofocus>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Palavra-passe</label>
                    <div class="campo-password">
                        <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" required>
                        <button type="button" class="ver-password" aria-label="Mostrar a palavra-passe" aria-controls="password"><i class="bi bi-eye" aria-hidden="true"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-principal btn-grande w-100">Entrar</button>
            </form>

            <p class="auth-rodape">Ainda não tens conta? <a href="registo.php" class="link-acao">Criar conta</a></p>
        </div>
    </div>
    <script>
        // Mostrar/esconder a palavra-passe (o ícone do olho)
        document.querySelectorAll('.ver-password').forEach(function (botao) {
            botao.addEventListener('click', function () {
                var campo = document.getElementById(botao.getAttribute('aria-controls'));
                var mostrar = campo.type === 'password';
                campo.type = mostrar ? 'text' : 'password';
                botao.querySelector('i').className = 'bi ' + (mostrar ? 'bi-eye-slash' : 'bi-eye');
                botao.setAttribute('aria-label', mostrar ? 'Esconder a palavra-passe' : 'Mostrar a palavra-passe');
            });
        });
    </script>
</body>
</html>
