<?php
session_start();

require_once 'includes/db.php';
require_once 'includes/auditoria.php';

$pagina_atual  = 'pacientes';
$titulo_pagina = 'Pacientes';

$funcao_atual = $_SESSION['currentFuncao'] ?? '';

/* =========================================================
   DESATIVAR PACIENTE (Soft Delete)
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'eliminar') {
    if ($funcao_atual !== 'ti') {
        header('Location: pacientes.php?erro=sem_permissao');
        exit;
    }

    try {
        $id_eliminar = (int)$_POST['id_paciente'];

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE PACIENTE
            SET ativo = 0
            WHERE id_paciente = ?
        ");
        $stmt->execute([$id_eliminar]);

        $stmt2 = $pdo->prepare("
            UPDATE INTERNAMENTO
            SET data_alta = NOW()
            WHERE id_paciente = ?
            AND data_alta IS NULL
        ");
        $stmt2->execute([$id_eliminar]);

        // CORREÇÃO: Ação alterada de 'DELETE' para 'DISABLE' (reflete melhor o UPDATE efetuado)
        registarLog($pdo, 'PACIENTE', 'DISABLE', $id_eliminar, $id_eliminar);

        $pdo->commit();

        header('Location: pacientes.php?ok=delete');
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        die($e->getMessage());
    }
}

/* =========================================================
   NOVO PACIENTE
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'novo') {
    try {
        $nome            = trim($_POST['nome']);
        $data_nascimento = $_POST['data_nascimento'];
        $num_utente      = trim($_POST['num_utente']);
        $contacto        = trim($_POST['contacto'] ?? '');
        $morada          = trim($_POST['morada'] ?? '');

        if (!preg_match('/^\d{9}$/', $num_utente)) {
            header('Location: pacientes.php?erro=utente_invalido');
            exit;
        }

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

        $stmt = $pdo->prepare("
            INSERT INTO PACIENTE (nome, data_nascimento, num_utente, contacto, morada, ativo)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$nome, $data_nascimento, $num_utente, $contacto, $morada]);

        $id_paciente = $pdo->lastInsertId();

        registarLog($pdo, 'PACIENTE', 'INSERT', $id_paciente, $id_paciente);

        header('Location: pacientes.php?ok=1');
        exit;
    } catch (PDOException $e) {
        die($e->getMessage());
    }
}

/* =========================================================
   EDITAR PACIENTE
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'editar') {
    if ($funcao_atual !== 'ti' && $funcao_atual !== 'administrativo') {
        header('Location: pacientes.php?erro=sem_permissao');
        exit;
    }

    try {
        $id = (int)$_POST['id'];
        $nome = trim($_POST['nome']);
        $data_nascimento = $_POST['data_nascimento'];
        $contacto = trim($_POST['contacto'] ?? '');
        $morada = trim($_POST['morada'] ?? '');

        // Se for TI, pode processar a alteração do número de utente
        if ($funcao_atual === 'ti' && isset($_POST['num_utente'])) {
            $num_utente = trim($_POST['num_utente']);

            if (!preg_match('/^\d{9}$/', $num_utente)) {
                header('Location: pacientes.php?erro=utente_invalido');
                exit;
            }

            // Validar se o número de utente pertence a outro paciente ativo/existente
            $check = $pdo->prepare("
                SELECT id_paciente 
                FROM PACIENTE 
                WHERE num_utente = ? AND id_paciente <> ?
            ");
            $check->execute([$num_utente, $id]);

            if ($check->fetch()) {
                header('Location: pacientes.php?erro=utente_existente');
                exit;
            }

            // Atualização incluindo número de utente
            $stmt = $pdo->prepare("
                UPDATE PACIENTE
                SET nome = ?, data_nascimento = ?, num_utente = ?, contacto = ?, morada = ?
                WHERE id_paciente = ?
            ");
            $stmt->execute([$nome, $data_nascimento, $num_utente, $contacto, $morada, $id]);
        } else {
            // Atualização normal para administrativos (não altera número de utente)
            $stmt = $pdo->prepare("
                UPDATE PACIENTE
                SET nome = ?, data_nascimento = ?, contacto = ?, morada = ?
                WHERE id_paciente = ?
            ");
            $stmt->execute([$nome, $data_nascimento, $contacto, $morada, $id]);
        }

        registarLog($pdo, 'PACIENTE', 'UPDATE', $id, $id);

        header('Location: pacientes.php?ok=edit');
        exit;
    } catch (PDOException $e) {
        die($e->getMessage());
    }
}

/* =========================================================
   PESQUISA & LISTAGEM
========================================================= */
$search = trim($_GET['q'] ?? '');
$params = [];

