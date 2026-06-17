<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/logger.php';

// Verifica permissões administrativas (Bloqueia se for administrativo simples se necessário)
if (isset($_SESSION['currentFuncao']) && ($_SESSION['currentFuncao'] === 'administrativo' || $_SESSION['currentFuncao'] === 'administrative')) {
    header('Location: pacientes?erro=sem_permissao');
    exit;
}

// Resgatar e validar o ID do evento vindo da URL
$id_evento = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_evento === 0) {
    header('Location: eventos?erro=id_invalido');
    exit;
}

// ── CONSULTA ADAPTADA RIGOROSAMENTE ÀS TABELAS DA TUA DB ────────────────
$sql = "
    SELECT 
        e.id_evento,
        e.data_hora,
        e.tipo_evento,
        e.descricao,
        e.intervencao_realizada,
        e.gravidade,
        i.id_internamento,
        p.nome AS nome_paciente,
        p.num_utente,
        p.data_nascimento,
        s.nome AS nome_servico,
        q.numero_quarto,
        c.numero_cama,
        prof.nome AS nome_profissional,
        prof.funcao AS funcao_profissional
    FROM EVENTO_CRITICO e
    INNER JOIN INTERNAMENTO i ON e.id_internamento = i.id_internamento
    INNER JOIN PACIENTE p ON i.id_paciente = p.id_paciente
    INNER JOIN CAMA c ON i.id_cama = c.id_cama
    INNER JOIN QUARTO q ON c.id_quarto = q.id_quarto
    INNER JOIN SERVICO s ON q.id_servico = s.id_servico
    INNER JOIN PROFISSIONAL prof ON e.id_profissional = prof.id_profissional
    WHERE e.id_evento = :id
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id_evento]);
$r = $stmt->fetch();

// Redireciona se a ocorrência não existir
if (!$r) {
    header('Location: eventos?erro=nao_encontrado');
    exit;
}

$pagina_atual  = 'eventos';
$titulo_pagina = 'Detalhes da Ocorrência #' . $r['id_evento'];
require_once 'includes/header.php';
?>

<div class="action-bar" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
    <a href="eventos" class="btn btn-outline">
        <i class="ti ti-arrow-left"></i> Voltar ao Histórico
    </a>
    <div class="text-sm text-muted mono">
        <i class="ti ti-clock"></i> Registado em: <?= date('d/m/Y H:i', strtotime($r['data_hora'])) ?>
    </div>
</div>

