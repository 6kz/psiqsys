<?php
require_once 'includes/db.php';
$pagina_atual  = 'observacoes';
$titulo_pagina = 'Observações Comportamentais';

// ── Novo registo ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'novo') {
    $stmt = $pdo->prepare("
        INSERT INTO OBSERVACAO_COMPORTAMENTAL
          (id_internamento, id_profissional, data_hora, humor, sono, discurso,
           atividade_psicomotora, delirio, alucinacao, adesao_terapeutica, notas_clinicas)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->execute([
        (int)$_POST['id_internamento'],
        (int)$_POST['id_profissional'],
        $_POST['data_hora'],
        $_POST['humor'],
        $_POST['sono'],
        $_POST['discurso'],
        $_POST['atividade_psicomotora'],
        (int)$_POST['delirio'],
        (int)$_POST['alucinacao'],
        $_POST['adesao_terapeutica'],
        trim($_POST['notas_clinicas'] ?? ''),
    ]);
    $redirect_id = (int)$_POST['id_internamento'];
    header("Location: observacoes.php?ok=1" . ($redirect_id ? "&internamento=$redirect_id" : ''));
    exit;
}

// ── Filtros ────────────────────────────────────────────────
$filtro_int = (int)($_GET['internamento'] ?? 0);
$search     = trim($_GET['q'] ?? '');

