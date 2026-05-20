<?php
require_once 'includes/db.php';
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: internamentos.php');
    exit;
}

$i = $pdo->prepare("
    SELECT i.*, p.nome AS paciente, p.num_utente, p.data_nascimento, p.contacto,
           q.numero_quarto, c.numero_cama, s.nome AS servico
    FROM INTERNAMENTO i
    JOIN PACIENTE p ON p.id_paciente = i.id_paciente
    JOIN CAMA c ON c.id_cama = i.id_cama
    JOIN QUARTO q ON q.id_quarto = c.id_quarto
    JOIN SERVICO s ON s.id_servico = q.id_servico
    WHERE i.id_internamento = ?
");
$i->execute([$id]);
$int = $i->fetch();
if (!$int) {
    header('Location: internamentos.php');
    exit;
}

// Diagnósticos
$diags = $pdo->prepare("
    SELECT d.codigo_dsm, d.nome_diagnostico, id2.tipo, id2.data
    FROM INTERNAMENTO_DIAGNOSTICO id2
    JOIN DIAGNOSTICO_DSM d ON d.id_diagnostico = id2.id_diagnostico
    WHERE id2.id_internamento = ?
    ORDER BY FIELD(id2.tipo,'principal','secundario','comorbilidade')
");
$diags->execute([$id]);
$diags = $diags->fetchAll();

// Prescrições ativas
$presc = $pdo->prepare("
    SELECT pr.*, m.nome AS medicamento, m.classe, prof.nome AS medico
    FROM PRESCRICAO pr
    JOIN MEDICACAO m ON m.id_medicacao = pr.id_medicacao
    JOIN PROFISSIONAL prof ON prof.id_profissional = pr.id_profissional
    WHERE pr.id_internamento = ? ORDER BY pr.data_inicio DESC
");
$presc->execute([$id]);
$presc = $presc->fetchAll();

// Observações recentes
$obs = $pdo->prepare("
    SELECT o.*, prof.nome AS profissional
    FROM OBSERVACAO_COMPORTAMENTAL o
    JOIN PROFISSIONAL prof ON prof.id_profissional = o.id_profissional
    WHERE o.id_internamento = ? ORDER BY o.data_hora DESC LIMIT 10
");
$obs->execute([$id]);
$obs = $obs->fetchAll();

// Eventos críticos
$evts = $pdo->prepare("
    SELECT e.*, prof.nome AS profissional
    FROM EVENTO_CRITICO e
    JOIN PROFISSIONAL prof ON prof.id_profissional = e.id_profissional
    WHERE e.id_internamento = ? ORDER BY e.data_hora DESC
");
$evts->execute([$id]);
$evts = $evts->fetchAll();

$pagina_atual  = 'internamentos';
$titulo_pagina = 'Internamento #' . $id . ' — ' . $int['paciente'];
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="internamentos.php" class="btn btn-outline btn-sm"><i class="ti ti-arrow-left"></i> Voltar</a>
</div>

<!-- Cabeçalho do paciente -->
<div class="card mb-4">
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:20px">
        <div>
            <div class="text-sm text-muted">Paciente</div>
            <div class="fw-600" style="font-size:1.1rem"><?= htmlspecialchars($int['paciente']) ?></div>
            <div class="mono text-sm text-muted"><?= $int['num_utente'] ?></div>
        </div>
        <div>
            <div class="text-sm text-muted">Data Nasc.</div>
            <div class="fw-600"><?= date('d/m/Y', strtotime($int['data_nascimento'])) ?></div>
            <div class="text-sm text-muted">Contacto: <?= htmlspecialchars($int['contacto'] ?? '—') ?></div>
        </div>
        <div>
            <div class="text-sm text-muted">Localização</div>
            <div class="fw-600"><?= htmlspecialchars($int['servico']) ?></div>
            <div class="text-sm text-muted">Q<?= $int['numero_quarto'] ?> / C<?= $int['numero_cama'] ?></div>
        </div>
        <div>
            <div class="text-sm text-muted">Admissão</div>
            <div class="fw-600"><?= date('d/m/Y H:i', strtotime($int['data_admissao'])) ?></div>
            <?php if ($int['data_alta']): ?>
                <span class="badge badge-green">Alta: <?= date('d/m/Y', strtotime($int['data_alta'])) ?></span>
            <?php else: ?>
                <span class="badge badge-red">Internado</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

    <!-- Avaliação clínica -->
    <div class="card">
        <div class="card-header"><span class="card-title"><i class="ti ti-stethoscope"></i> Avaliação Clínica</span></div>
        <div class="card-body">
            <table style="width:100%">
                <tr>
                    <td class="text-sm text-muted" style="padding:6px 0;width:45%">Tipo de Episódio</td>
                    <td><span class="badge badge-blue"><?= $int['tipo_episodio'] ?></span></td>
                </tr>
                <tr>
                    <td class="text-sm text-muted" style="padding:6px 0">Risco Suicidário</td>
                    <td><?php $rc = ['nenhum' => 'gray', 'baixo' => 'green', 'moderado' => 'amber', 'elevado' => 'red', 'iminente' => 'red'];
                        echo '<span class="badge badge-' . ($rc[$int['risco_suicidario']] ?? 'gray') . '">' . $int['risco_suicidario'] . '</span>'; ?></td>
                </tr>
                <tr>
                    <td class="text-sm text-muted" style="padding:6px 0">Risco Agressividade</td>
                    <td><?php $ra = ['nenhum' => 'gray', 'baixo' => 'green', 'moderado' => 'amber', 'elevado' => 'red'];
                        echo '<span class="badge badge-' . ($ra[$int['risco_agressividade']] ?? 'gray') . '">' . $int['risco_agressividade'] . '</span>'; ?></td>
                </tr>
                <tr>
                    <td class="text-sm text-muted" style="padding:6px 0">Estado Clínico</td>
                    <td><?php $ec = ['instavel' => 'red', 'estabilizando' => 'amber', 'estavel' => 'green', 'alta_prevista' => 'cyan'];
                        echo '<span class="badge badge-' . ($ec[$int['estado_clinico']] ?? 'gray') . '">' . $int['estado_clinico'] . '</span>'; ?></td>
                </tr>
            </table>
            <div class="mt-4">
                <div class="text-sm text-muted mb-1">Motivo de Internamento</div>
                <p class="text-sm"><?= nl2br(htmlspecialchars($int['motivo_internamento'])) ?></p>
            </div>
        </div>
    </div>

    <!-- Diagnósticos -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="ti ti-clipboard-heart"></i> Diagnósticos</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>DSM</th>
                        <th>Diagnóstico</th>
                        <th>Tipo</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($diags as $d): ?>
                        <tr>
                            <td class="mono text-sm"><?= $d['codigo_dsm'] ?></td>
                            <td class="text-sm"><?= htmlspecialchars($d['nome_diagnostico']) ?></td>
                            <td><?php $dt = ['principal' => 'blue', 'secundario' => 'cyan', 'comorbilidade' => 'amber'];
                                echo '<span class="badge badge-' . ($dt[$d['tipo']] ?? 'gray') . '">' . $d['tipo'] . '</span>'; ?></td>
                            <td class="mono text-sm"><?= date('d/m/Y', strtotime($d['data'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($diags)): ?><tr>
                            <td colspan="4" class="text-muted text-sm" style="padding:16px">Sem diagnósticos</td>
                        </tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Prescrições -->
<div class="card mb-4">
    <div class="card-header">
        <span class="card-title"><i class="ti ti-pill"></i> Prescrições</span>
        <a href="prescricoes.php?internamento=<?= $id ?>" class="btn btn-outline btn-sm">Gerir</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Medicamento</th>
                    <th>Classe</th>
                    <th>Dose</th>
                    <th>Via</th>
                    <th>Frequência</th>
                    <th>PRN</th>
                    <th>Estado</th>
                    <th>Início</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($presc as $p): ?>
                    <tr>
                        <td class="fw-600"><?= htmlspecialchars($p['medicamento']) ?></td>
                        <td class="text-sm text-muted"><?= htmlspecialchars($p['classe']) ?></td>
                        <td class="mono text-sm"><?= $p['dose'] ?></td>
                        <td><span class="badge badge-gray"><?= $p['via'] ?></span></td>
                        <td class="text-sm"><?= $p['frequencia'] ?></td>
                        <td><?= $p['prn'] ? '<span class="badge badge-amber">SOS</span>' : '—' ?></td>
                        <td><?php $es = ['ativa' => 'green', 'suspensa' => 'amber', 'concluida' => 'gray', 'cancelada' => 'red'];
                            echo '<span class="badge badge-' . ($es[$p['estado']] ?? 'gray') . '">' . $p['estado'] . '</span>'; ?></td>
                        <td class="mono text-sm"><?= date('d/m/Y', strtotime($p['data_inicio'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Observações -->
<div class="card mb-4">
    <div class="card-header">
        <span class="card-title"><i class="ti ti-clipboard-list"></i> Observações Comportamentais</span>
        <a href="observacoes.php?internamento=<?= $id ?>" class="btn btn-outline btn-sm">Adicionar</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Profissional</th>
                    <th>Humor</th>
                    <th>Sono</th>
                    <th>Discurso</th>
                    <th>Atividade</th>
                    <th>Adesão</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($obs as $o): ?>
                    <tr>
                        <td class="mono text-sm"><?= date('d/m H:i', strtotime($o['data_hora'])) ?></td>
                        <td class="text-sm"><?= htmlspecialchars($o['profissional']) ?></td>
                        <td><?php $hm = ['eutimico' => 'green', 'deprimido' => 'blue', 'expansivo' => 'amber', 'irritavel' => 'red', 'ansioso' => 'amber', 'labil' => 'red'];
                            echo '<span class="badge badge-' . ($hm[$o['humor']] ?? 'gray') . '">' . $o['humor'] . '</span>'; ?></td>
                        <td class="text-sm"><?= $o['sono'] ?></td>
                        <td class="text-sm"><?= $o['discurso'] ?></td>
                        <td class="text-sm"><?= $o['atividade_psicomotora'] ?></td>
                        <td><?php $am = ['total' => 'green', 'parcial' => 'amber', 'recusa' => 'red'];
                            echo '<span class="badge badge-' . ($am[$o['adesao_terapeutica']] ?? 'gray') . '">' . $o['adesao_terapeutica'] . '</span>'; ?></td>
                        <td class="text-sm" style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                            title="<?= htmlspecialchars($o['notas_clinicas'] ?? '') ?>">
                            <?= htmlspecialchars(substr($o['notas_clinicas'] ?? '—', 0, 60)) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Eventos críticos -->
<?php if (!empty($evts)): ?>
    <div class="card">
        <div class="card-header">
            <span class="card-title" style="color:var(--danger)"><i class="ti ti-alert-triangle"></i> Eventos Críticos</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Tipo</th>
                        <th>Gravidade</th>
                        <th>Profissional</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evts as $e): ?>
                        <tr>
                            <td class="mono text-sm"><?= date('d/m/Y H:i', strtotime($e['data_hora'])) ?></td>
                            <td><span class="badge badge-red"><?= str_replace('_', ' ', $e['tipo_evento']) ?></span></td>
                            <td><?php $gv = ['baixa' => 'green', 'moderada' => 'amber', 'elevada' => 'red', 'critica' => 'red'];
                                echo '<span class="badge badge-' . ($gv[$e['gravidade']] ?? 'gray') . '">' . $e['gravidade'] . '</span>'; ?></td>
                            <td class="text-sm"><?= htmlspecialchars($e['profissional']) ?></td>
                            <td class="text-sm" style="max-width:250px"><?= htmlspecialchars(substr($e['descricao'], 0, 80)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>