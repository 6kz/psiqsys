<?php
// includes/logger

// Garante que a sessão está ativa antes de registar o log
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Captura o IP real do utilizador, tratando proxies
 */
function getUserIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipList[0]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Regista uma ação de auditoria na base de dados
 */
function registarLog(
    PDO $pdo,
    string $tabela,
    string $acao_original,
    ?int $id_registo = null,
    ?int $id_paciente = null
) {
    $id_utilizador = $_SESSION['currentID'] ?? 0;
    $ip = getUserIP();

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
            (id_utilizador, id_paciente, tabela_acedida, id_registo, acao, data_hora, ip_origem)
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
    } catch (PDOException $e) {
        // Em produção, considera fazer log disto num ficheiro de texto em vez de matar a aplicação com die()
        die("Erro crítico ao gravar log de auditoria: " . $e->getMessage());
    }
}
