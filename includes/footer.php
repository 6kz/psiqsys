<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo_pagina ?? 'PsiqSys') ?> - PsiqSys</title>
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>

<script>
    function atualizarRelogio() {
        const agora = new Date();
        const horas = String(agora.getHours()).padStart(2, '0');
        const minutos = String(agora.getMinutes()).padStart(2, '0');
        const segundos = String(agora.getSeconds()).padStart(2, '0');
        document.getElementById('clock').textContent = `${horas}:${minutos}:${segundos}`;
    }
    setInterval(atualizarRelogio, 1000);
</script>

<script>
    function toggleTheme() {
        const root = document.body;
        const current = root.getAttribute("data-theme");
        const next = current === "dark" ? "light" : "dark";
        root.setAttribute("data-theme", next);
        localStorage.setItem("theme", next);
    }
    document.addEventListener("DOMContentLoaded", () => {
        const saved = localStorage.getItem("theme") || "light";
        document.body.setAttribute("data-theme", saved);
    });
</script>

<?php
// Garante que temos a variável de função disponível para validação visual
$funcao_sidebar = $_SESSION['currentFuncao'] ?? '';
?>

<body data-theme="light">
    <div class="layout">

        <aside class="sidebar">
            <div class="sidebar-logo">
                <i class="ti ti-brain"></i>
                <div>
                    <strong>PsiqSys</strong>
                    <span>Internamento Psiquiátrico</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <?php if ($funcao_sidebar !== 'administrativo' && $funcao_sidebar !== 'administrative'): ?>
                    <div class="nav-group-label">Geral</div>
                    <a href="dashboard.php" class="nav-item <?= ($pagina_atual ?? '') === 'dashboard' ? 'active' : '' ?>"><i class="ti ti-layout-dashboard"></i> Dashboard</a>
                <?php endif; ?>

                <div class="nav-group-label">Clínico</div>
                <a href="pacientes.php" class="nav-item <?= ($pagina_atual ?? '') === 'pacientes' ? 'active' : '' ?>"><i class="ti ti-users"></i> Pacientes</a>
                <a href="internamentos.php" class="nav-item <?= ($pagina_atual ?? '') === 'internamentos' ? 'active' : '' ?>"><i class="ti ti-bed"></i> Internamentos</a>

                <?php if ($funcao_sidebar !== 'administrativo' && $funcao_sidebar !== 'administrative'): ?>
                    <a href="observacoes.php" class="nav-item <?= ($pagina_atual ?? '') === 'observacoes' ? 'active' : '' ?>"><i class="ti ti-clipboard-list"></i> Observações</a>
                    <a href="prescricoes.php" class="nav-item <?= ($pagina_atual ?? '') === 'prescricoes' ? 'active' : '' ?>"><i class="ti ti-pill"></i> Prescrições</a>
                    <a href="eventos.php" class="nav-item <?= ($pagina_atual ?? '') === 'eventos' ? 'active' : '' ?>"><i class="ti ti-alert-triangle"></i> Eventos Críticos</a>

                    <div class="nav-group-label">Infraestrutura</div>
                    <a href="camas.php" class="nav-item <?= ($pagina_atual ?? '') === 'camas' ? 'active' : '' ?>"><i class="ti ti-building-hospital"></i> Camas & Quartos</a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <i class="ti ti-user-circle" style="font-size:26px"></i>
                <div>
                    <strong><?= htmlspecialchars($_SESSION['currentNome'] ?? 'Usuário') ?></strong>
                    <div><?= date('d/m/Y') ?></div>
                </div>
                <div style="margin-left: auto;">
                    <button class="btn btn-outline btn-sm openModalBtnLogout"><i class="ti ti-power"></i> Sair</button>
                </div>
            </div>
        </aside>

        <div class="main-content">
            <?php
            $dias = ['Sunday' => 'Domingo', 'Monday' => 'Segunda-feira', 'Tuesday' => 'Terça-feira', 'Wednesday' => 'Quarta-feira', 'Thursday' => 'Quinta-feira', 'Friday' => 'Sexta-feira', 'Saturday' => 'Sábado'];
            $meses = ['January' => 'janeiro', 'February' => 'fevereiro', 'March' => 'março', 'April' => 'abril', 'May' => 'maio', 'June' => 'junho', 'July' => 'julho', 'August' => 'agosto', 'September' => 'setembro', 'October' => 'outubro', 'November' => 'novembro', 'December' => 'dezembro'];
            $data = $dias[date('l')] . ', ' . date('d') . ' de ' . $meses[date('F')] . ' de ' . date('Y');
            ?>
            <div class="topbar">
                <button class="btn btn-outline btn-sm" onclick="toggleTheme()"><i class="ti ti-moon"></i></button>
                <div class="page-title"><?= htmlspecialchars($titulo_pagina ?? 'PsiqSys') ?></div>
                <div class="topbar-right">
                    <span class="topbar-date"><?= $data ?></span>
                    <span class="topbar-hour" id="clock"></span>
                </div>
            </div>