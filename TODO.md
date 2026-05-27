# 🧠 PsiqSys — TO-DO

## 🚧 Funcionalidades Pendentes

### 👥 Pacientes

- [ ] Melhorar a visualização das observações clínicas e eventos críticos

### 💊 Prescrições

- [ ] Apenas médicos podem executar ações nas prescrições
- [ ] Adicionar modal para cancelamento de prescrição (se sim o porquê de ter cancelado)
- [ ] Quando paciente é desativado devemos cancelar a prescrição?

### 🛏️ Camas & Internamentos

- [ ] Permitir aos enfermeiros gerir camas e quartos
- [ ] Adicionar diárias aos internamentos

### 🔐 Sistema

- [ ] Recuperar a palavra-passe (mudar a password de 90d em 90d?)
- [ ] Mudar a palavra-passe no login (password_recovery.php) | user, password antiga, password nova e confirmação password nova
- [ ] Conseguir por o auditoria.php a funcionar em todas as paginas
- [ ] Criar um user para cada um
- [ ] Criar página de administração de medicação

---

# ✅ Já Implementado

- [x] Adição da hora juntamente com a data no topbar
- [x] Permitir à equipa de TI eliminar pacientes
- [x] Adicionar botão de logout
- [x] Administrativos apenas podem visualizar dados do paciente, e internamento
- [x] Mostrar pacientes desativos com tag desativo (SÓ COM BUSCA)
- [x] Mudar fonte de dark mode
- [x] Após x minutos offline session_destroy()
- [x] Administrativos só podem acessar dados do paciente e não dados clinicos
- [x] Ter uma forma de ocultar dados sensíveis (Oculto), OnClick aparecia