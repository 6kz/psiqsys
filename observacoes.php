<?php
session_start();

require_once 'includes/db.php';
$pagina_atual  = 'observacoes';
$titulo_pagina = 'Observações Comportamentais';

$id_internamento = (int)($_GET['internamento'] ?? 0);

// ── Inserir Observação Comportamental ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'nova_obs') {
    $stmt = $pdo->prepare("
        INSERT INTO OBSERVACAO_COMPORTAMENTAL (
            id_internamento, id_profissional, data_hora, humor, 
            sono, discurso, atividade_psicomotora, adesao_terapeutica, notas_clinicas
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        (int)$_POST['id_internamento'],
        (int)$_POST['id_profissional'],
        $_POST['data_hora'],
        $_POST['humor'],
        $_POST['sono'],
        $_POST['discurso'],
        $_POST['atividade_psicomotora'],
        $_POST['adesao_terapeutica'],
        $_POST['notas_clinicas'] ?: null
    ]);

    $redir = $id_internamento ? "observacoes.php?internamento=" . $id_internamento . "&ok=1" : "observacoes.php?ok=1";
    header("Location: " . $redir);
    exit;
}

// ── Query de Listagem ─────────────────────────────────
$where = "";
$params = [];
if ($id_internamento) {
    $where = "WHERE o.id_internamento = ?";
    $params[] = $id_internamento;
}

