<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>

<body>
    <div class="modal-overlay" id="logoutModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="ti ti-logout"></i>
                    Terminar Sessão
                </div>
            </div>

            <div class="modal-body">
                <p>Tem a certeza que pretende terminar sessão?</p>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('logoutModal')">
                    Cancelar
                </button>

                <a href="includes/logout.php" class="btn btn-danger">
                    <i class="ti ti-power"></i>
                    Sair
                </a>
            </div>
        </div>
    </div>

    <script>
        // Função do relógio (criada para evitar o erro de 'not defined')
        function atualizarRelogio() {
            // Se tiver um elemento de relógio na tela, você pode atualizar o texto dele aqui
            // Exemplo: document.getElementById('seu-relogio').innerText = new Date().toLocaleTimeString();
            console.log("Relógio atualizado: " + new Date().toLocaleTimeString());
        }

        // Inicializa o relógio se a função existir
        if (typeof atualizarRelogio === "function") {
            atualizarRelogio();
            setInterval(atualizarRelogio, 1000);
        }

        // abrir modal logout
        document.querySelectorAll('.openModalBtnLogout').forEach(btn => {
            btn.addEventListener('click', () => {
                openModal('logoutModal');
            });
        });

        // abrir modal
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.add('open');
        }

        // fechar modal
        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.remove('open');
        }

        // fechar modal ao clicar fora (corrigido o erro de fechamento de chaves '}')
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('open');
                }
            });
        });
    </script>

    <script>
        // 10 minutos até auto-logout / session destroy
        const SESSION_TIMEOUT = 10 * 60 * 1000;

        setTimeout(() => {
            // esconde imediatamente a informação visível injetando a tela de bloqueio
            document.body.innerHTML = `
            <div style="
                min-height:100vh;
                display:flex;
                align-items:center;
                justify-content:center;
                font-family:Arial, sans-serif;
                background:#eef2f6;
                color:#1f2937;
            ">
                <div style="
                    background:white;
                    border:1px solid #d8e0e8;
                    padding:30px;
                    max-width:420px;
                    text-align:center;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
                ">
                    <h2 style="margin-top:0; color:#ef4444;">Sessão expirada</h2>
                    <p>A sessão foi terminada por inatividade.</p>
                    <p style="color:#6b7280; font-size:14px;">Será redirecionado para o login...</p>
                </div>
            </div>
        `;

            // redireciona para o arquivo correto de logout para destruir a sessão no servidor
            setTimeout(() => {
                window.location.href = 'includes/logout.php?timeout=1';
            }, 1500);

        }, SESSION_TIMEOUT);
    </script>
</body>

</html>