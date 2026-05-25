<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (isset($_SESSION['currentFuncao']) && ($_SESSION['currentFuncao'] === 'administrativo' || $_SESSION['currentFuncao'] === 'administrative')) {
    header('Location: pacientes.php?erro=sem_permissao');
    exit;
}

require_once 'includes/db.php';
$pagina_atual  = 'eventos';
$titulo_pagina = 'Eventos Críticos';

// ── Novo evento ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'novo') {
    $stmt = $pdo->prepare("
        INSERT INTO EVENTO_CRITICO
          (id_internamento, id_profissional, data_hora, tipo_evento,
            descricao, intervencao_realizada, gravidade)
        VALUES (?,?,?,?,?,?,?)
    ");
    $stmt->execute([
        (int)$_POST['id_internamento'],
        (int)$_POST['id_profissional'],
        $_POST['data_hora'],
        $_POST['tipo_evento'],
        trim($_POST['descricao']),
        trim($_POST['intervencao_realizada']),
        $_POST['gravidade'],
    ]);
    $redir = (int)$_POST['id_internamento'];
    header("Location: eventos.php?ok=1" . ($redir ? "&internamento=$redir" : ''));
    exit;
}

// ── Filtros ────────────────────────────────────────────────
$filtro_int  = (int)($_GET['internamento'] ?? 0);
$filtro_grav = $_GET['gravidade'] ?? '';
$filtro_tipo = $_GET['tipo'] ?? '';
$search      = trim($_GET['q'] ?? '');

$where  = '1=1';
$params = [];

