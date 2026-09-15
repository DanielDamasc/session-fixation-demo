<?php

session_start();

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

render_header('Minha Conta');
?>
<div class="card">
    <p>Bem-vindo(a), <strong><?= htmlspecialchars($_SESSION['user']) ?></strong>!</p>
    <p>Última atividade: <?= date('d/m/Y H:i:s') ?> (gerada a cada carregamento, é fake)</p>
    <p>Session ID que autenticou esta conta: <code><?= htmlspecialchars(session_id()) ?></code></p>
    <p style="font-size:0.85rem;color:#6b6b70">
        Se este Session ID for igual ao ID fixo mostrado no Console do Atacante,
        a sessão foi sequestrada com sucesso.
    </p>
</div>
<?php
render_footer();
