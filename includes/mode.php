<?php

define('MODE_FILE', __DIR__ . '/../data/mode.txt');
define('MODE_SEGURO', 'seguro');
define('MODE_VULNERAVEL', 'vulneravel');

function getModo(): string
{
    if (!file_exists(MODE_FILE)) {
        return MODE_VULNERAVEL;
    }

    $conteudo = trim(file_get_contents(MODE_FILE));

    return $conteudo === MODE_SEGURO ? MODE_SEGURO : MODE_VULNERAVEL;
}

function setModo(string $modo): void
{
    $modo = $modo === MODE_SEGURO ? MODE_SEGURO : MODE_VULNERAVEL;

    $dir = dirname(MODE_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents(MODE_FILE, $modo);
}
