<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tempo_limite = 900; // 15 minutos

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (!isset($_SESSION['currentID']) || !isset($_SESSION['currentLogin'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

if (isset($_SESSION['LAST_ACTIVITY'])) {
    $tempo_inativo = time() - $_SESSION['LAST_ACTIVITY'];

    if ($tempo_inativo > $tempo_limite) {
        session_unset();
        session_destroy();
        header("Location: index.php?timeout=1");
        exit;
    }
}

$_SESSION['LAST_ACTIVITY'] = time();