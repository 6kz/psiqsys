# PSIQSYS — Sistema de Gestão Hospitalar Psiquiátrica 🧠

O **PSIQSYS** é uma plataforma clínica especializada para a gestão e monitorização em tempo real de unidades de internamento psiquiátrico. O sistema destaca-se pelo foco na segurança do paciente através do controlo estrito de riscos (suicidário e agressividade), registo rápido de observações comportamentais, controlo de prescrições e gestão dinâmica de camas.

## 🚀 Versão Atual: v4.2 (Role-Based Access & Audit Refined)

A versão 4.2 consolida um sistema de controlo de acessos baseado em perfis (RBAC) e otimiza o mecanismo de auditoria interna, garantindo conformidade com a proteção de dados em ambiente hospitalar sem comprometer a fluidez da interface.

### Principais Funcionalidades
* **Dashboard Clínico:** Resumo estatístico centralizado de internamentos, ocupação de camas e alertas de risco iminente.
* **Triagem e Monitorização de Risco:** Indicadores visuais pulsantes para identificação imediata de pacientes em risco suicidário elevado ou instabilidade severa.
* **Gestão Adaptativa de Pacientes:** Fluxo segmentado de visualização onde a integridade dos dados demográficos e do histórico clínico é salvaguardada.
* **Evolução Hospitalar:** Registo cronológico de humor, adesão terapêutica (total, parcial ou recusa) e notas clínicas detalhadas.

---

## 👥 Controlo de Acessos por Perfil (RBAC)

O ecossistema do PSIQSYS divide-se em três níveis rígidos de permissão para garantir o princípio do privilégio mínimo:

| Perfil | Permissões na Interface | Restrições de Segurança |
| :--- | :--- | :--- |
| 🛠️ **TI (`ti`)** | Acesso total ao sistema, criação/edição de pacientes (incluindo alteração do Nº de Utente SNS) e desativação de registos (*Soft Delete*). | Nenhuma. Perfil de administração global. |
| 📋 **Administrativo (`administrativo`)** | **Acesso exclusivo a Pacientes e Internamentos.** Permissão para criar e editar dados demográficos básicos. | Bloqueio completo via backend/frontend a dashboards, observações clínicas, prescrições, eventos críticos e infraestrutura. Não altera Nº de Utente nem apaga registos. |
| 🩺 **Médico (`medico`)** | Acesso de consulta à listagem de pacientes, evolução clínica, passagem de prescrições e gestão de eventos críticos. | Interface em modo de **leitura estrita** no módulo de Pacientes. Não pode criar, editar ou desativar dados demográficos. |

---

## 🛠️ Stack Tecnológica

* **Backend:** PHP 8.2+ (Tipagem estrita e manipulação de sessões nativas)
* **Base de Dados:** MySQL 8.0 / MariaDB (Persistência segura via PDO)
* **Frontend:** HTML5 Semântico, CSS3 Moderno (Componentização por variáveis nativas), Tabler Icons & Themify Icons
* **Tipografia:** `DM Sans` (Legibilidade de interface) & `DM Mono` (Dados técnicos e IDs numéricos)

---

## 📁 Estrutura de Ficheiros (Núcleo Atualizado)

* `dashboard.php`: Painel analítico com lógica de agregação e barreira de segurança para administrativos.
* `pacientes.php`: Listagem adaptativa de utentes com formulários inteligentes que se moldam ao nível de permissão do utilizador conectado.
* `includes/db.php`: Singleton / Instanciação segura da conexão PDO.
* `includes/auditoria.php`: Motor do sistema de Logs (`registarLog()`). Monitoriza de forma cirúrgica operações de `INSERT`, `UPDATE`, `DISABLE` e `SEARCH`.
* `includes/header.php` & `footer.php`: Esqueleto global com controlo dinâmico de renderização da barra lateral (`sidebar`) baseado no perfil da sessão.
* `assets/css/style.css`: Design System v2.2 (Arquitetura centralizada de tokens de cor e suporte estrutural a *Dark Mode*).

---

## 🎨 Design System v2.2 (Cores Semânticas)

A palete visual foi desenhada para mitigar a fadiga ocular em turnos hospitalares prolongados e garantir contraste acessível sob diferentes condições de luminosidade.

```
├── [ #ef4444 ] 🔴 Crítico  -> Riscos elevados, recusa terapêutica, alarmes iminentes
├── [ #f59e0b ] 🟡 Atenção  -> Riscos moderados, adesão parcial, alertas preventivos
├── [ #10b981 ] 🟢 Estável  -> Pacientes compensados, camas livres, altas validadas
└── [ #06b6d4 ] 🔵 Info     -> Métricas do sistema, contadores e dados neutros
```

---

## ⚙️ Instalação e Configuração

1. **Ambiente:** Certifique-se de que o servidor possui o PHP 8.x ativo com a extensão `pdo_mysql` instalada.
2. **Base de Dados:**
   * Execute o script SQL estrutural para gerar as tabelas `PACIENTE`, `INTERNAMENTO` e a tabela de auditoria `LOG`.
   * Certifique-se de que a view `vw_internamentos_ativos` está devidamente compilada no seu SGBD.
3. **Variáveis de Sessão Obrigatórias:**
   Para o correto funcionamento do ecossistema, o script de login deve injetar na superglobal `$_SESSION`:
   * `$_SESSION['currentFuncao']`: Expectável (`ti`, `administrativo` ou `medico`).
   * `$_SESSION['currentNome']`: Nome de exibição do profissional.
4. **Ficheiro de Ligação:**
   Configure as credenciais em `includes/db.php`:
   ```php
   $host = 'localhost';
   $db   = 'psiqsys';
   $user = 'usuario_seguro';
   $pass = 'senha_criptografada';
   ```

---

## 🔒 Protocolos de Segurança Implementados

* **Prevenção de SQL Injection:** Bloqueio absoluto de queries dinâmicas. Toda e qualquer interação com o banco de dados utiliza parâmetros fortemente tipados em *Prepared Statements* via PDO.
* **XSS Defesa Crítica:** Toda a renderização de dados vindos da base de dados ou de inputs do utilizador é sanitizada na camada de apresentação através da função `htmlspecialchars()`.
* **Segurança de Estado Dinâmica:** A interface omite componentes de mutação de dados para utilizadores sem privilégios e os scripts de receção de dados (`POST`) realizam uma dupla validação redundante no servidor, devolvendo `sem_permissao` caso detetem manipulação de requisições.

---
*Desenvolvido em ambiente académico por Tomás Maio Matos, José Maria Alves, Ivan Mubai e António Urdaneta.*

### License / Copyright

Copyright (c) 2026 Tomás Maio Matos. All rights reserved. 

This code is provided solely for educational purposes and personal review. 
No permission is granted to copy, distribute, modify, or use this software 
in any project or application.
