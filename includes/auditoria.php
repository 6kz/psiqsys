<?php

function getUserIP()
{
    // Check if the IP is passed from a proxy/CDN
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Can contain a comma-separated list of IPs; the first one is the original client
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipList[0]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // Validate the IP address (ensures it's a valid IPv4 or IPv6)
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}


function registarLog(
    PDO $pdo,
    string $tabela,
    string $acao_original,
    ?int $id_registo = null,
    ?int $id_paciente = null
) {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $id_utilizador = $_SESSION['currentID'] ?? 0;
    $ip = getUserIP();

    // Mapeia strings personalizadas para os ENUMs permitidos da tua BD
    $acao = 'SELECT';
    $acao_upper = strtoupper($acao_original);

    if (strpos($acao_upper, 'SEARCH') !== false || $acao_upper === 'SELECT') {
        $acao = 'SELECT';
    } elseif ($acao_upper === 'INSERT') {
        $acao = 'INSERT';
    } elseif ($acao_upper === 'UPDATE' || $acao_upper === 'DISABLE') {
        $acao = 'UPDATE';
    } elseif ($acao_upper === 'DELETE') {
        $acao = 'DELETE';
    }

    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

        // CORREÇÃO: Adicionado o $tabela que estava em falta na correspondência das '?'
        $stmt->execute([
            $id_utilizador,  // 1ª ?
            $id_paciente,    // 2ª ?
            $tabela,         // 3ª ? 
            $id_registo,     // 4ª ?
            $acao,           // 5ª ?
            $ip              // 6ª ?
        ]);
    } catch (PDOException $e) {
        die("Erro crítico ao gravar log de auditoria: " . $e->getMessage());
    }
}
