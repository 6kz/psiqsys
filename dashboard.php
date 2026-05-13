<?php
require_once 'includes/db.php';
$pagina_atual  = 'dashboard';
$titulo_pagina = 'Dashboard';
require_once 'includes/header.php';

// ── Stats ──────────────────────────────────────────────────
$internamentos_ativos = $pdo->query("SELECT COUNT(*) FROM INTERNAMENTO WHERE data_alta IS NULL")->fetchColumn();
$total_pacientes      = $pdo->query("SELECT COUNT(*) FROM PACIENTE")->fetchColumn();
$camas_disponiveis    = $pdo->query("SELECT COUNT(*) FROM CAMA WHERE estado='disponivel'")->fetchColumn();
$camas_ocupadas       = $pdo->query("SELECT COUNT(*) FROM CAMA WHERE estado='ocupada'")->fetchColumn();
$eventos_hoje         = $pdo->query("SELECT COUNT(*) FROM EVENTO_CRITICO WHERE DATE(data_hora)=CURDATE()")->fetchColumn();
$prescricoes_ativas   = $pdo->query("SELECT COUNT(*) FROM PRESCRICAO WHERE estado='ativa'")->fetchColumn();

// ── Internamentos ativos ──────────────────────────────────
$stmt = $pdo->query("SELECT * FROM vw_internamentos_ativos ORDER BY data_admissao DESC LIMIT 10");
$ativos = $stmt->fetchAll();

