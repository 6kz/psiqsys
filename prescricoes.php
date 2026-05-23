<?php
session_start();

require_once 'includes/db.php';
$pagina_atual  = 'prescricoes';
$titulo_pagina = 'Prescrições';

// ── Nova prescrição ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'nova') {
    $stmt = $pdo->prepare("
        INSERT INTO PRESCRICAO
          (id_internamento, id_profissional, id_medicacao, dose, via, frequencia,
           prn, dose_maxima_dia, data_inicio, data_fim, estado)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ");
    $prn = isset($_POST['prn']) ? 1 : 0;
    $stmt->execute([
        (int)$_POST['id_internamento'],
        (int)$_POST['id_profissional'],
        (int)$_POST['id_medicacao'],
        trim($_POST['dose']),
        $_POST['via'],
        trim($_POST['frequencia']),
        $prn,
        $prn ? trim($_POST['dose_maxima_dia']) : null,
        $_POST['data_inicio'],
        $_POST['data_fim'] ?: null,
        'ativa',
    ]);
    $redir = (int)$_POST['id_internamento'];
    header("Location: prescricoes.php?ok=nova" . ($redir ? "&internamento=$redir" : ''));
    exit;
}

// ── Alterar estado ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'estado') {
    $stmt = $pdo->prepare("UPDATE PRESCRICAO SET estado=? WHERE id_prescricao=?");
    $stmt->execute([$_POST['estado'], (int)$_POST['id']]);
    header('Location: prescricoes.php?ok=estado');
    exit;
}

// ── Filtros ────────────────────────────────────────────────
$filtro_int  = (int)($_GET['internamento'] ?? 0);
$filtro_est  = $_GET['estado'] ?? '';
$search      = trim($_GET['q'] ?? '');

$where  = '1=1';
$params = [];

