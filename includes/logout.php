<?php
session_start();

if (empty($_SESSION['currentID'])) {
    header('refresh:3; url=../index');
    exit("Página indisponível para acesso.");
} else {
    session_destroy();
    header('location:../index');
    exit;
}