if ($search) {
    // Quando há pesquisa, não filtramos por p.ativo = 1 (traz ativos e inativos)
    $where = '
        p.nome LIKE ?
        OR p.num_utente LIKE ?
        OR p.contacto LIKE ?
    ';
    $params = ["%$search%", "%$search%", "%$search%"];

    // CORREÇÃO: Só regista log se houver uma pesquisa real baseada em inputs
    registarLog($pdo, 'PACIENTE', 'SEARCH: ' . substr($search, 0, 50), null, null);
} else {
    // Listagem padrão sem pesquisa: apenas os ativos
    $where = 'p.ativo = 1';
}

$rows = $pdo->prepare("
    SELECT
        p.*,
        (SELECT COUNT(*) FROM INTERNAMENTO i WHERE i.id_paciente = p.id_paciente) AS total_internamentos,
        (SELECT COUNT(*) FROM INTERNAMENTO i WHERE i.id_paciente = p.id_paciente AND i.data_alta IS NULL) AS internamento_ativo
    FROM PACIENTE p
    WHERE $where
    ORDER BY p.nome
    LIMIT 100
");
$rows->execute($params);
$rows = $rows->fetchAll();

// REMOVIDO: O log genérico de SELECT que executava a cada carregamento de página.

require_once 'includes/header.php';
?>

<div class="content-area">

    <?php if (isset($_GET['ok'])): ?>
        <div class="alert alert-success mb-4" id="sucess-alert">
            <i class="ti ti-circle-check"></i>
            <?php
            if ($_GET['ok'] === 'edit') {
                echo 'Paciente atualizado com sucesso.';
            } elseif ($_GET['ok'] === 'delete') {
                echo 'Paciente desativado com sucesso.';
            } else {
                echo 'Paciente criado com sucesso.';
            }
            ?>
        </div>

        <script>
            // Aguarda 4 segundos (4000ms) para limpar o '?ok=...' do URL
            setTimeout(function() {
                // Remove o parâmetro do URL sem recarregar a página
                const url = new URL(window.location);
                url.searchParams.delete('ok');
                window.history.pushState({}, '', url);

                // Esconde o alerta visualmente com um efeito suave
                const alert = document.getElementById('sucess-alert');
                if (alert) {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            }, 4000);
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger mb-4">
            <i class="ti ti-alert-circle"></i>
            <?php
            if ($_GET['erro'] === 'utente_existente') {
                echo 'Já existe um paciente com esse número de utente.';
            } elseif ($_GET['erro'] === 'utente_invalido') {
                echo 'O número de utente deve ter exatamente 9 dígitos.';
            } elseif ($_GET['erro'] === 'sem_permissao') {
                echo 'Não tem permissões para esta ação.';
            }
            ?>
        </div>
    <?php endif; ?>

    <div class="flex items-center justify-between mb-4">
        <div class="flex gap-3 items-center" style="flex-grow: 1; max-width: 500px;">
            <form method="get" style="display:flex;gap:8px;align-items:center;width:100%">
                <div class="search-bar" style="flex-grow:1">
                    <i class="ti ti-search"></i>
                    <input type="text" name="q" placeholder="Pesquisar nome ou nº utente…" value="<?= htmlspecialchars($search) ?>">
                </div>
                <?php if ($search): ?>
                    <a href="pacientes.php" class="btn btn-outline btn-icon" style="height:38px;width:38px;display:flex;align-items:center;justify-content:center">
                        <i class="ti ti-x"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

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
                        <tr style="<?= ((int)$r['ativo'] === 0) ? 'opacity: 0.65; background-color: var(--card-bg-dim, #fafafa);' : '' ?>">
                            <td class="mono text-muted"><?= (int)$r['id_paciente'] ?></td>
                            <td>
                                <div class="fw-600">
                                    <?= htmlspecialchars($r['nome']) ?>
                                    <?php if ((int)$r['ativo'] === 0): ?>
                                        <span class="text-xs text-muted fw-400" style="margin-left: 6px;">(Inativo)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm text-muted"><?= htmlspecialchars($r['morada'] ?? '—') ?></div>
                            </td>
                            <td class="mono"><?= htmlspecialchars($r['num_utente']) ?></td>
                            <td class="mono text-sm"><?= date('d/m/Y', strtotime($r['data_nascimento'])) ?></td>
                            <td class="text-sm"><?= htmlspecialchars($r['contacto'] ?? '—') ?></td>
                            <td>
                                <span class="badge badge-blue"><?= (int)$r['total_internamentos'] ?></span>
                            </td>
                            <td>
                                <?php if ((int)$r['ativo'] === 0): ?>
                                    <span class="badge text-muted" style="background: var(--border)">Inativo</span>
                                <?php elseif ($r['internamento_ativo'] > 0): ?>
                                    <span class="badge badge-red">Internado</span>
                                <?php else: ?>
                                    <span class="badge badge-green">Ambulatório</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <?php if ($funcao_atual === 'ti' || $funcao_atual === 'administrativo'): ?>
                                        <button type="button" class="btn btn-outline btn-icon btn-sm"
                                            onclick='abrirEditar(
                                                    <?= (int)$r["id_paciente"] ?>,
                                                    <?= json_encode($r["nome"]) ?>,
                                                    <?= json_encode($r["num_utente"]) ?>,
                                                    <?= json_encode($r["data_nascimento"]) ?>,
                                                    <?= json_encode($r["contacto"] ?? "") ?>,
                                                    <?= json_encode($r["morada"] ?? "") ?>
                                                )'>
                                            <i class="ti ti-pencil"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($funcao_atual === 'ti' && (int)$r['ativo'] === 1): ?>
                                        <form method="post" action="pacientes.php" style="display:inline"
                                            onsubmit="return confirmarEliminacao(<?= json_encode($r['nome']) ?>)">
                                            <input type="hidden" name="action" value="eliminar">
                                            <input type="hidden" name="id_paciente" value="<?= (int)$r['id_paciente'] ?>">
                                            <button type="submit" class="btn btn-outline btn-icon btn-sm">
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
                            <td colspan="8" class="text-muted text-sm" style="text-align:center;padding:32px">
                                <i class="ti ti-mood-empty" style="font-size:32px;display:block;margin-bottom:8px;color:var(--text-muted)"></i>
                                Nenhum paciente encontrado
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-novo" style="display: none;">
    <div class="modal" style="max-width:540px">
        <div class="modal-header">
            <span class="modal-title">
                <i class="ti ti-plus" style="margin-right:8px"></i>Novo Paciente
            </span>
            <button class="btn btn-outline btn-icon" onclick="closeModal('modal-novo')"><i class="ti ti-x"></i></button>
        </div>
        <form method="post" action="pacientes.php">
            <input type="hidden" name="action" value="novo">
            <div class="modal-body">
                <div class="form-grid form-grid-1">
                    <div class="form-group">
                        <label class="form-label">Nome Completo *</label>
                        <input type="text" name="nome" class="form-control" required placeholder="Ex: Maria Silva">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nº de Utente (SNS) *</label>
                        <input type="text" name="num_utente" class="form-control" required pattern="\d{9}" title="O número de utente deve ter exatamente 9 dígitos" placeholder="Ex: 123456789">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Data de Nascimento *</label>
                        <input type="date" name="data_nascimento" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contacto Telefónico</label>
                        <input type="text" name="contacto" class="form-control" placeholder="Ex: 912345678">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Morada</label>
                        <textarea name="morada" class="form-control" placeholder="Ex: Rua Principal, nº 10" style="min-height:70px"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-novo')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Criar Paciente</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modal-editar" style="display: none;">
    <div class="modal" style="max-width:540px">
        <div class="modal-header">
            <span class="modal-title">
                <i class="ti ti-pencil" style="margin-right:8px"></i>Editar Paciente
            </span>
            <button class="btn btn-outline btn-icon" onclick="closeModal('modal-editar')"><i class="ti ti-x"></i></button>
        </div>
        <form method="post" action="pacientes.php">
            <input type="hidden" name="action" value="editar">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-body">
                <div class="form-grid form-grid-1">
                    <div class="form-group">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" id="edit-nome" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nº de Utente (SNS)</label>
                        <input type="text" name="num_utente" id="edit-utente" class="form-control" required pattern="\d{9}" title="O número de utente deve ter exatamente 9 dígitos" <?= ($funcao_atual === 'ti') ? '' : 'disabled style="background:var(--border);color:var(--text-muted);cursor:not-allowed;"' ?>>
                        <?php if ($funcao_atual !== 'ti'): ?>
                            <small class="text-muted text-sm" style="display:block;margin-top:4px;">Apenas utilizadores de TI podem editar este campo.</small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Data de Nascimento</label>
                        <input type="date" name="data_nascimento" id="edit-data" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contacto</label>
                        <input type="text" name="contacto" id="edit-contacto" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Morada</label>
                        <textarea name="morada" id="edit-morada" class="form-control" style="min-height:70px"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-editar')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Guardar Alterações</button>
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

    function abrirEditar(id, nome, numUtente, data, contacto, morada) {
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-nome').value = nome;

        const elUtente = document.getElementById('edit-utente');
        if (elUtente) elUtente.value = numUtente;

        document.getElementById('edit-data').value = data;
        document.getElementById('edit-contacto').value = contacto;
        document.getElementById('edit-morada').value = morada;
        openModal('modal-editar');
    }

    function confirmarEliminacao(nomePaciente) {
        return confirm("Tem a certeza que deseja desativar o paciente '" + nomePaciente + "'? Todos os seus internamentos ativos serão encerrados.");
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