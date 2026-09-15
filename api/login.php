<?php

require_once __DIR__ . '/includes/mode.php';

// Ponto vulnerável: adota um Session ID vindo da URL antes de iniciar a
// sessão. É assim que um app vulnerável a fixação de sessão aceita o ID
// escolhido pelo atacante (equivalente ao efeito de session.use_trans_sid).
if (isset($_GET['PHPSESSID']) && preg_match('/^[a-zA-Z0-9,\-]{1,128}$/', $_GET['PHPSESSID'])) {
    session_id($_GET['PHPSESSID']);
}

session_start();

require_once __DIR__ . '/includes/layout.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if ($usuario === 'vitima' && $senha === 'senha123') {
        // Defesa: gera um novo Session ID e descarta o antigo, invalidando
        // qualquer ID que um atacante tenha fixado antes do login.
        if (getModo() === MODE_SEGURO) {
            session_regenerate_id(true);
        }

        $_SESSION['user'] = $usuario;
        header('Location: account.php');
        exit;
    }

    $erro = 'Usuário ou senha inválidos.';
}

render_header('Login');
?>
<div class="card">
    <?php if ($erro): ?>
        <p class="erro"><?= htmlspecialchars($erro) ?></p>
    <?php endif; ?>
    <?php if (isset($_GET['PHPSESSID'])): ?>
        <p><strong>Atenção:</strong> esta página foi aberta com um Session ID vindo da URL
        (<code><?= htmlspecialchars($_GET['PHPSESSID']) ?></code>) — é exatamente assim que um
        link malicioso de fixação de sessão funciona.</p>
    <?php endif; ?>
    <form method="post">
        <label>Usuário
            <input type="text" name="usuario" value="vitima" required>
        </label>
        <label>Senha
            <input type="password" name="senha" value="senha123" required>
        </label>
        <button type="submit">Entrar</button>
    </form>
    <p style="font-size:0.85rem;color:#6b6b70">
        Credenciais de demonstração já preenchidas: <code>vitima</code> / <code>senha123</code>.
    </p>
</div>
<?php
render_footer();
