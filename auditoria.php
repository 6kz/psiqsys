<?php
// auditoria.php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/logger.php'; // Inclui as funções de log caso queiras usar aqui

// Verificação de permissões (Apenas TI ou Diretor)
$is_admin = (isset($_SESSION['currentFuncao']) && ($_SESSION['currentFuncao'] === 'ti' || $_SESSION['currentFuncao'] === 'diretor'));

if (!$is_admin) {
    header('HTTP/1.1 403 Forbidden');
    echo "<div style='padding:40px; font-family:sans-serif; text-align:center;'><h2>Acesso Negado</h2><p>Não tem permissões para visualizar as logs de auditoria.</p></div>";
    header('refresh:3; url=index');
    exit;
}

$pagina_atual  = 'auditoria';
$titulo_pagina = 'Auditoria de Sistema';

// Opcional: Registar que o administrador visualizou a tabela de auditoria
// registarLog($pdo, 'LOG_ACESSO', 'SELECT', null, null);

// Filtros da listagem
$search_user = trim($_GET['user'] ?? '');
$filter_acao = $_GET['acao'] ?? 'todas';

$where = "WHERE 1=1";
$params = [];

if ($search_user) {
    $where .= " AND u.username LIKE ?";
    $params[] = "%$search_user%";
}

if ($filter_acao !== 'todas') {
    $where .= " AND l.acao = ?";
    $params[] = $filter_acao;
}

// Consulta à Base de Dados
$stmt = $pdo->prepare("
    SELECT l.*, 
           u.username,
           p.nome AS nome_paciente
    FROM LOG_ACESSO l
    LEFT JOIN UTILIZADOR u ON u.id_utilizador = l.id_utilizador
    LEFT JOIN PACIENTE p ON p.id_paciente = l.id_paciente
    $where
    ORDER BY l.data_hora DESC
    LIMIT 100
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Cabeçalho visual
require_once 'includes/header.php';
?>

<div class="content-area">
    <div class="flex items-center justify-between mb-4">
        <div class="flex gap-2">
            <a href="?acao=todas<?= $search_user ? '&user=' . urlencode($search_user) : '' ?>"
                class="btn <?= $filter_acao === 'todas' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Todas as Ações</a>
            <a href="?acao=SELECT<?= $search_user ? '&user=' . urlencode($search_user) : '' ?>"
                class="btn <?= $filter_acao === 'SELECT' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Leituras (SELECT)</a>
            <a href="?acao=INSERT<?= $search_user ? '&user=' . urlencode($search_user) : '' ?>"
                class="btn <?= $filter_acao === 'INSERT' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Criações (INSERT)</a>
            <a href="?acao=UPDATE<?= $search_user ? '&user=' . urlencode($search_user) : '' ?>"
                class="btn <?= $filter_acao === 'UPDATE' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Edições (UPDATE)</a>
        </div>

        <form method="get" style="display:flex; gap:8px; align-items:center">
            <input type="hidden" name="acao" value="<?= htmlspecialchars($filter_acao) ?>">
            <div class="search-bar">
                <i class="ti ti-search"></i>
                <input type="text" name="user" placeholder="Pesquisar por username…" value="<?= htmlspecialchars($search_user) ?>">
            </div>
            <button type="submit" class="btn btn-outline btn-sm">Filtrar</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="ti ti-shield-lock" style="color: var(--primary);"></i> Histórico de Acessos</span>
            <span class="text-sm text-muted">Últimos <?= count($logs) ?> registos</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Data / Hora</th>
                        <th>Utilizador</th>
                        <th>Ação</th>
                        <th>Tabela Acedida</th>
                        <th>ID Registo</th>
                        <th>Paciente</th>
                        <th>IP Origem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="mono text-sm"><?= date('d/m/Y H:i:s', strtotime($log['data_hora'])) ?></td>
                            <td>
                                <div class="fw-600">@<?= htmlspecialchars($log['username'] ?? 'Sistema / ID: ' . $log['id_utilizador']) ?></div>
                                <div class="mono text-xs text-muted">ID: <?= $log['id_utilizador'] ?></div>
                            </td>
                            <td>
                                <?php
                                $badge_class = 'gray';
                                if ($log['acao'] === 'INSERT') $badge_class = 'green';
                                if ($log['acao'] === 'UPDATE') $badge_class = 'amber';
                                if ($log['acao'] === 'DELETE') $badge_class = 'red';
                                if ($log['acao'] === 'SELECT') $badge_class = 'blue';
                                ?>
                                <span class="badge badge-<?= $badge_class ?> mono text-xs"><?= $log['acao'] ?></span>
                            </td>
                            <td class="mono fw-600 text-sm"><?= htmlspecialchars($log['tabela_acedida']) ?></td>
                            <td class="mono text-muted text-sm"><?= $log['id_registo'] ?? '—' ?></td>
                            <td>
                                <?php if ($log['id_paciente']): ?>
                                    <div class="text-sm fw-600"><?= htmlspecialchars($log['nome_paciente'] ?? 'Paciente Eliminado') ?></div>
                                    <div class="mono text-xs text-muted">ID: <?= $log['id_paciente'] ?></div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="mono text-sm text-muted"><?= htmlspecialchars($log['ip_origem']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:32px" class="text-muted">Nenhum registo encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>