if ($filtro_int) {
    $where   .= ' AND e.id_internamento = ?';
    $params[] = $filtro_int;
}
if ($filtro_grav) {
    $where   .= ' AND e.gravidade = ?';
    $params[] = $filtro_grav;
}
if ($filtro_tipo) {
    $where   .= ' AND e.tipo_evento = ?';
    $params[] = $filtro_tipo;
}
if ($search) {
    $where   .= ' AND (p.nome LIKE ? OR e.descricao LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$rows = $pdo->prepare("
    SELECT e.*,
           p.nome AS paciente,
           prof.nome AS profissional
    FROM EVENTO_CRITICO e
    JOIN INTERNAMENTO i   ON i.id_internamento    = e.id_internamento
    JOIN PACIENTE p       ON p.id_paciente         = i.id_paciente
    JOIN PROFISSIONAL prof ON prof.id_profissional = e.id_profissional
    WHERE $where
    ORDER BY e.data_hora DESC
    LIMIT 200
");
$rows->execute($params);
$rows = $rows->fetchAll();

// Stats
$stats_grav = $pdo->query("SELECT gravidade, COUNT(*) AS n FROM EVENTO_CRITICO GROUP BY gravidade")->fetchAll(PDO::FETCH_KEY_PAIR);
$stats_hoje = $pdo->query("SELECT COUNT(*) FROM EVENTO_CRITICO WHERE DATE(data_hora)=CURDATE()")->fetchColumn();
$stats_tipo = $pdo->query("SELECT tipo_evento, COUNT(*) AS n FROM EVENTO_CRITICO GROUP BY tipo_evento ORDER BY n DESC")->fetchAll();

// Para modal
$internamentos_ativos = $pdo->query("
    SELECT i.id_internamento, p.nome AS paciente, p.num_utente
    FROM INTERNAMENTO i JOIN PACIENTE p ON p.id_paciente = i.id_paciente
    WHERE i.data_alta IS NULL ORDER BY p.nome
")->fetchAll();
$profissionais = $pdo->query("SELECT id_profissional, nome, funcao FROM PROFISSIONAL ORDER BY nome")->fetchAll();

require_once 'includes/header.php';

function gravidade_badge(string $g): string
{
    $map = ['baixa' => 'green', 'moderada' => 'amber', 'elevada' => 'red', 'critica' => 'red'];
    $extra = $g === 'critica' ? ' style="animation:pulse 1s infinite"' : '';
    return '<span class="badge badge-' . ($map[$g] ?? 'gray') . '"' . $extra . '>' . $g . '</span>';
}

$tipos_label = [
    'autoagressao'    => 'Autoagressão',
    'heteroagressao'  => 'Heteroagressão',
    'fuga'            => 'Fuga',
    'queda'           => 'Queda',
    'crise_convulsiva' => 'Crise Convulsiva',
    'recusa_alimentar' => 'Recusa Alimentar',
    'outro'           => 'Outro',
];
?>

<div class="content-area">

    <?php if (isset($_GET['ok'])): ?>
        <div class="alert alert-success mb-4">
            <i class="ti ti-circle-check"></i> Evento crítico registado com sucesso.
        </div>
    <?php endif; ?>

    <div class="stats-grid mb-4">
        <div class="stat-card red">
            <div class="stat-icon"><i class="ti ti-alert-triangle" style="color:var(--danger)"></i></div>
            <div class="stat-label">Hoje</div>
            <div class="stat-value"><?= $stats_hoje ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="ti ti-circle-check" style="color:var(--success)"></i></div>
            <div class="stat-label">Gravidade Baixa</div>
            <div class="stat-value"><?= $stats_grav['baixa'] ?? 0 ?></div>
        </div>
        <div class="stat-card amber">
            <div class="stat-icon"><i class="ti ti-alert-circle" style="color:var(--warning)"></i></div>
            <div class="stat-label">Gravidade Moderada</div>
            <div class="stat-value"><?= $stats_grav['moderada'] ?? 0 ?></div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon"><i class="ti ti-flame" style="color:var(--danger)"></i></div>
            <div class="stat-label">Gravidade Elevada</div>
            <div class="stat-value"><?= ($stats_grav['elevada'] ?? 0) + ($stats_grav['critica'] ?? 0) ?></div>
        </div>
    </div>

    <?php if ($filtro_int): ?>
        <?php
        $ii = $pdo->prepare("SELECT p.nome FROM INTERNAMENTO i JOIN PACIENTE p ON p.id_paciente=i.id_paciente WHERE i.id_internamento=?");
        $ii->execute([$filtro_int]);
        $ii = $ii->fetch();
        ?>
        <div class="alert alert-warning mb-4">
            <i class="ti ti-filter"></i>
            A filtrar por internamento #<?= $filtro_int ?> — <strong><?= htmlspecialchars($ii['nome'] ?? '') ?></strong>
            &nbsp;<a href="eventos.php" class="btn btn-outline btn-sm">Ver todos</a>
        </div>
    <?php endif; ?>

    <div class="flex items-center justify-between mb-4">
        <div class="flex gap-2" style="flex-wrap:wrap">
            <a href="eventos.php<?= $filtro_int ? "?internamento=$filtro_int" : '' ?>" class="btn <?= !$filtro_grav ? 'btn-primary' : 'btn-outline' ?> btn-sm">Todos</a>
            <?php foreach (['baixa', 'moderada', 'elevada', 'critica'] as $g): ?>
                <a href="?<?= $filtro_int ? "internamento=$filtro_int&" : '' ?>gravidade=<?= $g ?>"
                    class="btn <?= $filtro_grav === $g ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                    <?= ucfirst($g) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="flex gap-3 items-center">
            <form method="get" style="display:flex;gap:8px;align-items:center">
                <?php if ($filtro_int): ?><input type="hidden" name="internamento" value="<?= $filtro_int ?>"><?php endif; ?>
                <div class="search-bar">
                    <i class="ti ti-search"></i>
                    <input type="text" name="q" placeholder="Pesquisar paciente…" value="<?= htmlspecialchars($search) ?>">
                </div>
            </form>
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-novo')">
                <i class="ti ti-plus"></i> Registar Evento
            </button>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start">

        <div class="card">
            <div class="card-header">
                <span class="card-title" style="color:var(--danger)"><i class="ti ti-alert-triangle"></i> Eventos Críticos</span>
                <span class="text-sm text-muted"><?= count($rows) ?> registos</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Paciente</th>
                            <th>Tipo</th>
                            <th>Gravidade</th>
                            <th>Profissional</th>
                            <th>Descrição</th>
                            <th>Intervenção</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td class="mono text-sm"><?= date('d/m/Y H:i', strtotime($r['data_hora'])) ?></td>
                                <td>
                                    <a href="internamento_detalhe.php?id=<?= $r['id_internamento'] ?>" class="fw-600">
                                        <?= htmlspecialchars($r['paciente']) ?>
                                    </a>
                                    <div class="text-sm text-muted">Int. #<?= $r['id_internamento'] ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-red">
                                        <?= $tipos_label[$r['tipo_evento']] ?? $r['tipo_evento'] ?>
                                    </span>
                                </td>
                                <td><?= gravidade_badge($r['gravidade']) ?></td>
                                <td class="text-sm"><?= htmlspecialchars($r['profissional']) ?></td>
                                <td class="text-sm" style="max-width:200px">
                                    <span title="<?= htmlspecialchars($r['descricao']) ?>">
                                        <?= htmlspecialchars(substr($r['descricao'], 0, 70)) ?><?= strlen($r['descricao']) > 70 ? '…' : '' ?>
                                    </span>
                                </td>
                                <td class="text-sm" style="max-width:180px">
                                    <span title="<?= htmlspecialchars($r['intervencao_realizada']) ?>">
                                        <?= htmlspecialchars(substr($r['intervencao_realizada'], 0, 60)) ?><?= strlen($r['intervencao_realizada']) > 60 ? '…' : '' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="7" class="text-muted text-sm" style="text-align:center;padding:32px">
                                    <i class="ti ti-mood-happy" style="font-size:32px;display:block;margin-bottom:8px;color:var(--success)"></i>
                                    Nenhum evento crítico registado
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="ti ti-chart-bar"></i> Por Tipo</span>
                </div>
                <div class="card-body" style="padding:16px">
                    <?php if (empty($stats_tipo)): ?>
                        <div class="text-muted text-sm">Sem dados</div>
                    <?php else: ?>
                        <?php
                        $max_tipo = max(array_column($stats_tipo, 'n'));
                        foreach ($stats_tipo as $st):
                            $pct = $max_tipo > 0 ? round($st['n'] / $max_tipo * 100) : 0;
                        ?>
                            <div style="margin-bottom:12px">
                                <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                                    <span class="text-sm"><?= $tipos_label[$st['tipo_evento']] ?? $st['tipo_evento'] ?></span>
                                    <span class="mono text-sm fw-600"><?= $st['n'] ?></span>
                                </div>
                                <div style="height:6px;background:var(--border);border-radius:3px">
                                    <div style="height:100%;width:<?= $pct ?>%;background:var(--danger);border-radius:3px;transition:width .4s"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</div>
<div class="modal-overlay" id="modal-novo" style="display: none;">
    <div class="modal" style="max-width:680px">
        <div class="modal-header">
            <span class="modal-title" style="color:var(--danger)">
                <i class="ti ti-alert-triangle" style="margin-right:8px"></i>Registar Evento Crítico
            </span>
            <button class="btn btn-outline btn-icon" onclick="closeModal('modal-novo')"><i class="ti ti-x"></i></button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="novo">
            <div class="modal-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Internamento (Paciente) *</label>
                        <select name="id_internamento" class="form-control" required>
                            <option value="">— Selecionar —</option>
                            <?php foreach ($internamentos_ativos as $ia): ?>
                                <option value="<?= $ia['id_internamento'] ?>" <?= $filtro_int == $ia['id_internamento'] ? 'selected' : '' ?>>
                                    #<?= $ia['id_internamento'] ?> — <?= htmlspecialchars($ia['paciente']) ?> (<?= $ia['num_utente'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Profissional *</label>
                        <select name="id_profissional" class="form-control" required>
                            <option value="">— Selecionar —</option>
                            <?php foreach ($profissionais as $pf): ?>
                                <option value="<?= $pf['id_profissional'] ?>"><?= htmlspecialchars($pf['nome']) ?> (<?= $pf['funcao'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data/Hora *</label>
                        <input type="datetime-local" name="data_hora" class="form-control" required value="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo de Evento *</label>
                        <select name="tipo_evento" class="form-control" required>
                            <?php foreach ($tipos_label as $val => $label): ?>
                                <option value="<?= $val ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Gravidade *</label>
                        <div style="display:flex;gap:10px;flex-wrap:wrap">
                            <?php foreach (['baixa' => 'green', 'moderada' => 'amber', 'elevada' => 'red', 'critica' => 'red'] as $g => $c): ?>
                                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:7px 14px;border:1px solid var(--border-dark);border-radius:var(--radius-sm)">
                                    <input type="radio" name="gravidade" value="<?= $g ?>" <?= $g === 'baixa' ? 'checked' : '' ?>>
                                    <span class="badge badge-<?= $c ?>"><?= ucfirst($g) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Descrição do Evento *</label>
                        <textarea name="descricao" class="form-control" required placeholder="Descreva o que aconteceu…" style="min-height:80px"></textarea>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Intervenção Realizada *</label>
                        <textarea name="intervencao_realizada" class="form-control" required placeholder="Descreva as medidas tomadas…" style="min-height:80px"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-novo')">Cancelar</button>
                <button type="submit" class="btn btn-danger"><i class="ti ti-check"></i> Registar Evento</button>
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