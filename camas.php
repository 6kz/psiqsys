<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/auditoria.php'; // Garante o acesso às funções de log

$pagina_atual  = 'camas';
$titulo_pagina = 'Gestão de Camas';

// ── 1. PROCESSAMENTO DE FORMULÁRIOS (AÇÕES POST) ───────────────────────────

// Atualizar estado de uma cama
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'estado') {
    $id_cama = (int)$_POST['id_cama'];
    $novo_estado = $_POST['estado'];

    $stmt = $pdo->prepare("UPDATE CAMA SET estado=? WHERE id_cama=?");
    $stmt->execute([$novo_estado, $id_cama]);

    // Auditoria: Registo de alteração de estado da infraestrutura
    registarLog($pdo, 'CAMA', 'UPDATE', $id_cama, null);

    header('Location: camas.php?ok=estado');
    exit;
}

// Criar nova cama
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'nova') {
    $id_quarto = (int)$_POST['id_quarto'];
    $numero_cama = trim($_POST['numero_cama']);
    $estado_inicial = $_POST['estado'];

    $stmt = $pdo->prepare("INSERT INTO CAMA (id_quarto, numero_cama, estado) VALUES (?,?,?)");
    $stmt->execute([$id_quarto, $numero_cama, $estado_inicial]);

    // Auditoria: Registo da criação da nova cama
    $id_nova_cama = (int)$pdo->lastInsertId();
    registarLog($pdo, 'CAMA', 'INSERT', $id_nova_cama, null);

    header('Location: camas.php?ok=nova');
    exit;
}

// Criar novo quarto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'quarto') {
    $id_servico = (int)$_POST['id_servico'];
    $numero_quarto = trim($_POST['numero_quarto']);

    $stmt = $pdo->prepare("INSERT INTO QUARTO (id_servico, numero_quarto) VALUES (?,?)");
    $stmt->execute([$id_servico, $numero_quarto]);

    // Auditoria: Registo da criação do novo quarto
    $id_novo_quarto = (int)$pdo->lastInsertId();
    registarLog($pdo, 'QUARTO', 'INSERT', $id_novo_quarto, null);

    header('Location: camas.php?ok=quarto');
    exit;
}

// ── 2. CARREGAMENTO E FILTRAGEM DE DADOS ───────────────────────────────────

$filtro_estado = $_GET['estado'] ?? '';

$where  = '1=1';
$params = [];
if ($filtro_estado) {
    $where   .= ' AND c.estado = ?';
    $params[] = $filtro_estado;
}

