<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/logger.php';

// Determinar se o utilizador logado é administrativo
// (Ajusta 'administrativo' e 'user_role' se a tua chave na sessão for diferente)
$is_administrativo = (isset($_SESSION['currentFuncao']) && $_SESSION['currentFuncao'] === 'administrativo');

$pagina_atual  = 'internamentos';
$titulo_pagina = 'Internamentos';

// ── Dar alta ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'alta') {
        $stmt = $pdo->prepare("UPDATE INTERNAMENTO SET data_alta=NOW(), estado_clinico='alta_prevista' WHERE id_internamento=?");
        $stmt->execute([(int)$_POST['id']]);
        header('Location: internamentos?ok=alta');
        exit;
    }
    if ($_POST['action'] === 'novo') {

        // ── VERIFICAR SE A CAMA ESTÁ DISPONÍVEL ───────────────
        $checkCama = $pdo->prepare("
            SELECT estado
            FROM CAMA
            WHERE id_cama = ?
        ");
        $checkCama->execute([(int)$_POST['id_cama']]);
        $cama = $checkCama->fetch();

        if (!$cama || $cama['estado'] !== 'disponivel') {
            header('Location: internamentos?erro=cama_indisponivel');
            exit;
        }

        // ── INSERIR INTERNAMENTO ───────────────────────────────
        $stmt = $pdo->prepare("
            INSERT INTO INTERNAMENTO (
                id_paciente,id_cama,data_admissao,motivo_internamento,
                tipo_episodio,risco_suicidario,risco_agressividade,estado_clinico
            )
            VALUES (?,?,?,?,?,?,?,?)
        ");

        try {
            $stmt->execute([
                (int)$_POST['id_paciente'],
                (int)$_POST['id_cama'],
                $_POST['data_admissao'],
                $_POST['motivo'],
                $_POST['tipo_episodio'],
                $_POST['risco_suicidario'],
                $_POST['risco_agressividade'],
                $_POST['estado_clinico'],
            ]);

            // marca cama ocupada
            $pdo->prepare("UPDATE CAMA SET estado='ocupada' WHERE id_cama=?")
                ->execute([(int)$_POST['id_cama']]);

            header('Location: internamentos?ok=novo');
            exit;
        } catch (PDOException $e) {
            // erro trigger cama ocupada
            if ($e->getCode() == '45000') {
                header('Location: internamentos?erro=cama_ocupada');
                exit;
            }
            // outros erros
            header('Location: internamentos?erro=geral');
            exit;
        }
    }
}

// ── Filtros ────────────────────────────────────────────────
$search  = trim($_GET['q'] ?? '');
$filtro  = $_GET['filtro'] ?? 'ativos';

$where = $filtro === 'todos' ? '' : 'AND i.data_alta IS NULL';
$params = [];
if ($search) {
    $where .= ' AND (p.nome LIKE ? OR p.num_utente LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("
    SELECT i.*, p.nome AS paciente, p.num_utente,
           q.numero_quarto, c.numero_cama, s.nome AS servico
    FROM INTERNAMENTO i
    JOIN PACIENTE p ON p.id_paciente = i.id_paciente
    JOIN CAMA c ON c.id_cama = i.id_cama
    JOIN QUARTO q ON q.id_quarto = c.id_quarto
    JOIN SERVICO s ON s.id_servico = q.id_servico
    WHERE 1=1 $where
    ORDER BY i.data_admissao DESC
    LIMIT 50
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Para o modal de novo internamento
$pacientes = $pdo->query("SELECT id_paciente,nome,num_utente FROM PACIENTE ORDER BY nome")->fetchAll();
$camas_disp = $pdo->query("SELECT c.id_cama, q.numero_quarto, c.numero_cama, s.nome AS servico
    FROM CAMA c JOIN QUARTO q ON q.id_quarto=c.id_quarto JOIN SERVICO s ON s.id_servico=q.id_servico
    WHERE c.estado='disponivel' ORDER BY s.nome, q.numero_quarto, c.numero_cama")->fetchAll();

require_once 'includes/header.php';
?>

<div class="content-area">

    <?php if (isset($_GET['ok'])): ?>
        <div class="alert alert-success mb-4">
            <i class="ti ti-circle-check"></i>
            <?= $_GET['ok'] === 'alta' ? 'Alta clínica registada com sucesso.' : 'Internamento criado com sucesso.' ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger mb-4">
            <i class="ti ti-alert-circle"></i>
            <?php
            switch ($_GET['erro']) {
                case 'cama_ocupada':
                    echo 'A cama selecionada já se encontra ocupada.';
                    break;
                case 'cama_indisponivel':
                    echo 'A cama selecionada não está disponível.';
                    break;
                default:
                    echo 'Ocorreu um erro ao criar o internamento.';
            }
            ?>
        </div>
    <?php endif; ?>

    <div class="flex items-center justify-between mb-4">
        <div class="flex gap-2">
            <a href="?filtro=ativos<?= $search ? '&q=' . urlencode($search) : '' ?>"
                class="btn <?= $filtro !== 'todos' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Ativos</a>
            <a href="?filtro=todos<?= $search ? '&q=' . urlencode($search) : '' ?>"
                class="btn <?= $filtro === 'todos' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Todos</a>
        </div>
        <div class="flex gap-3 items-center">
            <form method="get" style="display:flex;gap:8px;align-items:center">
                <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
                <div class="search-bar">
                    <i class="ti ti-search"></i>
                    <input type="text" name="q" placeholder="Pesquisar paciente…" value="<?= htmlspecialchars($search) ?>">
                </div>
            </form>
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-novo')">
                <i class="ti ti-plus"></i> Novo Internamento
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="ti ti-bed"></i> Internamentos <?= $filtro === 'todos' ? '(todos)' : 'Ativos' ?></span>
            <span class="text-sm text-muted"><?= count($rows) ?> registos</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Paciente</th>
                        <th>Serviço / Cama</th>
                        <th>Admissão</th>
                        <th>Episódio</th>
                        <th>Risco Suic.</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td class="mono text-muted"><?= $r['id_internamento'] ?></td>
                            <td>
                                <div class="fw-600"><?= htmlspecialchars($r['paciente']) ?></div>
                                <div class="mono text-sm text-muted"><?= $r['num_utente'] ?></div>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($r['servico']) ?></div>
                                <div class="text-sm text-muted">Q<?= $r['numero_quarto'] ?> / C<?= $r['numero_cama'] ?></div>
                            </td>
                            <td class="mono text-sm"><?= date('d/m/Y H:i', strtotime($r['data_admissao'])) ?></td>

                            <td>
                                <?php if ($is_administrativo): ?>
                                    <span class="confidential-blur">Oculto</span>
                                <?php else: ?>
                                    <div class="revealable-wrapper" onclick="toggleSensitiveData(this)">
                                        <span class="confidential-placeholder">Oculto</span>
                                        <span class="badge badge-blue confidential-content" style="display: none;"><?= htmlspecialchars($r['tipo_episodio']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($is_administrativo): ?>
                                    <span class="confidential-blur">Oculto</span>
                                <?php else: ?>
                                    <div class="revealable-wrapper" onclick="toggleSensitiveData(this)">
                                        <span class="confidential-placeholder">Oculto</span>
                                        <div class="confidential-content" style="display: none;">
                                            <?php
                                            $rc = ['nenhum' => 'gray', 'baixo' => 'green', 'moderado' => 'amber', 'elevado' => 'red', 'iminente' => 'red'];
                                            echo '<span class="badge badge-' . ($rc[$r['risco_suicidario']] ?? 'gray') . '">' . htmlspecialchars($r['risco_suicidario']) . '</span>';
                                            ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($is_administrativo): ?>
                                    <span class="confidential-blur">Oculto</span>
                                <?php else: ?>
                                    <div class="revealable-wrapper" onclick="toggleSensitiveData(this)">
                                        <span class="confidential-placeholder">Oculto</span>
                                        <div class="confidential-content" style="display: none;">
                                            <?php
                                            $ec = ['instavel' => 'red', 'estabilizando' => 'amber', 'estavel' => 'green', 'alta_prevista' => 'cyan'];
                                            echo '<span class="badge badge-' . ($ec[$r['estado_clinico']] ?? 'gray') . '">' . htmlspecialchars($r['estado_clinico']) . '</span>';
                                            ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="flex gap-2">
                                    <?php if (!$is_administrativo): ?>
                                        <a href="internamento_detalhes?id=<?= $r['id_internamento'] ?>" class="btn btn-outline btn-sm">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (is_null($r['data_alta'])): ?>
                                        <form method="post" onsubmit="return confirm('Confirmar alta clínica?')">
                                            <input type="hidden" name="action" value="alta">
                                            <input type="hidden" name="id" value="<?= $r['id_internamento'] ?>">
                                            <button class="btn btn-success btn-sm"><i class="ti ti-logout"></i> Alta</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-sm text-muted mono"><?= date('d/m/Y', strtotime($r['data_alta'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;padding:32px" class="text-muted">Nenhum registo encontrado</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-novo">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="ti ti-bed" style="color:var(--primary);margin-right:8px"></i>Novo Internamento</span>
            <button class="btn btn-outline btn-icon" onclick="closeModal('modal-novo')"><i class="ti ti-x"></i></button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="novo">
            <div class="modal-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Paciente *</label>
                        <select name="id_paciente" class="form-control" required>
                            <option value="">— Selecionar —</option>
                            <?php foreach ($pacientes as $pac): ?>
                                <option value="<?= $pac['id_paciente'] ?>"><?= htmlspecialchars($pac['nome']) ?> (<?= $pac['num_utente'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cama Disponível *</label>
                        <select name="id_cama" class="form-control" required>
                            <option value="">— Selecionar —</option>
                            <?php foreach ($camas_disp as $c): ?>
                                <option value="<?= $c['id_cama'] ?>"><?= htmlspecialchars($c['servico']) ?> — Q<?= $c['numero_quarto'] ?>/C<?= $c['numero_cama'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data Admissão *</label>
                        <input type="datetime-local" name="data_admissao" class="form-control" required value="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo de Episódio *</label>
                        <select name="tipo_episodio" class="form-control" required>
                            <option value="maníaco">Maníaco</option>
                            <option value="depressivo">Depressivo</option>
                            <option value="misto">Misto</option>
                            <option value="hipomaníaco">Hipomaníaco</option>
                            <option value="nao_especificado">Não especificado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Risco Suicidário *</label>
                        <select name="risco_suicidario" class="form-control" required>
                            <option value="nenhum">Nenhum</option>
                            <option value="baixo">Baixo</option>
                            <option value="moderado">Moderado</option>
                            <option value="elevado">Elevado</option>
                            <option value="iminente">Iminente</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Risco Agressividade *</label>
                        <select name="risco_agressividade" class="form-control" required>
                            <option value="nenhum">Nenhum</option>
                            <option value="baixo">Baixo</option>
                            <option value="moderado">Moderado</option>
                            <option value="elevado">Elevado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estado Clínico *</label>
                        <select name="estado_clinico" class="form-control" required>
                            <option value="instavel">Instável</option>
                            <option value="estabilizando">Estabilizando</option>
                            <option value="estavel">Estável</option>
                            <option value="alta_prevista">Alta Prevista</option>
                        </select>
                    </div>
                </div>
                <div class="form-group mt-4">
                    <label class="form-label">Motivo de Internamento *</label>
                    <textarea name="motivo" class="form-control" required placeholder="Descreva o motivo do internamento…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-novo')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Criar Internamento</button>
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

    // Função interativa para alternar a exibição de dados sensíveis (On/Off)
    function toggleSensitiveData(element) {
        const placeholder = element.querySelector('.confidential-placeholder');
        const content = element.querySelector('.confidential-content');

        if (content.style.display === 'none') {
            content.style.display = 'inline-flex';
            placeholder.style.display = 'none';
        } else {
            content.style.display = 'none';
            placeholder.style.display = 'inline-flex';
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>