// ── Risco suicidário elevado/iminente ─────────────────────
$riscos = $pdo->query("
    SELECT p.nome, i.risco_suicidario, i.risco_agressividade, i.tipo_episodio,
           q.numero_quarto, c.numero_cama
    FROM INTERNAMENTO i
    JOIN PACIENTE p ON p.id_paciente = i.id_paciente
    JOIN CAMA c ON c.id_cama = i.id_cama
    JOIN QUARTO q ON q.id_quarto = c.id_quarto
    WHERE i.data_alta IS NULL
      AND i.risco_suicidario IN ('elevado','iminente')
    ORDER BY FIELD(i.risco_suicidario,'iminente','elevado') DESC
")->fetchAll();

// ── Últimas observações ────────────────────────────────────
$obs = $pdo->query("
    SELECT o.data_hora, p.nome AS paciente, pr.nome AS profissional,
           o.humor, o.adesao_terapeutica, o.notas_clinicas
    FROM OBSERVACAO_COMPORTAMENTAL o
    JOIN INTERNAMENTO i ON i.id_internamento = o.id_internamento
    JOIN PACIENTE p ON p.id_paciente = i.id_paciente
    JOIN PROFISSIONAL pr ON pr.id_profissional = o.id_profissional
    ORDER BY o.data_hora DESC LIMIT 6
")->fetchAll();

function risco_badge(string $r): string
{
    $map = [
        'nenhum'   => 'gray',
        'baixo'    => 'green',
        'moderado' => 'amber',
        'elevado'  => 'red',
        'iminente' => 'red',
    ];
    return '<span class="badge badge-' . ($map[$r] ?? 'gray') . '">' . $r . '</span>';
}
function humor_badge(string $h): string
{
    $map = ['eutimico' => 'green', 'deprimido' => 'blue', 'expansivo' => 'amber', 'irritavel' => 'red', 'ansioso' => 'amber', 'labil' => 'red'];
    return '<span class="badge badge-' . ($map[$h] ?? 'gray') . '">' . $h . '</span>';
}
?>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-icon"><i class="ti ti-bed" style="color:var(--primary)"></i></div>
        <div class="stat-label">Internamentos Ativos</div>
        <div class="stat-value"><?= $internamentos_ativos ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="ti ti-users" style="color:var(--success)"></i></div>
        <div class="stat-label">Total Pacientes</div>
        <div class="stat-value"><?= $total_pacientes ?></div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-icon"><i class="ti ti-building-hospital" style="color:var(--info)"></i></div>
        <div class="stat-label">Camas Disponíveis</div>
        <div class="stat-value"><?= $camas_disponiveis ?></div>
        <div class="stat-sub"><?= $camas_ocupadas ?> ocupadas</div>
    </div>
    <div class="stat-card amber">
        <div class="stat-icon"><i class="ti ti-pill" style="color:var(--warning)"></i></div>
        <div class="stat-label">Prescrições Ativas</div>
        <div class="stat-value"><?= $prescricoes_ativas ?></div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon"><i class="ti ti-alert-triangle" style="color:var(--danger)"></i></div>
        <div class="stat-label">Eventos Críticos Hoje</div>
        <div class="stat-value"><?= $eventos_hoje ?></div>
    </div>
</div>

<!-- Alertas de risco -->
<?php if (!empty($riscos)): ?>
    <div class="alert alert-danger mb-4">
        <i class="ti ti-alert-octagon"></i>
        <div>
            <strong><?= count($riscos) ?> paciente(s) com risco suicidário elevado ou iminente</strong>
            <?php foreach ($riscos as $r): ?>
                <div class="text-sm mt-1">
                    <strong><?= htmlspecialchars($r['nome']) ?></strong> — Quarto <?= $r['numero_quarto'] ?>/Cama <?= $r['numero_cama'] ?>
                    &nbsp;<?= risco_badge($r['risco_suicidario']) ?>&nbsp;<?= risco_badge($r['risco_agressividade']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

    <!-- Internamentos ativos -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="ti ti-bed"></i> Internamentos Ativos</span>
            <a href="internamentos.php" class="btn btn-outline btn-sm">Ver todos</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Paciente</th>
                        <th>Quarto</th>
                        <th>Episódio</th>
                        <th>Risco</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ativos as $a): ?>
                        <tr>
                            <td>
                                <div class="fw-600"><?= htmlspecialchars($a['paciente']) ?></div>
                                <div class="text-sm text-muted mono"><?= $a['num_utente'] ?></div>
                            </td>
                            <td><?= $a['numero_quarto'] ?>/<?= $a['numero_cama'] ?></td>
                            <td><span class="badge badge-blue"><?= $a['tipo_episodio'] ?></span></td>
                            <td><?= risco_badge($a['risco_suicidario']) ?></td>
                            <td>
                                <?php
                                $ec = ['instavel' => 'red', 'estabilizando' => 'amber', 'estavel' => 'green', 'alta_prevista' => 'cyan'];
                                echo '<span class="badge badge-' . ($ec[$a['estado_clinico']] ?? 'gray') . '">' . $a['estado_clinico'] . '</span>';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ativos)): ?><tr>
                            <td colspan="5" class="text-muted text-sm" style="text-align:center;padding:24px">Sem internamentos ativos</td>
                        </tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Últimas observações -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="ti ti-clipboard-list"></i> Últimas Observações</span>
            <a href="observacoes.php" class="btn btn-outline btn-sm">Ver todas</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Paciente</th>
                        <th>Humor</th>
                        <th>Adesão</th>
                        <th>Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($obs as $o): ?>
                        <tr>
                            <td>
                                <div class="fw-600"><?= htmlspecialchars($o['paciente']) ?></div>
                                <div class="text-sm text-muted"><?= htmlspecialchars($o['profissional']) ?></div>
                            </td>
                            <td><?= humor_badge($o['humor']) ?></td>
                            <td>
                                <?php
                                $am = ['total' => 'green', 'parcial' => 'amber', 'recusa' => 'red'];
                                echo '<span class="badge badge-' . ($am[$o['adesao_terapeutica']] ?? 'gray') . '">' . $o['adesao_terapeutica'] . '</span>';
                                ?>
                            </td>
                            <td class="text-sm text-muted mono"><?= date('d/m H:i', strtotime($o['data_hora'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>