// Procura as camas e faz subquery para encontrar o nome do paciente se estiver ocupada
$camas_stmt = $pdo->prepare("
    SELECT c.*, q.numero_quarto, s.nome AS servico,
           (SELECT p.nome FROM INTERNAMENTO i
            JOIN PACIENTE p ON p.id_paciente = i.id_paciente
            WHERE i.id_cama = c.id_cama AND i.data_alta IS NULL
            LIMIT 1) AS paciente_atual
    FROM CAMA c
    JOIN QUARTO q ON q.id_quarto = c.id_quarto
    JOIN SERVICO s ON s.id_servico = q.id_servico
    WHERE $where
    ORDER BY s.nome, q.numero_quarto, c.numero_cama
");
$camas_stmt->execute($params);
$camas = $camas_stmt->fetchAll();

// Métricas e Estatísticas para os Cards Superiores
$stats_estado = $pdo->query("
    SELECT estado, COUNT(*) AS total
    FROM CAMA GROUP BY estado
")->fetchAll(PDO::FETCH_KEY_PAIR);

$total_camas = array_sum($stats_estado);
$taxa_ocupacao = $total_camas > 0
    ? round(($stats_estado['ocupada'] ?? 0) / $total_camas * 100)
    : 0;

// Consultas auxiliares para popular as caixas de seleção dos Modais
$quartos = $pdo->query("
    SELECT q.id_quarto, q.numero_quarto, s.nome AS servico
    FROM QUARTO q JOIN SERVICO s ON s.id_servico = q.id_servico
    ORDER BY s.nome, q.numero_quarto
")->fetchAll();

$servicos = $pdo->query("SELECT * FROM SERVICO ORDER BY nome")->fetchAll();

require_once 'includes/header.php';

// Função auxiliar para gerar os badges de estado baseados nas classes do Design System
function estado_badge(string $e): string
{
    $map = ['disponivel' => 'green', 'ocupada' => 'red', 'interdita' => 'amber', 'manutencao' => 'gray'];
    return '<span class="badge badge-' . ($map[$e] ?? 'gray') . '">' . ucfirst($e) . '</span>';
}
?>

<div class="content-area">

    <?php if (isset($_GET['ok'])): ?>
        <div class="alert alert-success mb-4">
            <i class="ti ti-circle-check"></i>
            <?php
            $msgs = ['estado' => 'Estado da cama atualizado.', 'nova' => 'Cama criada com sucesso.', 'quarto' => 'Quarto criado com sucesso.'];
            echo $msgs[$_GET['ok']] ?? 'Operação realizada com sucesso.';
            ?>
        </div>
    <?php endif; ?>

    <div class="stats-grid mb-4">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="ti ti-building-hospital"></i></div>
            <div class="stat-label">Total Camas</div>
            <div class="stat-value"><?= $total_camas ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="ti ti-bed"></i></div>
            <div class="stat-label">Disponíveis</div>
            <div class="stat-value"><?= $stats_estado['disponivel'] ?? 0 ?></div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon"><i class="ti ti-bed"></i></div>
            <div class="stat-label">Ocupadas</div>
            <div class="stat-value"><?= $stats_estado['ocupada'] ?? 0 ?></div>
        </div>
        <div class="stat-card amber">
            <div class="stat-icon"><i class="ti ti-lock"></i></div>
            <div class="stat-label">Interditas</div>
            <div class="stat-value"><?= $stats_estado['interdita'] ?? 0 ?></div>
        </div>
        <div class="stat-card cyan">
            <div class="stat-icon"><i class="ti ti-tools"></i></div>
            <div class="stat-label">Manutenção</div>
            <div class="stat-value"><?= $stats_estado['manutencao'] ?? 0 ?></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon"><i class="ti ti-percentage"></i></div>
            <div class="stat-label">Taxa Ocupação</div>
            <div class="stat-value"><?= $taxa_ocupacao ?>%</div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?= $taxa_ocupacao ?>%"></div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4">
        <div class="flex gap-2">
            <a href="camas.php" class="btn <?= !$filtro_estado ? 'btn-primary' : 'btn-outline' ?> btn-sm">Todas</a>
            <?php foreach (['disponivel', 'ocupada', 'interdita', 'manutencao'] as $est): ?>
                <a href="?estado=<?= $est ?>" class="btn <?= $filtro_estado === $est ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                    <?= ucfirst($est) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="flex gap-2">
            <button class="btn btn-outline btn-sm" onclick="openModal('modal-quarto')">
                <i class="ti ti-plus"></i> Novo Quarto
            </button>
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-nova-cama')">
                <i class="ti ti-plus"></i> Nova Cama
            </button>
        </div>
    </div>

    <?php
    // Agrupamento lógico das camas por Bloco/Quarto correspondente
    $por_quarto = [];
    foreach ($camas as $c) {
        $key = $c['servico'] . ' — Q' . $c['numero_quarto'];
        $por_quarto[$key][] = $c;
    }
    ?>

    <?php foreach ($por_quarto as $quarto_label => $lista): ?>
        <div class="card mb-4">
            <div class="card-header">
                <span class="card-title"><i class="ti ti-door"></i> <?= htmlspecialchars($quarto_label) ?></span>
                <span class="text-sm text-muted"><?= count($lista) ?> cama(s)</span>
            </div>

            <div class="camas-layout-grid">
                <?php foreach ($lista as $c): ?>
                    <div class="cama-interactive-card cama-status-<?= $c['estado'] ?>">

                        <div class="cama-card-top">
                            <div>
                                <div class="cama-card-number">C<?= htmlspecialchars($c['numero_cama']) ?></div>
                                <div class="cama-card-id">ID #<?= $c['id_cama'] ?></div>
                            </div>
                            <?= estado_badge($c['estado']) ?>
                        </div>

                        <div class="cama-card-body">
                            <?php if ($c['paciente_atual']): ?>
                                <div class="cama-patient-info">
                                    <i class="ti ti-user"></i>
                                    <span><?= htmlspecialchars($c['paciente_atual']) ?></span>
                                </div>
                            <?php else: ?>
                                <div class="cama-empty-info">Sem paciente</div>
                            <?php endif; ?>
                        </div>

                        <div class="cama-card-actions">
                            <?php if ($c['estado'] !== 'ocupada'): ?>
                                <form method="post" class="cama-action-form">
                                    <input type="hidden" name="action" value="estado">
                                    <input type="hidden" name="id_cama" value="<?= $c['id_cama'] ?>">
                                    <select name="estado" class="form-control select-cama-sm">
                                        <?php foreach (['disponivel', 'interdita', 'manutencao'] as $opt): ?>
                                            <option value="<?= $opt ?>" <?= $c['estado'] === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-outline btn-xs">OK</button>
                                </form>
                            <?php else: ?>
                                <span class="cama-managed-text">Gerido pelo internamento</span>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($camas)): ?>
        <div class="card">
            <div class="empty-state-container">
                <i class="ti ti-bed"></i>
                <p>Nenhuma cama encontrada com os filtros selecionados.</p>
            </div>
        </div>
    <?php endif; ?>

</div>

<div class="modal-overlay" id="modal-nova-cama" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="ti ti-bed"></i> Nova Cama</span>
            <button class="btn btn-outline btn-icon" onclick="closeModal('modal-nova-cama')"><i class="ti ti-x"></i></button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="nova">
            <div class="modal-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Quarto *</label>
                        <select name="id_quarto" class="form-control" required>
                            <option value="">— Selecionar —</option>
                            <?php foreach ($quartos as $q): ?>
                                <option value="<?= $q['id_quarto'] ?>"><?= htmlspecialchars($q['servico']) ?> — Q<?= $q['numero_quarto'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Número da Cama *</label>
                        <input type="text" name="numero_cama" class="form-control" required placeholder="Ex: 1A, 2B">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estado Inicial *</label>
                        <select name="estado" class="form-control" required>
                            <option value="disponivel">Disponível</option>
                            <option value="interdita">Interdita</option>
                            <option value="manutencao">Manutenção</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-nova-cama')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Criar Cama</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modal-quarto" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="ti ti-door"></i> Novo Quarto</span>
            <button class="btn btn-outline btn-icon" onclick="closeModal('modal-quarto')"><i class="ti ti-x"></i></button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="quarto">
            <div class="modal-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Serviço *</label>
                        <select name="id_servico" class="form-control" required>
                            <option value="">— Selecionar —</option>
                            <?php foreach ($servicos as $sv): ?>
                                <option value="<?= $sv['id_servico'] ?>"><?= htmlspecialchars($sv['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Número do Quarto *</label>
                        <input type="text" name="numero_quarto" class="form-control" required placeholder="Ex: 101, A2">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-quarto')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Criar Quarto</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        var overlay = document.getElementById(id);
        if (!overlay) return;
        
        overlay.style.display = "flex";
        setTimeout(function() {
            overlay.classList.add('open');
        }, 10);
    }

    function closeModal(id) {
        var overlay = document.getElementById(id);
        if (!overlay) return;

        overlay.classList.remove('open');
        setTimeout(function() {
            if (!overlay.classList.contains('open')) {
                overlay.style.display = "none";
            }
        }, 200);
    }

    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.addEventListener('click', e => {
            if (e.target === m) {
                closeModal(m.id);
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>