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

</body>
</html>