<?php
session_start();

require_once 'includes/db.php';

$pagina_atual  = 'pacientes';
$titulo_pagina = 'Pacientes';

// Obter a função atual da sessão (garante que não dá erro se não estiver definida)
$funcao_atual = $_SESSION['currentFuncao'] ?? '';

// ─────────────────────────────────────────────────────────────
// ELIMINAR PACIENTE (Apenas para a função 'ti')
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'eliminar') {
    if ($funcao_atual !== 'ti') {
        header('Location: pacientes.php?erro=sem_permissao');
        exit;
    }

    try {
        $id_eliminar = (int)$_POST['id_paciente'];

        $stmt = $pdo->prepare("DELETE FROM PACIENTE WHERE id_paciente = ?");
        $stmt->execute([$id_eliminar]);

        header('Location: pacientes.php?ok=delete');
        exit;
    } catch (PDOException $e) {
        die("
            <div style='
                padding:20px;
                font-family:Arial;
                background:#ffe5e5;
                color:#a00000;
                border:1px solid #ffb3b3;
                border-radius:8px;
                margin:20px;
            '>
                <h3>Erro ao eliminar paciente</h3>
                <p>Certifique-se que o paciente não possui internamentos vinculados.</p>
                <p><small>" . htmlspecialchars($e->getMessage()) . "</small></p>
            </div>
        ");
    }
}

// ─────────────────────────────────────────────────────────────
// NOVO PACIENTE
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'novo') {

    try {

        $nome             = trim($_POST['nome']);
        $data_nascimento  = $_POST['data_nascimento'];
        $num_utente       = trim($_POST['num_utente']);
        $contacto         = trim($_POST['contacto'] ?? '');
        $morada           = trim($_POST['morada'] ?? '');

        // Validar nº utente
        if (!preg_match('/^\d{9}$/', $num_utente)) {
            header('Location: pacientes.php?erro=utente_invalido');
            exit;
        }

        // Verificar duplicado
        $check = $pdo->prepare("
            SELECT id_paciente
            FROM PACIENTE
            WHERE num_utente = ?
        ");

        $check->execute([$num_utente]);

        if ($check->fetch()) {
            header('Location: pacientes.php?erro=utente_existente');
            exit;
        }

        // Inserir paciente
        $stmt = $pdo->prepare("
            INSERT INTO PACIENTE
            (nome, data_nascimento, num_utente, contacto, morada)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $nome,
            $data_nascimento,
            $num_utente,
            $contacto,
            $morada
        ]);

        header('Location: pacientes.php?ok=1');
        exit;
    } catch (PDOException $e) {

        die("
            <div style='
                padding:20px;
                font-family:Arial;
                background:#ffe5e5;
                color:#a00000;
                border:1px solid #ffb3b3;
                border-radius:8px;
                margin:20px;
            '>
                <h3>Erro ao criar paciente</h3>
                <p>" . htmlspecialchars($e->getMessage()) . "</p>
            </div>
        ");
    }
}

// ─────────────────────────────────────────────────────────────
// EDITAR PACIENTE
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'editar') {

    try {

        $stmt = $pdo->prepare("
            UPDATE PACIENTE
            SET nome = ?,
                data_nascimento = ?,
                contacto = ?,
                morada = ?
            WHERE id_paciente = ?
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
    } catch (PDOException $e) {

        die("
            <div style='
                padding:20px;
                font-family:Arial;
                background:#ffe5e5;
                color:#a00000;
                border:1px solid #ffb3b3;
                border-radius:8px;
                margin:20px;
            '>
                <h3>Erro ao atualizar paciente</h3>
                <p>" . htmlspecialchars($e->getMessage()) . "</p>
            </div>
        ");
    }
}

// ─────────────────────────────────────────────────────────────
// PESQUISA
// ─────────────────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');

$params = [];
$where  = '1=1';

if ($search) {

    $where = '
        (
            nome LIKE ?
            OR num_utente LIKE ?
            OR contacto LIKE ?
        )
    ';

    $params = [
        "%$search%",
        "%$search%",
        "%$search%"
    ];
}

$rows = $pdo->prepare("
    SELECT
        p.*,

        (
            SELECT COUNT(*)
            FROM INTERNAMENTO i
            WHERE i.id_paciente = p.id_paciente
        ) AS total_internamentos,

        (
            SELECT COUNT(*)
            FROM INTERNAMENTO i
            WHERE i.id_paciente = p.id_paciente
            AND i.data_alta IS NULL
        ) AS internamento_ativo

    FROM PACIENTE p

    WHERE $where

    ORDER BY p.nome

    LIMIT 100
");

$rows->execute($params);

$rows = $rows->fetchAll();

// ─────────────────────────────────────────────────────────────
// DADOS PARA EDIÇÃO
// ─────────────────────────────────────────────────────────────
$edit_pac = null;

if (isset($_GET['edit'])) {

    $s = $pdo->prepare("
        SELECT *
        FROM PACIENTE
        WHERE id_paciente = ?
    ");

    $s->execute([
        (int)$_GET['edit']
    ]);

    $edit_pac = $s->fetch();
}

require_once 'includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>

    <div class="alert alert-success mb-4">
        <i class="ti ti-circle-check"></i>

        <?php
        if ($_GET['ok'] === 'edit') {
            echo 'Paciente atualizado com sucesso.';
        } elseif ($_GET['ok'] === 'delete') {
            echo 'Paciente eliminado com sucesso.';
        } else {
            echo 'Paciente criado com sucesso.';
        }
        ?>
    </div>

<?php endif; ?>


<?php if (isset($_GET['erro']) && $_GET['erro'] === 'utente_existente'): ?>

    <div class="alert alert-danger mb-4">
        <i class="ti ti-alert-circle"></i>
        Já existe um paciente com esse número de utente.
    </div>

<?php endif; ?>


<?php if (isset($_GET['erro']) && $_GET['erro'] === 'utente_invalido'): ?>

    <div class="alert alert-danger mb-4">
        <i class="ti ti-alert-circle"></i>
        O número de utente deve ter exatamente 9 dígitos.
    </div>

<?php endif; ?>

<?php if (isset($_GET['erro']) && $_GET['erro'] === 'sem_permissao'): ?>

    <div class="alert alert-danger mb-4">
        <i class="ti ti-alert-circle"></i>
        Erro: Não tem permissões para eliminar registos.
    </div>

<?php endif; ?>

<div class="flex items-center justify-between mb-4">

    <form method="get" style="display:flex;gap:8px;align-items:center">

        <div class="search-bar">
            <i class="ti ti-search"></i>

            <input
                type="text"
                name="q"
                placeholder="Pesquisar nome ou nº utente…"
                value="<?= htmlspecialchars($search) ?>">
        </div>

        <button class="btn btn-outline btn-sm" type="submit">
            Pesquisar
        </button>

        <?php if ($search): ?>

            <a href="pacientes.php" class="btn btn-outline btn-sm">
                <i class="ti ti-x"></i>
            </a>

        <?php endif; ?>

    </form>

    <button
        class="btn btn-primary btn-sm"
        onclick="openModal('modal-novo')">
        <i class="ti ti-plus"></i>
        Novo Paciente
    </button>

</div>

<div class="card">

    <div class="card-header">

        <span class="card-title">
            <i class="ti ti-users"></i>
            Pacientes
        </span>

        <span class="text-sm text-muted">
            <?= count($rows) ?> registos
        </span>

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

                        <td class="mono text-muted">
                            <?= $r['id_paciente'] ?>
                        </td>

                        <td>
                            <div class="fw-600">
                                <?= htmlspecialchars($r['nome']) ?>
                            </div>

                            <div class="text-sm text-muted">
                                <?= htmlspecialchars($r['morada'] ?? '—') ?>
                            </div>
                        </td>

                        <td class="mono">
                            <?= $r['num_utente'] ?>
                        </td>

                        <td class="mono text-sm">
                            <?= date('d/m/Y', strtotime($r['data_nascimento'])) ?>
                        </td>

                        <td class="text-sm">
                            <?= htmlspecialchars($r['contacto'] ?? '—') ?>
                        </td>

                        <td>
                            <span class="badge badge-blue">
                                <?= $r['total_internamentos'] ?>
                            </span>
                        </td>

                        <td>

                            <?php if ($r['internamento_ativo'] > 0): ?>

                                <span class="badge badge-red">
                                    Internado
                                </span>

                            <?php else: ?>

                                <span class="badge badge-green">
                                    Ambulatório
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <div class="flex gap-2">

                                <button
                                    type="button"
                                    class="btn btn-outline btn-sm"
                                    onclick='abrirEditar(
                                        <?= $r["id_paciente"] ?>,
                                        <?= json_encode($r["nome"]) ?>,
                                        <?= json_encode($r["data_nascimento"]) ?>,
                                        <?= json_encode($r["contacto"] ?? "") ?>,
                                        <?= json_encode($r["morada"] ?? "") ?>
                                    )'>
                                    <i class="ti ti-pencil"></i>
                                </button>

                                <?php if ($funcao_atual === 'ti'): ?>
                                    <form method="post" action="pacientes.php" style="display:inline;" onsubmit="return confirmarEliminacao(<?= json_encode($r['nome']) ?>);">
                                        <input type="hidden" name="action" value="eliminar">
                                        <input type="hidden" name="id_paciente" value="<?= $r['id_paciente'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm" style="color: var(--red, #dc2626); border-color: var(--red, #fca5a5);" title="Eliminar Paciente">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (empty($rows)): ?>

                    <tr>
                        <td
                            colspan="8"
                            class="text-muted text-sm"
                            style="text-align:center;padding:32px">
                            Nenhum paciente encontrado
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<div class="modal-overlay" id="modal-novo">

    <div class="modal">

        <div class="modal-header">

            <span class="modal-title">
                <i
                    class="ti ti-user-plus"
                    style="color:var(--primary);margin-right:8px"></i>

                Novo Paciente
            </span>

            <button
                class="btn btn-outline btn-icon"
                onclick="closeModal('modal-novo')">
                <i class="ti ti-x"></i>
            </button>

        </div>

        <form method="post">

            <input type="hidden" name="action" value="novo">

            <div class="modal-body">

                <div class="form-grid form-grid-2">

                    <div class="form-group" style="grid-column:1/-1">

                        <label class="form-label">
                            Nome Completo *
                        </label>

                        <input
                            type="text"
                            name="nome"
                            class="form-control"
                            required>

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Nº Utente (SNS) *
                        </label>

                        <input
                            type="text"
                            name="num_utente"
                            class="form-control"
                            required
                            maxlength="9"
                            pattern="\d{9}">

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Data de Nascimento *
                        </label>

                        <input
                            type="date"
                            name="data_nascimento"
                            class="form-control"
                            required>

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Contacto
                        </label>

                        <input
                            type="text"
                            name="contacto"
                            class="form-control">

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Morada
                        </label>

                        <input
                            type="text"
                            name="morada"
                            class="form-control">

                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-outline"
                    onclick="closeModal('modal-novo')">
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="btn btn-primary">
                    <i class="ti ti-check"></i>
                    Criar Paciente
                </button>

            </div>

        </form>

    </div>

</div>

<div class="modal-overlay" id="modal-editar">

    <div class="modal">

        <div class="modal-header">

            <span class="modal-title">
                <i class="ti ti-pencil" style="color:var(--primary);margin-right:8px"></i>
                Editar Paciente
            </span>

            <button
                class="btn btn-outline btn-icon"
                type="button"
                onclick="closeModal('modal-editar')">
                <i class="ti ti-x"></i>
            </button>

        </div>

        <form method="post">

            <input type="hidden" name="action" value="editar">
            <input type="hidden" name="id" id="edit-id">

            <div class="modal-body">

                <div class="form-grid form-grid-2">

                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Nome Completo *</label>
                        <input
                            type="text"
                            name="nome"
                            id="edit-nome"
                            class="form-control"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Data de Nascimento *</label>
                        <input
                            type="date"
                            name="data_nascimento"
                            id="edit-data"
                            class="form-control"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contacto</label>
                        <input
                            type="text"
                            name="contacto"
                            id="edit-contacto"
                            class="form-control">
                    </div>

                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Morada</label>
                        <input
                            type="text"
                            name="morada"
                            id="edit-morada"
                            class="form-control">
                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-outline"
                    onclick="closeModal('modal-editar')">
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="btn btn-primary">
                    <i class="ti ti-check"></i>
                    Guardar Alterações
                </button>

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

    // ABRIR EDITAR
    function abrirEditar(id, nome, data, contacto, morada) {

        document.getElementById('edit-id').value = id;
        document.getElementById('edit-nome').value = nome;
        document.getElementById('edit-data').value = data;
        document.getElementById('edit-contacto').value = contacto;
        document.getElementById('edit-morada').value = morada;

        openModal('modal-editar');
    }

    // CONFIRMAÇÃO DE ELIMINAÇÃO
    function confirmarEliminacao(nomePaciente) {
        return confirm("Tem a certeza que deseja eliminar o paciente '" + nomePaciente + "'? Esta ação não pode ser desfeita.");
    }

    // fechar ao clicar fora
    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.addEventListener('click', e => {
            if (e.target === m) {
                m.classList.remove('open');
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>