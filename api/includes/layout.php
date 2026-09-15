<?php

require_once __DIR__ . '/mode.php';

function render_header(string $titulo): void
{
    $modo = getModo();
    $modoLabel = $modo === MODE_SEGURO ? 'SEGURO (regenera o ID no login)' : 'VULNERÁVEL (não regenera o ID)';
    $modoCor = $modo === MODE_SEGURO ? '#1b7f3a' : '#b83b3b';
    $sessId = session_id();
    ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($titulo) ?> — Demo Session Fixation</title>
<style>
    body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; background: #f5f5f7; color: #1c1c1e; }
    header.banner { background: #1c1c1e; color: #fff; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.85rem; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
    header.banner code { background: #333; padding: 2px 6px; border-radius: 4px; }
    .modo-tag { font-weight: bold; }
    nav a { margin-right: 1rem; font-size: 0.9rem; }
    .card { background: #fff; border-radius: 10px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
    input[type=text], input[type=password] { padding: 0.5rem; width: 100%; box-sizing: border-box; margin-bottom: 0.75rem; border: 1px solid #ccc; border-radius: 6px; }
    button, .btn { display: inline-block; padding: 0.5rem 1rem; background: #1c1c1e; color: #fff; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; font-size: 0.9rem; }
    button.secondary, .btn.secondary { background: #6b6b70; }
    .erro { color: #b83b3b; font-weight: bold; }
    .link-malicioso { word-break: break-all; background: #f0f0f2; padding: 0.75rem; border-radius: 6px; font-family: monospace; }
    footer { font-size: 0.8rem; color: #6b6b70; margin-top: 2rem; text-align: center; }
</style>
</head>
<body>
<header class="banner">
    <div>PHPSESSID atual: <code><?= htmlspecialchars($sessId) ?></code></div>
    <div class="modo-tag" style="color: <?= $modoCor ?>">Modo: <?= $modoLabel ?></div>
</header>
<nav>
    <a href="index.php">Início</a>
    <a href="attacker.php">Console do Atacante</a>
    <a href="login.php">Login</a>
    <a href="account.php">Minha Conta</a>
    <a href="logout.php">Sair</a>
</nav>
<h1><?= htmlspecialchars($titulo) ?></h1>
    <?php
}

function render_footer(): void
{
    ?>
<footer>Demo educacional de fixação de sessão — apenas para uso local/didático.</footer>
</body>
</html>
    <?php
}
