<?php
session_start();

require_once 'includes/db.php';
$pagina_atual  = 'camas';
$titulo_pagina = 'Gestão de Camas';

// ── Atualizar estado de uma cama ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'estado') {
    $stmt = $pdo->prepare("UPDATE CAMA SET estado=? WHERE id_cama=?");
    $stmt->execute([$_POST['estado'], (int)$_POST['id_cama']]);
    header('Location: camas.php?ok=estado');
    exit;
}

// ── Nova cama ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'nova') {
    $stmt = $pdo->prepare("INSERT INTO CAMA (id_quarto, numero_cama, estado) VALUES (?,?,?)");
    $stmt->execute([(int)$_POST['id_quarto'], trim($_POST['numero_cama']), $_POST['estado']]);
    header('Location: camas.php?ok=nova');
    exit;
}

// ── Novo quarto ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'quarto') {
    $stmt = $pdo->prepare("INSERT INTO QUARTO (id_servico, numero_quarto) VALUES (?,?)");
    $stmt->execute([(int)$_POST['id_servico'], trim($_POST['numero_quarto'])]);
    header('Location: camas.php?ok=quarto');
    exit;
}

// ── Dados ──────────────────────────────────────────────────
$filtro_estado = $_GET['estado'] ?? '';

$where  = '1=1';
$params = [];
if ($filtro_estado) {
    $where   .= ' AND c.estado = ?';
    $params[] = $filtro_estado;
}

