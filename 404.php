<?php
// Se quiseres manter a sessão ou usar alguma variável do sistema, podes incluir o auth ou db, 
// mas para uma página 404 o ideal é ser o mais leve e independente possível.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página Não Encontrada</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            /* Podes ajustar para a cor do teu sistema */
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 flex items-center justify-center min-h-screen p-4">

    <div class="max-w-md w-full text-center bg-white p-8 rounded-2xl shadow-xl border border-slate-100 transition-all transform hover:scale-[1.01]">

        <div class="inline-flex items-center justify-center w-20 h-20 bg-amber-50 text-amber-500 rounded-full mb-6 animate-bounce">
            <i class="ti ti-error-404 text-5xl"></i>
        </div>

        <h1 class="text-6xl font-black text-slate-900 mb-2">404</h1>
        <h2 class="text-xl font-bold text-slate-700 mb-4">Página Não Encontrada</h2>

        <p class="text-slate-500 mb-8 leading-relaxed">
            O conteúdo que procura foi movido, eliminado ou nunca chegou a existir. Certifique-se que o endereço está correto.
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="javascript:history.back()" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors cursor-pointer">
                <i class="ti ti-arrow-left text-base"></i>
                Voltar Atrás
            </a>

            <a href="dashboard.php" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm shadow-blue-200 transition-colors">
                <i class="ti ti-home text-base"></i>
                Página Principal
            </a>
        </div>

        <div class="mt-8 pt-6 border-t border-slate-100 text-xs text-slate-400 flex items-center justify-center gap-1">
            <i class="ti ti-shield-lock"></i> PsiqSys
        </div>
    </div>

</body>

</html>