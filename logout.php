<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_unset();
session_destroy();

if (isset($_GET['timeout'])) {
    header("Location: index.php?timeout=1");
    exit;
}

header("Location: index.php");
exit;