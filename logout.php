<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (empty($_SESSION['currentID'])) {
    header('refresh:3; url=index.php');
    exit("Página indisponível para acesso.");
} else {
    session_destroy();
    header('location:index.php');
    exit;
}
