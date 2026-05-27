<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$pagina_atual = 'pacientes';
$titulo_pagina = 'Processo Clínico';

$id_paciente = (int)($_GET['id'] ?? 0);
$secao = $_GET['secao'] ?? 'resumo';
$funcao = $_SESSION['currentFuncao'] ?? '';

if ($id_paciente <= 0) {
    header('Location: pacientes.php');
    exit;
}

// obter profissional associado ao utilizador
$id_profissional = null;
if (isset($_SESSION['currentID'])) {
    $stmtProf = $pdo->prepare("SELECT id_profissional FROM UTILIZADOR WHERE id_utilizador = ?");
    $stmtProf->execute([(int)$_SESSION['currentID']]);
    $id_profissional = $stmtProf->fetchColumn();
}

// registar administração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'administrar') {
    if ($funcao !== 'enfermeiro') {
        header("Location: paciente_detalhe.php?id=$id_paciente&secao=medicacao&erro=sem_permissao");
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO ADMINISTRACAO_MEDICACAO
        (id_prescricao, id_internamento, id_profissional, data_hora, administrada, motivo_nao_administracao, efeitos_adversos)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $data_hora_administracao = $_POST['data_hora_administracao'] ?? date('Y-m-d\TH:i');
    $data_hora_mysql = str_replace('T', ' ', $data_hora_administracao) . ':00';
    $stmt->execute([
        (int)$_POST['id_prescricao'],
        (int)$_POST['id_internamento'],
        (int)$id_profissional,
        $data_hora_mysql,
        (int)$_POST['administrada'],
        $_POST['motivo_nao_administracao'] ?: null,
        $_POST['efeitos_adversos'] ?: null
    ]);

    header("Location: paciente_detalhe.php?id=$id_paciente&secao=medicacao&ok=admin");
    exit;
}

// paciente
$stmt = $pdo->prepare("SELECT * FROM PACIENTE WHERE id_paciente = ?");
$stmt->execute([$id_paciente]);
$paciente = $stmt->fetch();

if (!$paciente) {
    header('Location: pacientes.php');
    exit;
}

$idade = date_diff(date_create($paciente['data_nascimento']), date_create('today'))->y;

