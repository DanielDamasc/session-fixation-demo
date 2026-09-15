<?php

require_once __DIR__ . '/includes/mode.php';

session_start();
unset($_SESSION['id_fixo']);
setModo(MODE_VULNERAVEL);

header('Location: attacker.php');
exit;