<div class="dashboard-grid">

    <div class="main-content" style="display: flex; flex-direction: column; gap: 24px;">

        <div class="card">
            <div class="card-header" style="border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 16px;">
                <h3 class="card-title" style="display: flex; align-items: center; gap: 8px;">
                    <i class="ti ti-user-shared" style="color: var(--primary)"></i> Perfil do Utente Envolvido
                </h3>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div>
                    <div class="text-sm text-muted">Nome do Paciente</div>
                    <div class="revealable-wrapper" onclick="toggleSensitiveData(this)" style="font-weight: 600; margin-top: 4px;">
                        <span class="confidential-placeholder" style="display: inline-flex;">[DADO CONFIDENCIAL]</span>
                        <span class="confidential-content" style="display: none; align-items: center; gap: 6px; color: var(--primary);">
                            <?= htmlspecialchars($r['nome_paciente']) ?>
                        </span>
                        <i class="ti ti-eye" style="margin-left: 6px; font-size: 0.85rem; color: var(--text-dimmed);"></i>
                    </div>
                </div>

                <div>
                    <div class="text-sm text-muted">Nº Serviço de Saúde (Utente)</div>
                    <div class="mono" style="font-weight: 500; margin-top: 4px;"><?= htmlspecialchars($r['num_utente']) ?></div>
                </div>

                <div>
                    <div class="text-sm text-muted">Data de Nascimento</div>
                    <div style="font-weight: 500; margin-top: 4px;">
                        <?= !empty($r['data_nascimento']) ? date('d/m/Y', strtotime($r['data_nascimento'])) : 'Não registada' ?>
                    </div>
                </div>
            </div>

            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <span class="text-sm text-muted">Internamento de Referência: <strong class="mono">#<?= $r['id_internamento'] ?></strong></span>
                <a href="processo?id=<?= $r['id_internamento'] ?>" class="btn btn-outline btn-xs">
                    <i class="ti ti-folder"></i> Consultar Processo Clínico
                </a>
            </div>
        </div>

        <div class="card" style="border-left: 4px solid var(--red, #ef4444);">
            <div class="card-header" style="margin-bottom: 12px;">
                <h4 style="margin: 0; color: #ef4444; display: flex; align-items: center; gap: 8px; font-size: 0.95rem;">
                    <i class="ti ti-alert-triangle"></i> Descrição Descritiva Ocorrida
                </h4>
            </div>
            <div class="text-sm" style="line-height: 1.6; color: var(--text-2, #334155); white-space: pre-line;">
                <?= htmlspecialchars($r['descricao']) ?>
            </div>
        </div>

        <div class="card" style="border-left: 4px solid var(--green, #10b981);">
            <div class="card-header" style="margin-bottom: 12px;">
                <h4 style="margin: 0; color: #10b981; display: flex; align-items: center; gap: 8px; font-size: 0.95rem;">
                    <i class="ti ti-shield-check"></i> Intervenção de Contenção / Terapêutica Efetuada
                </h4>
            </div>
            <div class="text-sm" style="line-height: 1.6; color: var(--text-2, #334155); white-space: pre-line;">
                <?= htmlspecialchars($r['intervencao_realizada']) ?>
            </div>
        </div>

    </div>

    <div class="sidebar">
        <div class="card" style="display: flex; flex-direction: column; gap: 20px;">

            <div>
                <h3 class="card-title" style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                    <i class="ti ti-shield-alert" style="color: var(--primary)"></i> Parâmetros de Triagem
                </h3>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border);">
                    <span class="text-sm text-muted">Tipologia</span>
                    <span class="badge badge-gray" style="text-transform: capitalize; font-weight: 500;">
                        <?= htmlspecialchars($r['tipo_evento']) ?>
                    </span>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border);">
                    <span class="text-sm text-muted">Gravidade</span>
                    <?php
                    $g = strtolower($r['gravidade']);
                    $color = 'gray';
                    if ($g === 'critica' || $g === 'elevada') $color = 'red';
                    elseif ($g === 'moderada') $color = 'amber';
                    elseif ($g === 'leve') $color = 'green';

                    echo '<span class="badge badge-' . $color . '" style="font-weight: 600; text-transform: uppercase;">' . htmlspecialchars($r['gravidade']) . '</span>';
                    ?>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0;">
                    <span class="text-sm text-muted">Localização</span>
                    <div style="text-align: right;">
                        <span class="text-sm fw-600" style="display: block;"><?= htmlspecialchars($r['nome_servico']) ?></span>
                        <span class="text-xs text-muted mono">Q: <?= htmlspecialchars($r['numero_quarto']) ?> / C: <?= htmlspecialchars($r['numero_cama']) ?></span>
                    </div>
                </div>
            </div>

            <div style="background: var(--surface-2); padding: 14px; border-radius: var(--radius-sm, 6px); border: 1px solid var(--border); margin-top: 10px;">
                <div class="text-xs text-muted" style="text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                    Registado por
                </div>
                <div class="fw-600 text-sm" style="color: var(--text-main);">
                    <?= htmlspecialchars($r['nome_profissional']) ?>
                </div>
                <div class="text-xs text-muted style-italic" style="margin-top: 2px; text-transform: capitalize;">
                    Role: <?= htmlspecialchars($r['funcao_profissional']) ?>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
    function toggleSensitiveData(element) {
        const placeholder = element.querySelector('.confidential-placeholder');
        const content = element.querySelector('.confidential-content');
        const icon = element.querySelector('i');

        if (content.style.display === 'none') {
            content.style.display = 'inline-flex';
            placeholder.style.display = 'none';
            icon.classList.replace('ti-eye', 'ti-eye-off');
        } else {
            content.style.display = 'none';
            placeholder.style.display = 'inline-flex';
            icon.classList.replace('ti-eye-off', 'ti-eye');
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>