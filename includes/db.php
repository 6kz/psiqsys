<?php

/**
 * PsiqSys — Ligação à base de dados
 * Ajusta as credenciais abaixo conforme o teu ambiente.
 */

define('DB_HOST', 'localhost'); // db
define('DB_NAME', 'bd_psiquiatria');
define('DB_USER', 'root');       // psiqsys_user      ← altera para o teu utilizador MySQL   
define('DB_PASS', '');           // user_password_secreta     ← altera para a tua password MySQL    
define('DB_CHARSET', 'utf8mb4');

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    DB_HOST,
    DB_NAME,
    DB_CHARSET
);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Em produção nunca mostrar a mensagem de erro diretamente
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(503);
    die('<div style="font-family:sans-serif;padding:40px;color:#dc2626">
        <h2>⚠️ Serviço indisponível</h2>
        <p>Não foi possível ligar à base de dados. Verifica as credenciais em <code>includes/db.php</code>.</p>
        <p style="font-size:.85rem;color:#6b7280">Erro: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>');
}