$where  = '1=1';
$params = [];
if ($filtro_int) {
    $where   .= ' AND o.id_internamento = ?';
    $params[] = $filtro_int;
}
if ($search) {
    $where   .= ' AND (p.nome LIKE ? OR pr.nome LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$rows = $pdo->prepare("
    SELECT o.*, p.nome AS paciente, pr.nome AS profissional,
           i.id_internamento
    FROM OBSERVACAO_COMPORTAMENTAL o
    JOIN INTERNAMENTO i ON i.id_internamento = o.id_internamento
    JOIN PACIENTE p ON p.id_paciente = i.id_paciente
    JOIN PROFISSIONAL pr ON pr.id_profissional = o.id_profissional
    WHERE $where
    ORDER BY o.data_hora DESC
    LIMIT 100
");
$rows->execute($params);
$rows = $rows->fetchAll();

// Para o modal
$internamentos_ativos = $pdo->query("
    SELECT i.id_internamento, p.nome AS paciente, p.num_utente
    FROM INTERNAMENTO i JOIN PACIENTE p ON p.id_paciente = i.id_paciente
    WHERE i.data_alta IS NULL ORDER BY p.nome
")->fetchAll();

$profissionais = $pdo->query("SELECT id_profissional, nome, funcao FROM PROFISSIONAL ORDER BY nome")->fetchAll();

require_once 'includes/header.php';

function humor_badge(string $h): string
{
    $map = ['eutimico' => 'green', 'deprimido' => 'blue', 'expansivo' => 'amber', 'irritavel' => 'red', 'ansioso' => 'amber', 'labil' => 'red'];
    return '<span class="badge badge-' . ($map[$h] ?? 'gray') . '">' . $h . '</span>';
}
function adesao_badge(string $a): string
{
    $map = ['total' => 'green', 'parcial' => 'amber', 'recusa' => 'red'];
    return '<span class="badge badge-' . ($map[$a] ?? 'gray') . '">' . $a . '</span>';
}
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success mb-4">
        <i class="ti ti-circle-check"></i> Observação registada com sucesso.
    </div>
<?php endif; ?>

<div class="flex items-center justify-between mb-4">
    <form method="get" style="display:flex;gap:8px;align-items:center">
        <?php if ($filtro_int): ?><input type="hidden" name="internamento" value="<?= $filtro_int ?>"><?php endif; ?>
        <div class="search-bar">
            <i class="ti ti-search"></i>
            <input type="text" name="q" placeholder="Pesquisar paciente…" value="<?= htmlspecialchars($search) ?>">
        </div>
        <?php if ($filtro_int || $search): ?>
            <a href="observacoes.php" class="btn btn-outline btn-sm"><i class="ti ti-x"></i> Limpar</a>
        <?php endif; ?>
    </form>
    <button class="btn btn-primary btn-sm" onclick="openModal('modal-novo')">
        <i class="ti ti-plus"></i> Nova Observação
    </button>
</div>

<?php if ($filtro_int): ?>
    <?php
    $int_info = $pdo->prepare("SELECT p.nome, i.id_internamento FROM INTERNAMENTO i JOIN PACIENTE p ON p.id_paciente=i.id_paciente WHERE i.id_internamento=?");
    $int_info->execute([$filtro_int]);
    $ii = $int_info->fetch();
    ?>
    <div class="alert alert-warning mb-4">
        <i class="ti ti-filter"></i>
        A filtrar por internamento #<?= $filtro_int ?> — <strong><?= htmlspecialchars($ii['nome'] ?? '') ?></strong>
        &nbsp;<a href="observacoes.php" class="btn btn-outline btn-sm">Ver todas</a>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="ti ti-clipboard-list"></i> Observações Comportamentais</span>
        <span class="text-sm text-muted"><?= count($rows) ?> registos</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Paciente</th>
                    <th>Profissional</th>
                    <th>Humor</th>
                    <th>Sono</th>
                    <th>Discurso</th>
                    <th>Atividade</th>
                    <th>Delírio</th>
                    <th>Alucinação</th>
                    <th>Adesão</th>
                    <th>Notas</th>
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
                        </td>
                        <td class="text-sm"><?= htmlspecialchars($r['profissional']) ?></td>
                        <td><?= humor_badge($r['humor']) ?></td>
                        <td class="text-sm"><?= $r['sono'] ?></td>
                        <td class="text-sm"><?= $r['discurso'] ?></td>
                        <td class="text-sm"><?= $r['atividade_psicomotora'] ?></td>
                        <td>
                            <?php if ($r['delirio'] > 0): ?>
                                <span class="badge badge-<?= $r['delirio'] >= 3 ? 'red' : 'amber' ?>"><?= $r['delirio'] ?>/4</span>
                            <?php else: ?><span class="text-muted text-sm">0</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['alucinacao'] > 0): ?>
                                <span class="badge badge-<?= $r['alucinacao'] >= 3 ? 'red' : 'amber' ?>"><?= $r['alucinacao'] ?>/4</span>
                            <?php else: ?><span class="text-muted text-sm">0</span><?php endif; ?>
                        </td>
                        <td><?= adesao_badge($r['adesao_terapeutica']) ?></td>
                        <td class="text-sm" style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                            title="<?= htmlspecialchars($r['notas_clinicas'] ?? '') ?>">
                            <?= htmlspecialchars(substr($r['notas_clinicas'] ?? '—', 0, 50)) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="11" class="text-muted text-sm" style="text-align:center;padding:32px">Nenhuma observação encontrada</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nova Observação -->
<div class="modal-overlay" id="modal-novo">
    <div class="modal" style="max-width:700px">
        <div class="modal-header">
            <span class="modal-title"><i class="ti ti-clipboard-list" style="color:var(--primary);margin-right:8px"></i>Nova Observação</span>
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
                        <label class="form-label">Humor *</label>
                        <select name="humor" class="form-control" required>
                            <option value="eutimico">Eutímico</option>
                            <option value="deprimido">Deprimido</option>
                            <option value="expansivo">Expansivo</option>
                            <option value="irritavel">Irritável</option>
                            <option value="ansioso">Ansioso</option>
                            <option value="labil">Lábil</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sono *</label>
                        <select name="sono" class="form-control" required>
                            <option value="normal">Normal</option>
                            <option value="insonia">Insónia</option>
                            <option value="hipersonia">Hipersónia</option>
                            <option value="fragmentado">Fragmentado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Discurso *</label>
                        <select name="discurso" class="form-control" required>
                            <option value="normal">Normal</option>
                            <option value="acelerado">Acelerado</option>
                            <option value="lento">Lento</option>
                            <option value="incoerente">Incoerente</option>
                            <option value="mutismo">Mutismo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Atividade Psicomotora *</label>
                        <select name="atividade_psicomotora" class="form-control" required>
                            <option value="normal">Normal</option>
                            <option value="agitado">Agitado</option>
                            <option value="retardado">Retardado</option>
                            <option value="catatónico">Catatónico</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Adesão Terapêutica *</label>
                        <select name="adesao_terapeutica" class="form-control" required>
                            <option value="total">Total</option>
                            <option value="parcial">Parcial</option>
                            <option value="recusa">Recusa</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Delírio (0–4)</label>
                        <input type="number" name="delirio" class="form-control" min="0" max="4" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alucinação (0–4)</label>
                        <input type="number" name="alucinacao" class="form-control" min="0" max="4" value="0">
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Notas Clínicas</label>
                        <textarea name="notas_clinicas" class="form-control" placeholder="Observações relevantes do turno…"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-novo')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Registar Observação</button>
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