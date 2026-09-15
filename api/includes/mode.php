<?php

// Usa o diretório temporário do sistema (sempre gravável, inclusive em
// plataformas serverless como a Vercel, onde o restante do filesystem do
// deploy é somente leitura). Como consequência, o estado do toggle não é
// compartilhado entre instâncias/cold starts diferentes — aceitável para
// uma demo didática de uso local/individual.
define('MODE_FILE', sys_get_temp_dir() . '/session-fixation-demo-mode.txt');
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

    file_put_contents(MODE_FILE, $modo);
}
