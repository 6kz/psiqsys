</div><!-- /.content-area -->
</div><!-- /.main-content -->
</div><!-- /.layout -->

<div id="myModalLogout" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">
                <i class="ti ti-logout" style="margin-right: 8px; color: var(--danger); font-size: 18px;"></i>
                Confirmar Saída
            </div>
            <button class="btn btn-outline btn-sm closeLogout" style="padding: 2px 8px;">&times;</button>
        </div>
        <div class="modal-body">
            <p style="color: var(--text-2);">Tem a certeza que deseja terminar a sua sessão no PsiqSys?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline closeLogout">Cancelar</button>
            <form id="logoutForm" action="logout.php" method="post" style="display: inline;">
                <button class="btn btn-danger" type="submit" name="submitUser">Sair</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        function setupModal(modalOverlayId, btnClass, closeClass) {
            var overlay = document.getElementById(modalOverlayId);
            if (!overlay) return;

            var closeButtons = document.querySelectorAll("." + closeClass);

            document.querySelectorAll("." + btnClass).forEach(function(btn) {
                btn.addEventListener("click", function(e) {
                    e.preventDefault();
                    overlay.style.display = "flex";
                    setTimeout(function() {
                        overlay.classList.add("open");
                    }, 10);
                });
            });

            function closeModal() {
                overlay.classList.remove("open");
                setTimeout(function() {
                    if (!overlay.classList.contains("open")) {
                        overlay.style.display = "none";
                    }
                }, 200);
            }

            closeButtons.forEach(function(btn) {
                btn.addEventListener("click", closeModal);
            });

            overlay.addEventListener("click", function(e) {
                if (e.target === overlay) {
                    closeModal();
                }
            });
        }

        setupModal("myModalLogout", "openModalBtnLogout", "closeLogout");
    });
</script>

</div>
</div>
</div>
<footer style="
    margin-left: var(--sidebar-w);
    background: var(--surface);
    border-top: 1px solid var(--border);
    padding: 12px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: .78rem;
    color: var(--text-3);
">
    <span>PsiqSys &copy; <?= date('Y') ?> — Sistema de Internamento Psiquiátrico</span>
    <span class="mono">v3.0</span>
</footer>

</body>

</html>