$camas = $pdo->prepare("
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
$camas->execute($params);
$camas = $camas->fetchAll();

// Stats
$stats_estado = $pdo->query("
    SELECT estado, COUNT(*) AS total
    FROM CAMA GROUP BY estado
")->fetchAll(PDO::FETCH_KEY_PAIR);

$total_camas = array_sum($stats_estado);
$taxa_ocupacao = $total_camas > 0
    ? round(($stats_estado['ocupada'] ?? 0) / $total_camas * 100)
    : 0;

// Para modal de nova cama
$quartos = $pdo->query("
    SELECT q.id_quarto, q.numero_quarto, s.nome AS servico
    FROM QUARTO q JOIN SERVICO s ON s.id_servico = q.id_servico
    ORDER BY s.nome, q.numero_quarto
")->fetchAll();

$servicos = $pdo->query("SELECT * FROM SERVICO ORDER BY nome")->fetchAll();

require_once 'includes/header.php';

function estado_badge(string $e): string
{
    $map = ['disponivel' => 'green', 'ocupada' => 'red', 'interdita' => 'amber', 'manutencao' => 'gray'];
    return '<span class="badge badge-' . ($map[$e] ?? 'gray') . '">' . $e . '</span>';
}
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success mb-4">
        <i class="ti ti-circle-check"></i>
        <?php
        $msgs = ['estado' => 'Estado da cama atualizado.', 'nova' => 'Cama criada com sucesso.', 'quarto' => 'Quarto criado com sucesso.'];
        echo $msgs[$_GET['ok']] ?? 'Operação realizada com sucesso.';
        ?>
    </div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid mb-4">
    <div class="stat-card blue">
        <div class="stat-icon"><i class="ti ti-building-hospital" style="color:var(--primary)"></i></div>
        <div class="stat-label">Total Camas</div>
        <div class="stat-value"><?= $total_camas ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="ti ti-check-circle" style="color:var(--success)"></i></div>
        <div class="stat-label">Disponíveis</div>
        <div class="stat-value"><?= $stats_estado['disponivel'] ?? 0 ?></div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon"><i class="ti ti-bed" style="color:var(--danger)"></i></div>
        <div class="stat-label">Ocupadas</div>
        <div class="stat-value"><?= $stats_estado['ocupada'] ?? 0 ?></div>
    </div>
    <div class="stat-card amber">
        <div class="stat-icon"><i class="ti ti-lock" style="color:var(--warning)"></i></div>
        <div class="stat-label">Interditas</div>
        <div class="stat-value"><?= $stats_estado['interdita'] ?? 0 ?></div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-icon"><i class="ti ti-tools" style="color:var(--info)"></i></div>
        <div class="stat-label">Manutenção</div>
        <div class="stat-value"><?= $stats_estado['manutencao'] ?? 0 ?></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon"><i class="ti ti-percentage" style="color:var(--primary)"></i></div>
        <div class="stat-label">Taxa Ocupação</div>
        <div class="stat-value"><?= $taxa_ocupacao ?>%</div>
        <!-- Barra de progresso -->
        <div style="height:4px;background:var(--border);border-radius:2px;margin-top:4px">
            <div style="height:100%;width:<?= $taxa_ocupacao ?>%;background:var(--primary);border-radius:2px;transition:width .4s"></div>
        </div>
    </div>
</div>

<!-- Filtros + Ações -->
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

<!-- Mapa visual de camas por quarto -->
<?php
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
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;padding:20px">
            <?php foreach ($lista as $c): ?>
                <?php
                $cor = ['disponivel' => '#dcfce7', 'ocupada' => '#fee2e2', 'interdita' => '#fef3c7', 'manutencao' => '#f3f4f6'];
                $borda = ['disponivel' => 'var(--success)', 'ocupada' => 'var(--danger)', 'interdita' => 'var(--warning)', 'manutencao' => 'var(--border-dark)'];
                ?>
                <div style="background:<?= $cor[$c['estado']] ?? '#f9fafb' ?>;border:2px solid <?= $borda[$c['estado']] ?? 'var(--border)' ?>;border-radius:var(--radius);padding:16px">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
                        <div>
                            <div style="font-size:1.3rem;font-weight:700;font-family:var(--mono)">C<?= htmlspecialchars($c['numero_cama']) ?></div>
                            <div class="text-sm text-muted">Cama #<?= $c['id_cama'] ?></div>
                        </div>
                        <?= estado_badge($c['estado']) ?>
                    </div>
                    <?php if ($c['paciente_atual']): ?>
                        <div style="font-size:.82rem;margin-bottom:10px">
                            <i class="ti ti-user" style="font-size:13px"></i>
                            <?= htmlspecialchars($c['paciente_atual']) ?>
                        </div>
                    <?php else: ?>
                        <div style="font-size:.78rem;color:var(--text-3);margin-bottom:10px">Sem paciente</div>
                    <?php endif; ?>
                    <?php if ($c['estado'] !== 'ocupada'): ?>
                        <form method="post" style="display:flex;gap:6px;align-items:center">
                            <input type="hidden" name="action" value="estado">
                            <input type="hidden" name="id_cama" value="<?= $c['id_cama'] ?>">
                            <select name="estado" class="form-control" style="font-size:.78rem;padding:4px 8px">
                                <?php foreach (['disponivel', 'interdita', 'manutencao'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $c['estado'] === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline btn-sm" style="padding:4px 8px;font-size:.75rem">OK</button>
                        </form>
                    <?php else: ?>
                        <div class="text-sm text-muted" style="font-size:.78rem">Estado gerido pelo internamento</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

<?php if (empty($camas)): ?>
    <div class="card">
        <div style="text-align:center;padding:48px;color:var(--text-3)">
            <i class="ti ti-bed" style="font-size:40px;display:block;margin-bottom:12px"></i>
            Nenhuma cama encontrada
        </div>
    </div>
<?php endif; ?>

<!-- Modal Nova Cama -->
<div class="modal-overlay" id="modal-nova-cama">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="ti ti-bed" style="color:var(--primary);margin-right:8px"></i>Nova Cama</span>
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

<!-- Modal Novo Quarto -->
<div class="modal-overlay" id="modal-quarto">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="ti ti-door" style="color:var(--primary);margin-right:8px"></i>Novo Quarto</span>
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
        document.getElementById(id).classList.add('open');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
    }
    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.addEventListener('click', e => {
            if (e.target === m) m.classList.remove('open');
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>