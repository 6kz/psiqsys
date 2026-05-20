<?php
require_once 'includes/db.php';
$pagina_atual  = 'pacientes';
$titulo_pagina = 'Pacientes';

// ── Novo paciente ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'novo') {
    $stmt = $pdo->prepare("
        INSERT INTO PACIENTE (nome, data_nascimento, num_utente, contacto, morada)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        trim($_POST['nome']),
        $_POST['data_nascimento'],
        trim($_POST['num_utente']),
        trim($_POST['contacto'] ?? ''),
        trim($_POST['morada'] ?? ''),
    ]);
    header('Location: pacientes.php?ok=1');
    exit;
}

// ── Editar paciente ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'editar') {
    $stmt = $pdo->prepare("
        UPDATE PACIENTE SET nome=?, data_nascimento=?, contacto=?, morada=?
        WHERE id_paciente=?
    ");
    $stmt->execute([
        trim($_POST['nome']),
        $_POST['data_nascimento'],
        trim($_POST['contacto'] ?? ''),
        trim($_POST['morada'] ?? ''),
        (int)$_POST['id'],
    ]);
    header('Location: pacientes.php?ok=edit');
    exit;
}

// ── Filtro / pesquisa ──────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$params = [];
$where  = '1=1';
if ($search) {
    $where   = '(nome LIKE ? OR num_utente LIKE ? OR contacto LIKE ?)';
    $params  = ["%$search%", "%$search%", "%$search%"];
}

$rows = $pdo->prepare("
    SELECT p.*,
           (SELECT COUNT(*) FROM INTERNAMENTO i WHERE i.id_paciente = p.id_paciente) AS total_internamentos,
           (SELECT COUNT(*) FROM INTERNAMENTO i WHERE i.id_paciente = p.id_paciente AND i.data_alta IS NULL) AS internamento_ativo
    FROM PACIENTE p
    WHERE $where
    ORDER BY p.nome
    LIMIT 100
");
$rows->execute($params);
$rows = $rows->fetchAll();

// Dados para edição via modal
$edit_pac = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM PACIENTE WHERE id_paciente=?");
    $s->execute([(int)$_GET['edit']]);
    $edit_pac = $s->fetch();
}

require_once 'includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success mb-4">
        <i class="ti ti-circle-check"></i>
        <?= $_GET['ok'] === 'edit' ? 'Paciente atualizado com sucesso.' : 'Paciente criado com sucesso.' ?>
    </div>
<?php endif; ?>

<div class="flex items-center justify-between mb-4">
    <form method="get" style="display:flex;gap:8px;align-items:center">
        <div class="search-bar">
            <i class="ti ti-search"></i>
            <input type="text" name="q" placeholder="Pesquisar nome ou nº utente…" value="<?= htmlspecialchars($search) ?>">
        </div>
        <button class="btn btn-outline btn-sm" type="submit">Pesquisar</button>
        <?php if ($search): ?><a href="pacientes.php" class="btn btn-outline btn-sm"><i class="ti ti-x"></i></a><?php endif; ?>
    </form>
    <button class="btn btn-primary btn-sm" onclick="openModal('modal-novo')">
        <i class="ti ti-plus"></i> Novo Paciente
    </button>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="ti ti-users"></i> Pacientes</span>
        <span class="text-sm text-muted"><?= count($rows) ?> registos</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Nº Utente</th>
                    <th>Data Nasc.</th>
                    <th>Contacto</th>
                    <th>Internamentos</th>
                    <th>Estado</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="mono text-muted"><?= $r['id_paciente'] ?></td>
                        <td>
                            <div class="fw-600"><?= htmlspecialchars($r['nome']) ?></div>
                            <div class="text-sm text-muted"><?= htmlspecialchars($r['morada'] ?? '—') ?></div>
                        </td>
                        <td class="mono"><?= $r['num_utente'] ?></td>
                        <td class="mono text-sm"><?= date('d/m/Y', strtotime($r['data_nascimento'])) ?></td>
                        <td class="text-sm"><?= htmlspecialchars($r['contacto'] ?? '—') ?></td>
                        <td><span class="badge badge-blue"><?= $r['total_internamentos'] ?></span></td>
                        <td>
                            <?php if ($r['internamento_ativo'] > 0): ?>
                                <span class="badge badge-red">Internado</span>
                            <?php else: ?>
                                <span class="badge badge-green">Ambulatório</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex gap-2">
                                <a href="pacientes.php?edit=<?= $r['id_paciente'] ?>" class="btn btn-outline btn-sm" title="Editar">
                                    <i class="ti ti-pencil"></i>
                                </a>
                                <?php if ($r['internamento_ativo'] > 0): ?>
                                    <?php
                                    $sid = $pdo->prepare("SELECT id_internamento FROM INTERNAMENTO WHERE id_paciente=? AND data_alta IS NULL LIMIT 1");
                                    $sid->execute([$r['id_paciente']]);
                                    $sid_r = $sid->fetch();
                                    ?>
                                    <a href="internamento_detalhe.php?id=<?= $sid_r['id_internamento'] ?>" class="btn btn-outline btn-sm" title="Ver internamento">
                                        <i class="ti ti-bed"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="8" class="text-muted text-sm" style="text-align:center;padding:32px">Nenhum paciente encontrado</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Novo Paciente -->
<div class="modal-overlay <?= (!$edit_pac && isset($_GET['modal'])) ? 'open' : '' ?>" id="modal-novo">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="ti ti-user-plus" style="color:var(--primary);margin-right:8px"></i>Novo Paciente</span>
            <button class="btn btn-outline btn-icon" onclick="closeModal('modal-novo')"><i class="ti ti-x"></i></button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="novo">
            <div class="modal-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Nome Completo *</label>
                        <input type="text" name="nome" class="form-control" required placeholder="Nome do paciente">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nº Utente (SNS) *</label>
                        <input type="text" name="num_utente" class="form-control" required placeholder="9 dígitos" maxlength="9" pattern="\d{9}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data de Nascimento *</label>
                        <input type="date" name="data_nascimento" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contacto</label>
                        <input type="text" name="contacto" class="form-control" placeholder="Telemóvel ou telefone">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Morada</label>
                        <input type="text" name="morada" class="form-control" placeholder="Cidade, País">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-novo')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Criar Paciente</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Paciente -->
<?php if ($edit_pac): ?>
    <div class="modal-overlay open" id="modal-editar">
        <div class="modal">
            <div class="modal-header">
                <span class="modal-title"><i class="ti ti-pencil" style="color:var(--primary);margin-right:8px"></i>Editar Paciente</span>
                <a href="pacientes.php" class="btn btn-outline btn-icon"><i class="ti ti-x"></i></a>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="editar">
                <input type="hidden" name="id" value="<?= $edit_pac['id_paciente'] ?>">
                <div class="modal-body">
                    <div class="form-grid form-grid-2">
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($edit_pac['nome']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nº Utente</label>
                            <input type="text" class="form-control" value="<?= $edit_pac['num_utente'] ?>" disabled style="background:var(--surface-2)">
                            <span class="text-sm text-muted">Não editável</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Data de Nascimento *</label>
                            <input type="date" name="data_nascimento" class="form-control" required value="<?= $edit_pac['data_nascimento'] ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contacto</label>
                            <input type="text" name="contacto" class="form-control" value="<?= htmlspecialchars($edit_pac['contacto'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Morada</label>
                            <input type="text" name="morada" class="form-control" value="<?= htmlspecialchars($edit_pac['morada'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="pacientes.php" class="btn btn-outline">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Guardar Alterações</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

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