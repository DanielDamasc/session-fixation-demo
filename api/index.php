<?php

require_once __DIR__ . '/includes/mode.php';

session_start();

require_once __DIR__ . '/includes/layout.php';
render_header('Demo: Ataque de Fixação de Sessão');
?>
<div class="card">
    <p>Esta demo mostra, na prática, como um <strong>ataque de fixação de sessão
    (session fixation)</strong> funciona: o atacante define de antemão o Session ID
    que a vítima vai usar; se a aplicação não gerar um novo ID após o login, o
    atacante consegue reaproveitar esse mesmo ID para assumir a sessão autenticada
    da vítima.</p>

    <p>Para acompanhar o ataque completo, você vai precisar de <strong>duas janelas
    de navegador separadas</strong> (por exemplo, uma normal e uma anônima), já que
    atacante e vítima precisam de cookies independentes:</p>
    <ol>
        <li>Na janela normal, abra o <a href="attacker.php">Console do Atacante</a> —
            é você "no papel do atacante".</li>
        <li>Copie o link malicioso gerado lá e abra numa <strong>janela anônima</strong>
            — é você "no papel da vítima".</li>
        <li>Faça login normalmente na janela anônima.</li>
        <li>Volte pra janela normal e clique em "Verificar se a vítima logou".</li>
    </ol>

    <p>Experimente repetir o processo com o toggle "Modo seguro" ligado e desligado,
    pra comparar o comportamento vulnerável com o corrigido
    (<code>session_regenerate_id()</code>).</p>

    <a class="btn" href="attacker.php">Começar como Atacante →</a>
</div>
<?php
render_footer();