$stmt = $pdo->prepare("
    SELECT o.*, prof.nome AS profissional, p.nome AS paciente, p.num_utente
    FROM OBSERVACAO_COMPORTAMENTAL o
    JOIN INTERNAMENTO i ON i.id_internamento = o.id_internamento
    JOIN PACIENTE p ON p.id_paciente = i.id_paciente
    JOIN PROFISSIONAL prof ON prof.id_profissional = o.id_profissional
    $where
    ORDER BY o.data_hora DESC
    LIMIT 100
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Listas auxiliares para o modal de inserção
$profissionais = $pdo->query("SELECT id_profissional, nome, funcao FROM PROFISSIONAL ORDER BY nome")->fetchAll();
$internamentos_ativos = $pdo->query("
    SELECT i.id_internamento, p.nome AS paciente 
    FROM INTERNAMENTO i 
    JOIN PACIENTE p ON p.id_paciente = i.id_paciente 
    WHERE i.data_alta IS NULL 
    ORDER BY p.nome
")->fetchAll();

require_once 'includes/header.php';
?>

<div class="content-area">

    <?php if (isset($_GET['ok'])): ?>
        <div class="alert alert-success mb-4">
            <i class="ti ti-circle-check"></i> Observação comportamental registada com sucesso.
        </div>
    <?php endif; ?>

    <div class="flex items-center justify-between mb-4">
        <div>
            <?php if ($id_internamento): ?>
                <a href="internamento_detalhes.php?id=<?= $id_internamento ?>" class="btn btn-outline btn-sm mb-2 d-inline-flex items-center gap-1">
                    <i class="ti ti-arrow-left"></i> Voltar ao Internamento
                </a>
            <?php endif; ?>
        </div>

        <button class="btn btn-primary btn-sm" onclick="openModal('modal-obs')">
            <i class="ti ti-plus"></i> Adicionar Observação
        </button>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="ti ti-clipboard-list"></i>
                Histórico de Observações Comportamentais
                <?= $id_internamento ? "(Filtrado)" : "" ?>
            </span>
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
                        <th>Atividade Psicomotora</th>
                        <th>Adesão Terap.</th>
                        <th>Notas Clínicas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td class="mono text-sm"><?= date('d/m/Y H:i', strtotime($r['data_hora'])) ?></td>
                            <td>
                                <div class="fw-600"><?= htmlspecialchars($r['paciente']) ?></div>
                                <div class="mono text-sm text-muted">ID Int: #<?= $r['id_internamento'] ?></div>
                            </td>
                            <td class="text-sm"><?= htmlspecialchars($r['profissional']) ?></td>
                            <td>
                                <?php
                                $hm = ['eutimico' => 'green', 'deprimido' => 'blue', 'expansivo' => 'amber', 'irritavel' => 'red', 'ansioso' => 'amber', 'labil' => 'red'];
                                echo '<span class="badge badge-' . ($hm[$r['humor']] ?? 'gray') . '">' . $r['humor'] . '</span>';
                                ?>
                            </td>
                            <td class="text-sm"><?= htmlspecialchars($r['sono']) ?></td>
                            <td class="text-sm"><?= htmlspecialchars($r['discurso']) ?></td>
                            <td class="text-sm"><?= htmlspecialchars($r['atividade_psicomotora']) ?></td>
                            <td>
                                <?php
                                $am = ['total' => 'green', 'parcial' => 'amber', 'recusa' => 'red'];
                                echo '<span class="badge badge-' . ($am[$r['adesao_terapeutica']] ?? 'gray') . '">' . $r['adesao_terapeutica'] . '</span>';
                                ?>
                            </td>
                            <td class="text-sm" style="max-width:250px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis" title="<?= htmlspecialchars($r['notas_clinicas'] ?? '') ?>">
                                <?= htmlspecialchars($r['notas_clinicas'] ?? '—') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding:32px" class="text-muted">Nenhuma observação registada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<div class="modal-overlay" id="modal-obs">
    <div class="modal" style="max-width: 720px;">
        <div class="modal-header">
            <span class="modal-title"><i class="ti ti-clipboard-list" style="color:var(--primary); margin-right:8px"></i> Nova Observação Comportamental</span>
            <button class="btn btn-outline btn-icon" onclick="closeModal('modal-obs')"><i class="ti ti-x"></i></button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="nova_obs">
            <div class="modal-body">
                <div class="form-grid form-grid-2">

                    <div class="form-group">
                        <label class="form-label">Internamento / Paciente *</label>
                        <?php if ($id_internamento): ?>
                            <input type="hidden" name="id_internamento" value="<?= $id_internamento ?>">
                            <?php
                            $curr = array_filter($internamentos_ativos, fn($x) => $x['id_internamento'] == $id_internamento);
                            $pNome = $curr ? current($curr)['paciente'] : "Internamento #" . $id_internamento;
                            ?>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($pNome) ?>" disabled>
                        <?php else: ?>
                            <select name="id_internamento" class="form-control" required>
                                <option value="">— Selecionar Internado —</option>
                                <?php foreach ($internamentos_ativos as $int): ?>
                                    <option value="<?= $int['id_internamento'] ?>">#<?= $int['id_internamento'] ?> - <?= htmlspecialchars($int['paciente']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Profissional Registante *</label>
                        <select name="id_profissional" class="form-control" required>
                            <option value="">— Selecionar Profissional —</option>
                            <?php foreach ($profissionais as $prof): ?>
                                <option value="<?= $prof['id_profissional'] ?>"><?= htmlspecialchars($prof['nome']) ?> (<?= htmlspecialchars($prof['especialidade']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Data e Hora *</label>
                        <input type="datetime-local" name="data_hora" class="form-control" required value="<?= date('Y-m-d\TH:i') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Humor Dominante *</label>
                        <select name="humor" class="form-control" required>
                            <option value="eutimico">Eutímico (Estável/Neutro)</option>
                            <option value="deprimido">Deprimido</option>
                            <option value="expansivo">Expansivo</option>
                            <option value="irritavel">Irritável</option>
                            <option value="ansioso">Ansioso</option>
                            <option value="labil">Lábil (Instável)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Padrão de Sono *</label>
                        <input type="text" name="sono" class="form-control" required placeholder="Ex: Dormiu 6h, intermitente, calmo...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tipo de Discurso *</label>
                        <input type="text" name="discurso" class="form-control" required placeholder="Ex: Coerente, lento, acelerado, ruidoso...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Atividade Psicomotora *</label>
                        <input type="text" name="atividade_psicomotora" class="form-control" required placeholder="Ex: Normal, agitado, lentificado...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Adesão Terapêutica *</label>
                        <select name="adesao_terapeutica" class="form-control" required>
                            <option value="total">Adesão Total</option>
                            <option value="parcial">Adesão Parcial / Sob insistência</option>
                            <option value="recusa">Recusa Integral</option>
                        </select>
                    </div>

                </div>

                <div class="form-group mt-4">
                    <label class="form-label">Notas Clínicas e Evolução</label>
                    <textarea name="notas_clinicas" class="form-control" style="height: 100px;" placeholder="Detalhes adicionais sobre o comportamento e estado geral do paciente..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-obs')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Gravar Observação</button>
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