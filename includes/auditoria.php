<?php

function registarLog(
    PDO $pdo,
    string $tabela,
    string $acao,
    ?int $id_registo = null,
    ?int $id_paciente = null
) {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $id_utilizador = $_SESSION['currentID'] ?? null;

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $stmt = $pdo->prepare("
        INSERT INTO LOG_ACESSO
        (
            id_utilizador,
            id_paciente,
            tabela_acedida,
            id_registo,
            acao,
            data_hora,
            ip_origem
        )
        VALUES (?, ?, ?, ?, ?, NOW(), ?)
    ");

    $stmt->execute([
        $id_utilizador,
        $id_paciente,
        $tabela,
        $id_registo,
        $acao,
        $ip
    ]);
}
