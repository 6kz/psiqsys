<!DOCTYPE html>
<html lang="pt">
            </div><!-- /.content-area -->
        </div><!-- /.main-content -->
    </div><!-- /.layout -->

    <!-- Modal Logout -->
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

                <a href="logout.php" class="btn btn-danger">
                    <i class="ti ti-power"></i>
                    Sair
                </a>
            </div>
        </div>
    </div>

    <script>
        // relógio
        atualizarRelogio();
        setInterval(atualizarRelogio, 1000);

        // abrir modal logout
        document.querySelectorAll('.openModalBtnLogout').forEach(btn => {
            btn.addEventListener('click', () => {
                openModal('logoutModal');
            });
        });

        // abrir modal
        function openModal(id) {
            document.getElementById(id).classList.add('open');
        }

        // fechar modal
        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
        }

        // fechar modal ao clicar fora
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('open');
                }
            });
        });
    </script>
<script>
    const SESSION_TIMEOUT = 10 * 60 * 1000; // 10 minutos até auto-logout / session destroy

    setTimeout(() => {
        // esconde imediatamente a informação visível
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
                ">
                    <h2>Sessão expirada</h2>
                    <p>A sessão foi terminada por inatividade.</p>
                    <p>Será redirecionado para o login.</p>
                </div>
            </div>
        `;

        // redireciona para logout para destruir sessão no servidor
        setTimeout(() => {
            window.location.href = 'logout.php?timeout=1';
        }, 1000);

    }, SESSION_TIMEOUT);
</script>
</body>
</html>