if ($filtro_int) {
    $where   .= ' AND pr.id_internamento = ?';
    $params[] = $filtro_int;
}
if ($filtro_est) {
    $where   .= ' AND pr.estado = ?';
    $params[] = $filtro_est;
}
if ($search) {
    $where   .= ' AND (p.nome LIKE ? OR m.nome LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$rows = $pdo->prepare("
    SELECT pr.*,
           p.nome AS paciente,
           m.nome AS medicamento, m.classe,
           prof.nome AS medico
    FROM PRESCRICAO pr
    JOIN INTERNAMENTO i   ON i.id_internamento   = pr.id_internamento
    JOIN PACIENTE p       ON p.id_paciente        = i.id_paciente
    JOIN MEDICACAO m      ON m.id_medicacao        = pr.id_medicacao
    JOIN PROFISSIONAL prof ON prof.id_profissional = pr.id_profissional
    WHERE $where
    ORDER BY pr.data_inicio DESC
    LIMIT 200
");
$rows->execute($params);
$rows = $rows->fetchAll();

// Stats
$stats = $pdo->query("SELECT estado, COUNT(*) AS n FROM PRESCRICAO GROUP BY estado")->fetchAll(PDO::FETCH_KEY_PAIR);

// Para modal
$internamentos_ativos = $pdo->query("
    SELECT i.id_internamento, p.nome AS paciente, p.num_utente
    FROM INTERNAMENTO i JOIN PACIENTE p ON p.id_paciente = i.id_paciente
    WHERE i.data_alta IS NULL ORDER BY p.nome
")->fetchAll();

$medicacoes   = $pdo->query("SELECT * FROM MEDICACAO ORDER BY nome")->fetchAll();
$profissionais = $pdo->query("SELECT id_profissional, nome, funcao FROM PROFISSIONAL WHERE funcao='medico' ORDER BY nome")->fetchAll();

require_once 'includes/header.php';

function estado_badge(string $e): string
{
    $map = ['ativa' => 'green', 'suspensa' => 'amber', 'concluida' => 'gray', 'cancelada' => 'red'];
    return '<span class="badge badge-' . ($map[$e] ?? 'gray') . '">' . ucfirst($e) . '</span>';
}
?>

<div class="content-area">

    <?php if (isset($_GET['ok'])): ?>
        <div class="alert alert-success mb-4">
            <i class="ti ti-circle-check"></i>
            <?php
            $msgs = ['nova' => 'Prescrição criada com sucesso.', 'estado' => 'Estado da prescrição atualizado.'];
            echo $msgs[$_GET['ok']] ?? 'Operação realizada com sucesso.';
            ?>
        </div>
    <?php endif; ?>

    <div class="stats-grid mb-4">
        <div class="stat-card green">
            <div class="stat-icon"><i class="ti ti-pill" style="color:var(--success)"></i></div>
            <div class="stat-label">Ativas</div>
            <div class="stat-value"><?= (int)($stats['ativa'] ?? 0) ?></div>
        </div>
        <div class="stat-card amber">
            <div class="stat-icon"><i class="ti ti-player-pause" style="color:var(--warning)"></i></div>
            <div class="stat-label">Suspensas</div>
            <div class="stat-value"><?= (int)($stats['suspensa'] ?? 0) ?></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon"><i class="ti ti-circle-check" style="color:var(--primary)"></i></div>
            <div class="stat-label">Concluídas</div>
            <div class="stat-value"><?= (int)($stats['concluida'] ?? 0) ?></div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon"><i class="ti ti-ban" style="color:var(--danger)"></i></div>
            <div class="stat-label">Canceladas</div>
            <div class="stat-value"><?= (int)($stats['cancelada'] ?? 0) ?></div>
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
            &nbsp;<a href="prescricoes.php" class="btn btn-outline btn-sm" style="margin-left: auto;">Ver todas</a>
        </div>
    <?php endif; ?>

    <div class="flex items-center justify-between mb-4">
        <div class="flex gap-2">
            <a href="prescricoes.php<?= $filtro_int ? "?internamento=$filtro_int" : '' ?>" class="btn <?= !$filtro_est ? 'btn-primary' : 'btn-outline' ?> btn-sm">Todas</a>
            <?php foreach (['ativa', 'suspensa', 'concluida', 'cancelada'] as $est): ?>
                <a href="?<?= $filtro_int ? "internamento=$filtro_int&" : '' ?>estado=<?= $est ?>"
                    class="btn <?= $filtro_est === $est ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                    <?= ucfirst($est) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="flex gap-3 items-center">
            <form method="get" style="display:flex;gap:8px;align-items:center">
                <?php if ($filtro_int): ?><input type="hidden" name="internamento" value="<?= $filtro_int ?>"><?php endif; ?>
                <?php if ($filtro_est): ?><input type="hidden" name="estado" value="<?= $filtro_est ?>"><?php endif; ?>
                <div class="search-bar">
                    <i class="ti ti-search"></i>
                    <input type="text" name="q" placeholder="Paciente ou medicamento…" value="<?= htmlspecialchars($search) ?>">
                </div>
                <?php if ($search): ?>
                    <a href="prescricoes.php<?= ($filtro_int || $filtro_est) ? '?' . http_build_query(array_filter(['internamento' => $filtro_int, 'estado' => $filtro_est])) : '' ?>" class="btn btn-outline btn-icon" style="height:38px;width:38px;display:flex;align-items:center;justify-content:center">
                        <i class="ti ti-x"></i>
                    </a>
                <?php endif; ?>
            </form>
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-nova')">
                <i class="ti ti-plus"></i> Nova Prescrição
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="ti ti-pill"></i> Prescrições</span>
            <span class="text-sm text-muted"><?= count($rows) ?> registos</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Paciente</th>
                        <th>Medicamento</th>
                        <th>Classe</th>
                        <th>Dose</th>
                        <th>Via</th>
                        <th>Frequência</th>
                        <th>PRN</th>
                        <th>Estado</th>
                        <th>Início</th>
                        <th>Médico</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr style="<?= in_array($r['estado'], ['concluida', 'cancelada']) ? 'opacity: 0.65; background-color: var(--card-bg-dim, #fafafa);' : '' ?>">
                            <td class="mono text-muted"><?= (int)$r['id_prescricao'] ?></td>
                            <td>
                                <div class="fw-600"><?= htmlspecialchars($r['paciente']) ?></div>
                                <div class="text-sm text-muted">Int. #<?= (int)$r['id_internamento'] ?></div>
                            </td>
                            <td class="fw-600"><?= htmlspecialchars($r['medicamento']) ?></td>
                            <td class="text-sm text-muted"><?= htmlspecialchars($r['classe']) ?></td>
                            <td class="mono text-sm"><?= htmlspecialchars($r['dose']) ?></td>
                            <td><span class="badge badge-gray"><?= htmlspecialchars(strtoupper($r['via'])) ?></span></td>
                            <td class="text-sm"><?= htmlspecialchars($r['frequencia']) ?></td>
                            <td>
                                <?php if ($r['prn']): ?>
                                    <span class="badge badge-amber">SOS</span>
                                    <?php if ($r['dose_maxima_dia']): ?>
                                        <div class="text-sm text-muted" style="font-size:.72rem; margin-top:2px;">máx <?= htmlspecialchars($r['dose_maxima_dia']) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted text-sm">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= estado_badge($r['estado']) ?></td>
                            <td class="mono text-sm"><?= date('d/m/Y', strtotime($r['data_inicio'])) ?></td>
                            <td class="text-sm"><?= htmlspecialchars($r['medico']) ?></td>
                            <td>
                                <div class="flex gap-2">
                                    <?php if ($r['estado'] === 'ativa'): ?>
                                        <form method="post" style="display:inline">
                                            <input type="hidden" name="action" value="estado">
                                            <input type="hidden" name="id" value="<?= (int)$r['id_prescricao'] ?>">
                                            <input type="hidden" name="estado" value="suspensa">
                                            <button type="submit" class="btn btn-outline btn-icon btn-sm" title="Suspender">
                                                <i class="ti ti-player-pause"></i>
                                            </button>
                                        </form>
                                        <form method="post" style="display:inline" onsubmit="return confirm('Tem a certeza que deseja cancelar esta prescrição?')">
                                            <input type="hidden" name="action" value="estado">
                                            <input type="hidden" name="id" value="<?= (int)$r['id_prescricao'] ?>">
                                            <input type="hidden" name="estado" value="cancelada">
                                            <button type="submit" class="btn btn-outline btn-icon btn-sm" title="Cancelar" style="color:var(--danger)">
                                                <i class="ti ti-ban"></i>
                                            </button>
                                        </form>
                                    <?php elseif ($r['estado'] === 'suspensa'): ?>
                                        <form method="post" style="display:inline">
                                            <input type="hidden" name="action" value="estado">
                                            <input type="hidden" name="id" value="<?= (int)$r['id_prescricao'] ?>">
                                            <input type="hidden" name="estado" value="ativa">
                                            <button type="submit" class="btn btn-outline btn-sm" title="Reativar">
                                                <i class="ti ti-player-play"></i> Reativar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted text-sm">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="12" class="text-muted text-sm" style="text-align:center;padding:32px">
                                <i class="ti ti-mood-empty" style="font-size:32px;display:block;margin-bottom:8px;color:var(--text-muted)"></i>
                                Nenhuma prescrição encontrada
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-nova" style="display: none;">
    <div class="modal" style="max-width:700px">
        <div class="modal-header">
            <span class="modal-title">
                <i class="ti ti-pill" style="color:var(--primary);margin-right:8px"></i>Nova Prescrição
            </span>
            <button class="btn btn-outline btn-icon" onclick="closeModal('modal-nova')"><i class="ti ti-x"></i></button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="nova">
            <div class="modal-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Internamento (Paciente) *</label>
                        <select name="id_internamento" class="form-control" required>
                            <option value="">— Selecionar —</option>
                            <?php foreach ($internamentos_ativos as $ia): ?>
                                <option value="<?= (int)$ia['id_internamento'] ?>" <?= $filtro_int == $ia['id_internamento'] ? 'selected' : '' ?>>
                                    #<?= (int)$ia['id_internamento'] ?> — <?= htmlspecialchars($ia['paciente']) ?> (<?= htmlspecialchars($ia['num_utente']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Médico Prescritor *</label>
                        <select name="id_profissional" class="form-control" required>
                            <option value="">— Selecionar —</option>
                            <?php foreach ($profissionais as $pf): ?>
                                <option value="<?= (int)$pf['id_profissional'] ?>"><?= htmlspecialchars($pf['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Medicamento *</label>
                        <select name="id_medicacao" class="form-control" required>
                            <option value="">— Selecionar —</option>
                            <?php foreach ($medicacoes as $med): ?>
                                <option value="<?= (int)$med['id_medicacao'] ?>"><?= htmlspecialchars($med['nome']) ?> — <?= htmlspecialchars($med['dosagem']) ?> (<?= htmlspecialchars($med['classe']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dose *</label>
                        <input type="text" name="dose" class="form-control" required placeholder="Ex: 400mg, 10mg">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Via de Administração *</label>
                        <select name="via" class="form-control" required>
                            <option value="oral">Oral</option>
                            <option value="iv">IV (Intravenosa)</option>
                            <option value="im">IM (Intramuscular)</option>
                            <option value="sc">SC (Subcutânea)</option>
                            <option value="sublingual">Sublingual</option>
                            <option value="transdermico">Transdérmico</option>
                            <option value="inalado">Inalado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Frequência *</label>
                        <input type="text" name="frequencia" class="form-control" required placeholder="Ex: 8/8h, 12/12h, 1x/dia, SOS">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data de Início *</label>
                        <input type="date" name="data_inicio" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data de Fim</label>
                        <input type="date" name="data_fim" class="form-control">
                    </div>

                    <div class="form-group" style="grid-column:1/-1; margin-top: 8px;">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                            <input type="checkbox" name="prn" id="prn_check" value="1" onchange="togglePrn(this)">
                            <span class="form-label" style="margin:0">Prescrição SOS (PRN — se necessário)</span>
                        </label>
                    </div>
                    <div class="form-group" id="dose_max_group" style="grid-column:1/-1;display:none">
                        <label class="form-label">Dose Máxima Diária (obrigatório se SOS)</label>
                        <input type="text" name="dose_maxima_dia" class="form-control" placeholder="Ex: 10mg/dia">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-nova')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Criar Prescrição</button>
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

    function togglePrn(cb) {
        var group = document.getElementById('dose_max_group');
        if (!group) return;

        group.style.display = cb.checked ? 'block' : 'none';
        var input = group.querySelector('input');
        if (input) {
            input.required = cb.checked;
        }
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