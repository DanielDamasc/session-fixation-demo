<?php

// Front controller único: todas as rotas passam por esta mesma Serverless
// Function (veja vercel.json). Isso é necessário porque, na Vercel, cada
// arquivo PHP diferente vira uma função isolada com seu próprio /tmp — se
// login.php e account.php fossem funções separadas, a sessão gravada em
// uma nunca seria visível na outra. Concentrando tudo aqui, requisições
// sequenciais do mesmo visitante tendem a cair no mesmo container "quente"
// e, portanto, compartilhar o /tmp onde o PHP guarda as sessões.

require_once __DIR__ . '/includes/mode.php';
require_once __DIR__ . '/includes/layout.php';

$path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

$rotas = [
    ''             => 'home.php',
    'index.php'    => 'home.php',
    'login.php'    => 'login.php',
    'logout.php'   => 'logout.php',
    'account.php'  => 'account.php',
    'reset.php'    => 'reset.php',
    'attacker.php' => 'attacker.php',
];

if (!array_key_exists($path, $rotas)) {
    http_response_code(404);
    echo 'Página não encontrada.';
    exit;
}

require __DIR__ . '/pages/' . $rotas[$path];
