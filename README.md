# PSIQSYS — Sistema de Gestão Hospitalar Psiquiátrica 🧠

O **PSIQSYS** é uma plataforma de gestão clínica especializada para unidades de internamento psiquiátrico. O sistema foca-se na monitorização em tempo real de riscos (suicidário e agressividade), observações comportamentais e gestão de camas.

## 🚀 Versão Atual: v2.2 (Refined Design)

A versão 2.2 introduz um Design System aprimorado com foco em legibilidade, acessibilidade e micro-interações para reduzir a carga cognitiva dos profissionais de saúde.

### Principais Funcionalidades
* **Dashboard em Tempo Real:** Resumo de estatísticas críticas (internamentos, pacientes, camas, prescrições).
* **Monitorização de Risco:** Alertas visuais e animações para pacientes com risco suicidário elevado ou iminente.
* **Gestão de Internamentos:** Visualização rápida de estados clínicos (estável, instável, alta prevista).
* **Registo de Observações:** Acompanhamento de humor, adesão terapêutica e notas clínicas.

## 🛠️ Stack Tecnológica

* **Backend:** PHP 8.x
* **Base de Dados:** MySQL/MariaDB (PDO para conexões seguras)
* **Frontend:** HTML5, CSS3 (Custom Design System), Tabler Icons
* **Tipografia:** DM Sans (Interface) & DM Mono (Dados técnicos)

## 📁 Estrutura de Ficheiros (Núcleo)

* `dashboard.php`: Painel principal com lógica de agregação de dados e alertas de risco.
* `assets/css/style.css`: Design System v2.2 completo (variáveis CSS, utilitários e componentes).
* `includes/db.php`: Configuração da ligação à base de dados.
* `includes/header.php` & `footer.php`: Componentes globais de layout.

## 🎨 Design System v2.2

O CSS foi reconstruído utilizando uma arquitetura baseada em variáveis nativas para facilitar a manutenção e o "Dark Mode" futuro.

### Cores Semânticas
| Estado | Cor | Uso |
| :--- | :--- | :--- |
| **Crítico** | `#ef4444` | Riscos iminentes, recusa terapêutica, instabilidade. |
| **Atenção** | `#f59e0b` | Riscos moderados, adesão parcial. |
| **Estável** | `#10b981` | Pacientes estáveis, camas disponíveis. |
| **Info** | `#06b6d4` | Dados informativos, tipos de episódios. |

## ⚙️ Instalação e Configuração

1.  **Requisitos:** Servidor Apache/Nginx com suporte a PHP 7.4+ e MySQL.
2.  **Base de Dados:**
    * Importe o schema SQL (não incluído neste repositório).
    * Certifique-se de que a view `vw_internamentos_ativos` está criada.
3.  **Configuração de Ligação:**
    * Edite `includes/db.php` com as suas credenciais locais:
    ```php
    $host = 'localhost';
    $db   = 'psiqsys';
    $user = 'usuario';
    $pass = 'senha';
    ```

## 🔒 Segurança

* Utiliza **Prepared Statements** (PDO) para prevenir ataques de SQL Injection.
* **Sanitização de Output:** Todas as variáveis exibidas no dashboard passam por `htmlspecialchars()`.
* **Design Acessível:** Contrastes de cores otimizados para ambientes hospitalares de alta luminosidade.

---
*Desenvolvido por Tomás Maio Matos, José Maria Alves, Ivan Mubai e António Urdaneta.*

## License / Copyright

Copyright (c) 2026 Tomás Maio Matos. All rights reserved. 

This code is provided solely for educational purposes and personal review. 
No permission is granted to copy, distribute, modify, or use this software 
in any project or application.