// internamento atual
$stmt = $pdo->prepare("
    SELECT i.*, c.numero_cama, q.numero_quarto, s.nome AS servico
    FROM INTERNAMENTO i
    JOIN CAMA c ON c.id_cama = i.id_cama
    JOIN QUARTO q ON q.id_quarto = c.id_quarto
    JOIN SERVICO s ON s.id_servico = q.id_servico
    WHERE i.id_paciente = ?
    ORDER BY i.data_admissao DESC
    LIMIT 1
");
$stmt->execute([$id_paciente]);
$internamento = $stmt->fetch();

$id_internamento = $internamento['id_internamento'] ?? 0;

// diagnósticos
$stmt = $pdo->prepare("
    SELECT d.*, ind.tipo, ind.data
    FROM INTERNAMENTO_DIAGNOSTICO ind
    JOIN DIAGNOSTICO_DSM d ON d.id_diagnostico = ind.id_diagnostico
    WHERE ind.id_internamento = ?
    ORDER BY ind.tipo, ind.data DESC
");
$stmt->execute([$id_internamento]);
$diagnosticos = $stmt->fetchAll();

// observações
$stmt = $pdo->prepare("
    SELECT o.*, pr.nome AS profissional, pr.funcao
    FROM OBSERVACAO_COMPORTAMENTAL o
    JOIN PROFISSIONAL pr ON pr.id_profissional = o.id_profissional
    WHERE o.id_internamento = ?
    ORDER BY o.data_hora DESC
");
$stmt->execute([$id_internamento]);
$observacoes = $stmt->fetchAll();

// prescrições
$stmt = $pdo->prepare("
    SELECT 
        pr.*,
        m.nome AS medicamento,
        m.classe,
        m.dosagem,
        prof.nome AS prescrito_por

    FROM PRESCRICAO pr

    JOIN MEDICACAO m 
        ON m.id_medicacao = pr.id_medicacao

    JOIN PROFISSIONAL prof 
        ON prof.id_profissional = pr.id_profissional

    WHERE pr.id_internamento = ?

    ORDER BY
        pr.estado = 'ativa' DESC,
        pr.prn DESC,
        m.nome
");
$stmt->execute([$id_internamento]);
$prescricoes = $stmt->fetchAll();

// administrações
$stmt = $pdo->prepare("
    SELECT a.*, m.nome AS medicamento, pr.dose, pr.via, pr.frequencia, prof.nome AS administrado_por
    FROM ADMINISTRACAO_MEDICACAO a
    JOIN PRESCRICAO pr ON pr.id_prescricao = a.id_prescricao
    JOIN MEDICACAO m ON m.id_medicacao = pr.id_medicacao
    JOIN PROFISSIONAL prof ON prof.id_profissional = a.id_profissional
    WHERE a.id_internamento = ?
    ORDER BY a.data_hora DESC
");
$stmt->execute([$id_internamento]);
$administracoes = $stmt->fetchAll();

// eventos críticos
$stmt = $pdo->prepare("
    SELECT e.*, pr.nome AS profissional
    FROM EVENTO_CRITICO e
    JOIN PROFISSIONAL pr ON pr.id_profissional = e.id_profissional
    WHERE e.id_internamento = ?
    ORDER BY e.data_hora DESC
");
$stmt->execute([$id_internamento]);
$eventos = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="content-area">

    <?php if (isset($_GET['ok'])): ?>
        <div class="alert alert-success">
            <i class="ti ti-circle-check"></i>
            Administração registada com sucesso.
        </div>
    <?php endif; ?>

    <div class="clinical-layout">

        <aside class="clinical-menu">
            <a class="<?= $secao === 'resumo' ? 'active' : '' ?>" href="?id=<?= $id_paciente ?>&secao=resumo">
                Resumo Clínico
            </a>
            <a class="<?= $secao === 'observacoes' ? 'active' : '' ?>" href="?id=<?= $id_paciente ?>&secao=observacoes">
                Observações
            </a>
            <a class="<?= $secao === 'medicacao' ? 'active' : '' ?>" href="?id=<?= $id_paciente ?>&secao=medicacao">
                Medicação
            </a>
            <a class="<?= $secao === 'eventos' ? 'active' : '' ?>" href="?id=<?= $id_paciente ?>&secao=eventos">
                Eventos Críticos
            </a>
            <a class="<?= $secao === 'historico' ? 'active' : '' ?>" href="?id=<?= $id_paciente ?>&secao=historico">
                Histórico
            </a>
        </aside>

        <main class="clinical-main">

            <?php if ($secao === 'resumo'): ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <span class="card-title"><i class="ti ti-id"></i> Identificação</span>
                    </div>

                    <div class="card-body clinical-grid">
                        <div><strong>Nome:</strong><br><?= htmlspecialchars($paciente['nome']) ?></div>
                        <div><strong>Nº Utente:</strong><br><?= htmlspecialchars($paciente['num_utente']) ?></div>
                        <div><strong>Contacto:</strong><br><?= htmlspecialchars($paciente['contacto'] ?? '—') ?></div>
                        <div><strong>Morada:</strong><br><?= htmlspecialchars($paciente['morada'] ?? '—') ?></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="ti ti-stethoscope"></i> Diagnósticos</span>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Código DSM</th>
                                    <th>ICD-10</th>
                                    <th>Diagnóstico</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($diagnosticos as $d): ?>
                                    <tr>
                                        <td><span class="badge badge-blue"><?= htmlspecialchars($d['tipo']) ?></span></td>
                                        <td class="mono"><?= htmlspecialchars($d['codigo_dsm']) ?></td>
                                        <td class="mono"><?= htmlspecialchars($d['codigo_icd10'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($d['nome_diagnostico']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($d['data'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (empty($diagnosticos)): ?>
                                    <tr><td colspan="5" class="text-muted" style="text-align:center;padding:24px">Sem diagnósticos registados.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($secao === 'observacoes'): ?>

    <?php
    $tipo_obs = $_GET['tipo_obs'] ?? 'todas';

    $observacoes_filtradas = array_filter($observacoes, function ($o) use ($tipo_obs) {
        if ($tipo_obs === 'todas') return true;
        if ($tipo_obs === 'medicas') return $o['funcao'] === 'medico';
        if ($tipo_obs === 'enfermagem') return $o['funcao'] === 'enfermeiro';
        if ($tipo_obs === 'psicologia') return $o['funcao'] === 'psicologo';
        return true;
    });
    ?>

    <div class="clinical-section-header mb-4">
        <div>
            <h3>Observações Clínicas</h3>
            <p class="text-muted">Evolução clínica e registos dos profissionais de saúde</p>
        </div>
    </div>

    <div class="clinical-tabs mb-4">
        <a href="?id=<?= $id_paciente ?>&secao=observacoes&tipo_obs=todas"
           class="<?= $tipo_obs === 'todas' ? 'active' : '' ?>">
            Todas
        </a>

        <a href="?id=<?= $id_paciente ?>&secao=observacoes&tipo_obs=medicas"
           class="<?= $tipo_obs === 'medicas' ? 'active' : '' ?>">
            Médicas
        </a>

        <a href="?id=<?= $id_paciente ?>&secao=observacoes&tipo_obs=enfermagem"
           class="<?= $tipo_obs === 'enfermagem' ? 'active' : '' ?>">
            Enfermagem
        </a>

        <a href="?id=<?= $id_paciente ?>&secao=observacoes&tipo_obs=psicologia"
           class="<?= $tipo_obs === 'psicologia' ? 'active' : '' ?>">
            Psicologia
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="ti ti-clipboard-list"></i>
                Registos de Observação
            </span>

            <span class="text-sm text-muted">
                <?= count($observacoes_filtradas) ?> registo(s)
            </span>
        </div>

        <div class="clinical-timeline">
            <?php foreach ($observacoes_filtradas as $o): ?>

                <?php
                $badgeFuncao = 'badge-gray';

                if ($o['funcao'] === 'medico') {
                    $badgeFuncao = 'badge-blue';
                } elseif ($o['funcao'] === 'enfermeiro') {
                    $badgeFuncao = 'badge-green';
                } elseif ($o['funcao'] === 'psicologo') {
                    $badgeFuncao = 'badge-amber';
                }
                ?>

                <div class="timeline-item">
                    <div class="timeline-date">
                        <?= date('d/m/Y', strtotime($o['data_hora'])) ?><br>
                        <?= date('H:i', strtotime($o['data_hora'])) ?><br>
                        <span><?= htmlspecialchars($o['profissional']) ?></span>
                    </div>

                    <div class="timeline-content">
                        <div class="flex gap-2 mb-4" style="flex-wrap:wrap">
                            <span class="badge <?= $badgeFuncao ?>">
                                <?= htmlspecialchars($o['funcao']) ?>
                            </span>

                            <span class="badge badge-blue">
                                Humor: <?= htmlspecialchars($o['humor']) ?>
                            </span>

                            <span class="badge badge-gray">
                                Sono: <?= htmlspecialchars($o['sono']) ?>
                            </span>

                            <span class="badge badge-gray">
                                Discurso: <?= htmlspecialchars($o['discurso']) ?>
                            </span>

                            <span class="badge badge-amber">
                                Adesão: <?= htmlspecialchars($o['adesao_terapeutica']) ?>
                            </span>
                        </div>

                        <div class="clinical-note">
                            <?= nl2br(htmlspecialchars($o['notas_clinicas'] ?? 'Sem notas clínicas.')) ?>
                        </div>

                        <div class="clinical-note-footer">
                            <span>Delírio: <?= (int)$o['delirio'] ?>/4</span>
                            <span>Alucinação: <?= (int)$o['alucinacao'] ?>/4</span>
                            <span>Atividade psicomotora: <?= htmlspecialchars($o['atividade_psicomotora']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($observacoes_filtradas)): ?>
                <div class="empty-state-container">
                    <i class="ti ti-clipboard-off"></i>
                    <p>Sem observações para este filtro.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

            <?php elseif ($secao === 'medicacao'): ?>

<?php
$prescricoes_regulares = array_filter($prescricoes, fn($p) => !$p['prn']);
$prescricoes_sos = array_filter($prescricoes, fn($p) => $p['prn']);
?>

<div class="med-header">
    <div>
        <h3>Plano Terapêutico</h3>
        <div class="text-muted">Prescrições e administrações do internamento atual</div>
    </div>

    <?php if ($funcao === 'medico'): ?>
        <button class="btn btn-primary btn-sm">
            <i class="ti ti-plus"></i> Nova Prescrição
        </button>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-header">
        <span class="card-title">
            <i class="ti ti-pill"></i> Medicação Regular
        </span>
        <span class="text-sm text-muted"><?= count($prescricoes_regulares) ?> prescrição(ões)</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Medicamento</th>
                    <th>Dose</th>
                    <th>Via</th>
                    <th>Frequência</th>
                    <th>Início</th>
                    <th>Estado</th>
                    <th>Prescrito por</th>
                    <?php if ($funcao === 'enfermeiro'): ?>
                        <th>Ação</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($prescricoes_regulares as $p): ?>
                    <tr>
                        <td>
                            <div class="fw-600"><?= htmlspecialchars($p['medicamento']) ?></div>
                            <div class="text-sm text-muted">
                                <?= htmlspecialchars($p['classe']) ?> · <?= htmlspecialchars($p['dosagem']) ?>
                            </div>
                        </td>

                        <td><?= htmlspecialchars($p['dose']) ?></td>
                        <td><?= htmlspecialchars($p['via']) ?></td>

                        <td>
                            <div class="med-frequency">
                                <?= htmlspecialchars($p['frequencia']) ?>
                            </div>
                        </td>

                        <td class="mono text-sm">
                            <?= date('d/m/Y', strtotime($p['data_inicio'])) ?>
                        </td>

                        <td>
                            <span class="badge <?= $p['estado'] === 'ativa' ? 'badge-green' : 'badge-gray' ?>">
                                <?= htmlspecialchars($p['estado']) ?>
                            </span>
                        </td>

                        <td><?= htmlspecialchars($p['prescrito_por']) ?></td>

                        <?php if ($funcao === 'enfermeiro'): ?>
                            <td>
                                <?php if ($p['estado'] === 'ativa'): ?>
                                    <button class="btn-admin-med"
                                        onclick="abrirAdministracao(
                                            <?= (int)$p['id_prescricao'] ?>,
                                            <?= (int)$p['id_internamento'] ?>,
                                            '<?= htmlspecialchars($p['medicamento'], ENT_QUOTES) ?>'
                                        )">
                                        <i class="ti ti-syringe"></i> Administrar
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($prescricoes_regulares)): ?>
                    <tr>
                        <td colspan="<?= $funcao === 'enfermeiro' ? '8' : '7' ?>" class="text-muted" style="text-align:center;padding:24px">
                            Sem medicação regular prescrita.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <span class="card-title">
            <i class="ti ti-alert-circle"></i> Medicação SOS / PRN
        </span>
        <span class="text-sm text-muted"><?= count($prescricoes_sos) ?> prescrição(ões)</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Medicamento</th>
                    <th>Dose</th>
                    <th>Via</th>
                    <th>Indicação</th>
                    <th>Dose Máx./Dia</th>
                    <th>Estado</th>
                    <?php if ($funcao === 'enfermeiro'): ?>
                        <th>Ação</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($prescricoes_sos as $p): ?>
                    <tr>
                        <td>
                            <div class="fw-600"><?= htmlspecialchars($p['medicamento']) ?></div>
                            <div class="text-sm text-muted">
                                <?= htmlspecialchars($p['classe']) ?> · <?= htmlspecialchars($p['dosagem']) ?>
                            </div>
                        </td>

                        <td><?= htmlspecialchars($p['dose']) ?></td>
                        <td><?= htmlspecialchars($p['via']) ?></td>

                        <td>
                            <span class="badge badge-amber">SOS</span>
                            <?= htmlspecialchars($p['frequencia']) ?>
                        </td>

                        <td><?= htmlspecialchars($p['dose_maxima_dia'] ?? '—') ?></td>

                        <td>
                            <span class="badge <?= $p['estado'] === 'ativa' ? 'badge-green' : 'badge-gray' ?>">
                                <?= htmlspecialchars($p['estado']) ?>
                            </span>
                        </td>

                        <?php if ($funcao === 'enfermeiro'): ?>
                            <td>
                                <?php if ($p['estado'] === 'ativa'): ?>
                                    <button class="btn-admin-med"
                                        onclick="abrirAdministracao(
                                            <?= (int)$p['id_prescricao'] ?>,
                                            <?= (int)$p['id_internamento'] ?>,
                                            '<?= htmlspecialchars($p['medicamento'], ENT_QUOTES) ?>'
                                        )">
                                        <i class="ti ti-syringe"></i> Administrar
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($prescricoes_sos)): ?>
                    <tr>
                        <td colspan="<?= $funcao === 'enfermeiro' ? '7' : '6' ?>" class="text-muted" style="text-align:center;padding:24px">
                            Sem medicação SOS prescrita.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="ti ti-history"></i> Histórico de Administrações
        </span>
        <span class="text-sm text-muted"><?= count($administracoes) ?> registo(s)</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Data/Hora real</th>
                    <th>Medicamento</th>
                    <th>Dose / Via</th>
                    <th>Frequência</th>
                    <th>Estado</th>
                    <th>Profissional</th>
                    <th>Observações</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($administracoes as $a): ?>
                    <tr>
                        <td class="mono text-sm">
                            <?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?>
                        </td>

                        <td><?= htmlspecialchars($a['medicamento']) ?></td>

                        <td>
                            <?= htmlspecialchars($a['dose']) ?> /
                            <?= htmlspecialchars($a['via']) ?>
                        </td>

                        <td><?= htmlspecialchars($a['frequencia']) ?></td>

                        <td>
                            <?php if ($a['administrada']): ?>
                                <span class="badge badge-green">Administrada</span>
                            <?php else: ?>
                                <span class="badge badge-red">Não administrada</span>
                            <?php endif; ?>
                        </td>

                        <td><?= htmlspecialchars($a['administrado_por']) ?></td>

                        <td class="text-sm">
                            <?php if (!$a['administrada'] && !empty($a['motivo_nao_administracao'])): ?>
                                <strong>Motivo:</strong>
                                <?= htmlspecialchars($a['motivo_nao_administracao']) ?><br>
                            <?php endif; ?>

                            <?php if (!empty($a['efeitos_adversos'])): ?>
                                <strong>Obs.:</strong>
                                <?= htmlspecialchars($a['efeitos_adversos']) ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($administracoes)): ?>
                    <tr>
                        <td colspan="7" class="text-muted" style="text-align:center;padding:24px">
                            Sem administrações registadas.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
            <?php elseif ($secao === 'eventos'): ?>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="ti ti-alert-triangle"></i> Eventos Críticos</span>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Tipo</th>
                                    <th>Gravidade</th>
                                    <th>Descrição</th>
                                    <th>Intervenção</th>
                                    <th>Profissional</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eventos as $e): ?>
                                    <tr>
                                        <td class="mono"><?= date('d/m/Y H:i', strtotime($e['data_hora'])) ?></td>
                                        <td><?= htmlspecialchars($e['tipo_evento']) ?></td>
                                        <td><span class="badge badge-red"><?= htmlspecialchars($e['gravidade']) ?></span></td>
                                        <td><?= htmlspecialchars($e['descricao']) ?></td>
                                        <td><?= htmlspecialchars($e['intervencao_realizada']) ?></td>
                                        <td><?= htmlspecialchars($e['profissional']) ?></td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (empty($eventos)): ?>
                                    <tr><td colspan="6" class="text-muted" style="text-align:center;padding:24px">Sem eventos críticos.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php else: ?>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="ti ti-history"></i> Histórico</span>
                    </div>
                    <div class="card-body text-muted">
                        Histórico completo do doente a desenvolver.
                    </div>
                </div>

            <?php endif; ?>

        </main>

        <aside class="clinical-side-panel">
            <div class="side-box">
                <strong>Alertas</strong>
                <p>Risco suicidário: <?= htmlspecialchars($internamento['risco_suicidario'] ?? '—') ?></p>
                <p>Risco agressividade: <?= htmlspecialchars($internamento['risco_agressividade'] ?? '—') ?></p>
            </div>

            <div class="side-box">
                <strong>Internamento</strong>
                <?php if ($internamento): ?>
                    <p><?= htmlspecialchars($internamento['tipo_episodio']) ?></p>
                    <p><?= date('d/m/Y H:i', strtotime($internamento['data_admissao'])) ?></p>
                    <p><?= htmlspecialchars($internamento['estado_clinico']) ?></p>
                <?php else: ?>
                    <p>Sem internamento.</p>
                <?php endif; ?>
            </div>
        </aside>

    </div>
</div>

<!-- Modal Administração -->
<div class="modal-overlay" id="modal-admin">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">
                <i class="ti ti-syringe"></i>
                Administração de Medicação
            </div>

            <button class="btn btn-outline btn-sm" onclick="closeModal('modal-admin')">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <form method="post">
            <input type="hidden" name="action" value="administrar">
            <input type="hidden" name="id_prescricao" id="admin-prescricao">
            <input type="hidden" name="id_internamento" id="admin-internamento">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Medicamento</label>
                    <div class="form-group">
                        <label class="form-label">Data/Hora real da administração</label>
                        <input 
                            type="datetime-local" 
                            name="data_hora_administracao" 
                            id="admin-data-hora"
                            class="form-control"
                            required
                        >
                        <small class="text-muted">
                            Indique a hora em que a medicação foi realmente administrada.
                        </small>
                    </div>
                    <input type="text" id="admin-medicamento" class="form-control" disabled>
                </div>

                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select name="administrada" class="form-control">
                        <option value="1">Administrada</option>
                        <option value="0">Não administrada</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Motivo de não administração</label>
                    <textarea name="motivo_nao_administracao" class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Efeitos adversos / observações</label>
                    <textarea name="efeitos_adversos" class="form-control"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-admin')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirAdministracao(idPrescricao, idInternamento, medicamento) {
    document.getElementById('admin-prescricao').value = idPrescricao;
    document.getElementById('admin-internamento').value = idInternamento;
    document.getElementById('admin-medicamento').value = medicamento;
    const agora = new Date();
    agora.setMinutes(agora.getMinutes() - agora.getTimezoneOffset());
    document.getElementById('admin-data-hora').value = agora.toISOString().slice(0, 16);
    openModal('modal-admin');
}
</script>

<?php require_once 'includes/footer.php'; ?>