-- ============================================================
--  BD Internamento Psiquiátrico — Perturbação Bipolar
--  Versão 2.0 — Melhorada
--  Compatível com MySQL 8.0+
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- ============================================================
-- 0. CRIAÇÃO DA BASE DE DADOS
-- ============================================================

CREATE DATABASE IF NOT EXISTS bd_psiquiatria
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bd_psiquiatria;


-- ============================================================
-- 1. TABELAS BASE (sem dependências externas)
-- ============================================================

-- ------------------------------------------------------------
-- SERVICO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS SERVICO (
  id_servico   INT           NOT NULL AUTO_INCREMENT,
  nome         VARCHAR(100)  NOT NULL,
  PRIMARY KEY (id_servico)
) ENGINE=InnoDB COMMENT='Serviços hospitalares (ex: Psiquiatria Adultos)';


-- ------------------------------------------------------------
-- QUARTO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS QUARTO (
  id_quarto      INT          NOT NULL AUTO_INCREMENT,
  id_servico     INT          NOT NULL,
  numero_quarto  VARCHAR(10)  NOT NULL,
  PRIMARY KEY (id_quarto),
  CONSTRAINT fk_quarto_servico
    FOREIGN KEY (id_servico) REFERENCES SERVICO (id_servico)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='Quartos pertencentes a um serviço';


-- ------------------------------------------------------------
-- CAMA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS CAMA (
  id_cama       INT          NOT NULL AUTO_INCREMENT,
  id_quarto     INT          NOT NULL,
  numero_cama   VARCHAR(10)  NOT NULL,
  estado        ENUM('disponivel','ocupada','interdita','manutencao')
                NOT NULL DEFAULT 'disponivel',
  PRIMARY KEY (id_cama),
  CONSTRAINT fk_cama_quarto
    FOREIGN KEY (id_quarto) REFERENCES QUARTO (id_quarto)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='Camas com estado operacional';


-- ------------------------------------------------------------
-- DIAGNOSTICO_DSM
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS DIAGNOSTICO_DSM (
  id_diagnostico  INT           NOT NULL AUTO_INCREMENT,
  codigo_dsm      VARCHAR(20)   NOT NULL,
  codigo_icd10    VARCHAR(20)   NULL,
  nome_diagnostico VARCHAR(200) NOT NULL,
  descricao       TEXT          NULL,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_diagnostico),
  UNIQUE KEY uk_codigo_dsm (codigo_dsm)
) ENGINE=InnoDB COMMENT='Catálogo de diagnósticos DSM-5 / ICD-10';


-- ------------------------------------------------------------
-- MEDICACAO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS MEDICACAO (
  id_medicacao       INT          NOT NULL AUTO_INCREMENT,
  nome               VARCHAR(150) NOT NULL,
  classe             VARCHAR(100) NOT NULL
                     COMMENT 'Ex: Estabilizador de humor, Antipsicótico',
  dosagem            VARCHAR(50)  NOT NULL,
  forma_farmaceutica ENUM('comprimido','capsula','injetavel','xarope','patch','sublingual')
                     NOT NULL,
  created_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_medicacao)
) ENGINE=InnoDB COMMENT='Catálogo de medicamentos';


-- ------------------------------------------------------------
-- PERFIL_ACESSO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PERFIL_ACESSO (
  id_perfil   INT          NOT NULL AUTO_INCREMENT,
  nome_perfil VARCHAR(50)  NOT NULL,
  PRIMARY KEY (id_perfil),
  UNIQUE KEY uk_nome_perfil (nome_perfil)
) ENGINE=InnoDB COMMENT='Perfis RBAC: Médico, Enfermeiro, Administrativo, etc.';


-- ============================================================
-- 2. TABELAS DE ENTIDADES PRINCIPAIS
-- ============================================================

-- ------------------------------------------------------------
-- PACIENTE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PACIENTE (
  id_paciente      INT          NOT NULL AUTO_INCREMENT,
  nome             VARCHAR(150) NOT NULL,
  data_nascimento  DATE         NOT NULL,
  num_utente       CHAR(9)      NOT NULL,
  contacto         VARCHAR(20)  NULL,
  morada           TEXT         NULL,
  created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                   ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_paciente),
  UNIQUE KEY uk_num_utente (num_utente)
) ENGINE=InnoDB COMMENT='Dados demográficos do paciente — categoria especial RGPD Art.9';


-- ------------------------------------------------------------
-- PROFISSIONAL
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PROFISSIONAL (
  id_profissional  INT          NOT NULL AUTO_INCREMENT,
  nome             VARCHAR(150) NOT NULL,
  funcao           ENUM('medico','enfermeiro','psicologo','assistente_social','administrativo')
                   NOT NULL,
  num_cedula       VARCHAR(20)  NOT NULL,
  created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                   ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_profissional),
  UNIQUE KEY uk_cedula (num_cedula)
) ENGINE=InnoDB COMMENT='Profissionais de saúde e administrativos';


-- ------------------------------------------------------------
-- UTILIZADOR
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS UTILIZADOR (
  id_utilizador    INT          NOT NULL AUTO_INCREMENT,
  id_profissional  INT          NOT NULL,
  username         VARCHAR(50)  NOT NULL,
  password_hash    CHAR(60)     NOT NULL COMMENT 'bcrypt hash',
  ativo            BOOLEAN      NOT NULL DEFAULT TRUE,
  created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                   ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_utilizador),
  UNIQUE KEY uk_username (username),
  UNIQUE KEY uk_profissional (id_profissional),
  CONSTRAINT fk_utilizador_profissional
    FOREIGN KEY (id_profissional) REFERENCES PROFISSIONAL (id_profissional)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='Credenciais de acesso ao sistema (1 utilizador por profissional)';


-- ------------------------------------------------------------
-- UTILIZADOR_PERFIL  (N:M)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS UTILIZADOR_PERFIL (
  id_utilizador  INT  NOT NULL,
  id_perfil      INT  NOT NULL,
  PRIMARY KEY (id_utilizador, id_perfil),
  CONSTRAINT fk_up_utilizador
    FOREIGN KEY (id_utilizador) REFERENCES UTILIZADOR (id_utilizador)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_up_perfil
    FOREIGN KEY (id_perfil) REFERENCES PERFIL_ACESSO (id_perfil)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Associação utilizador ↔ perfil (RBAC)';


-- ============================================================
-- 3. INTERNAMENTO E DEPENDENTES
-- ============================================================

-- ------------------------------------------------------------
-- INTERNAMENTO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS INTERNAMENTO (
  id_internamento      INT           NOT NULL AUTO_INCREMENT,
  id_paciente          INT           NOT NULL,
  id_cama              INT           NOT NULL,
  data_admissao        DATETIME      NOT NULL,
  motivo_internamento  TEXT          NOT NULL,
  tipo_episodio        ENUM('maníaco','depressivo','misto','hipomaníaco','nao_especificado')
                       NOT NULL,
  risco_suicidario     ENUM('nenhum','baixo','moderado','elevado','iminente')
                       NOT NULL DEFAULT 'nenhum',
  risco_agressividade  ENUM('nenhum','baixo','moderado','elevado')
                       NOT NULL DEFAULT 'nenhum',
  estado_clinico       ENUM('instavel','estabilizando','estavel','alta_prevista')
                       NOT NULL DEFAULT 'instavel',
  data_alta            DATETIME      NULL
                       COMMENT 'NULL = internamento ativo',
  created_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                       ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_internamento),
  CONSTRAINT fk_int_paciente
    FOREIGN KEY (id_paciente) REFERENCES PACIENTE (id_paciente)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_int_cama
    FOREIGN KEY (id_cama) REFERENCES CAMA (id_cama)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  -- Garante que não existem dois internamentos ativos na mesma cama
  CONSTRAINT chk_data_alta
    CHECK (data_alta IS NULL OR data_alta > data_admissao)
) ENGINE=InnoDB COMMENT='Episódio de internamento psiquiátrico';

-- Índice para a verificação de sobreposição temporal por cama
CREATE INDEX idx_internamento_cama_datas
  ON INTERNAMENTO (id_cama, data_admissao, data_alta);


-- ------------------------------------------------------------
-- INTERNAMENTO_DIAGNOSTICO  (N:M — substitui FK direta)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS INTERNAMENTO_DIAGNOSTICO (
  id_internamento  INT   NOT NULL,
  id_diagnostico   INT   NOT NULL,
  tipo             ENUM('principal','secundario','comorbilidade')
                   NOT NULL DEFAULT 'principal',
  data             DATE  NOT NULL,
  PRIMARY KEY (id_internamento, id_diagnostico),
  CONSTRAINT fk_id_internamento
    FOREIGN KEY (id_internamento) REFERENCES INTERNAMENTO (id_internamento)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_id_diagnostico
    FOREIGN KEY (id_diagnostico) REFERENCES DIAGNOSTICO_DSM (id_diagnostico)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='Diagnósticos por internamento (permite múltiplos e comorbilidades)';


-- ------------------------------------------------------------
-- OBSERVACAO_COMPORTAMENTAL
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS OBSERVACAO_COMPORTAMENTAL (
  id_observacao          INT       NOT NULL AUTO_INCREMENT,
  id_internamento        INT       NOT NULL,
  id_profissional        INT       NOT NULL,
  data_hora              DATETIME  NOT NULL,
  humor                  ENUM('eutimico','deprimido','expansivo','irritavel','ansioso','labil')
                         NOT NULL,
  sono                   ENUM('normal','insonia','hipersonia','fragmentado')
                         NOT NULL,
  discurso               ENUM('normal','acelerado','lento','incoerente','mutismo')
                         NOT NULL,
  atividade_psicomotora  ENUM('normal','agitado','retardado','catatónico')
                         NOT NULL,
  delirio                TINYINT   NOT NULL DEFAULT 0
                         COMMENT 'Escala 0-4 (0=ausente, 4=grave)',
  alucinacao             TINYINT   NOT NULL DEFAULT 0
                         COMMENT 'Escala 0-4 (0=ausente, 4=grave)',
  adesao_terapeutica     ENUM('total','parcial','recusa')
                         NOT NULL DEFAULT 'total',
  notas_clinicas         TEXT      NULL,
  PRIMARY KEY (id_observacao),
  CONSTRAINT fk_obs_internamento
    FOREIGN KEY (id_internamento) REFERENCES INTERNAMENTO (id_internamento)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_obs_profissional
    FOREIGN KEY (id_profissional) REFERENCES PROFISSIONAL (id_profissional)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_delirio
    CHECK (delirio BETWEEN 0 AND 4),
  CONSTRAINT chk_alucinacao
    CHECK (alucinacao BETWEEN 0 AND 4)
) ENGINE=InnoDB COMMENT='Registo de observação comportamental (turno a turno)';


-- ------------------------------------------------------------
-- PRESCRICAO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PRESCRICAO (
  id_prescricao    INT           NOT NULL AUTO_INCREMENT,
  id_internamento  INT           NOT NULL,
  id_profissional  INT           NOT NULL,
  id_medicacao     INT           NOT NULL,
  dose             VARCHAR(30)   NOT NULL,
  via              ENUM('oral','iv','im','sc','sublingual','transdermico','inalado')
                   NOT NULL,
  frequencia       VARCHAR(50)   NOT NULL COMMENT 'Ex: 8/8h, 12/12h, SOS',
  prn              BOOLEAN       NOT NULL DEFAULT FALSE
                   COMMENT 'PRN = prescrição em SOS (se necessário)',
  dose_maxima_dia  VARCHAR(30)   NULL
                   COMMENT 'Obrigatório quando prn = TRUE',
  data_inicio      DATE          NOT NULL,
  data_fim         DATE          NULL,
  estado           ENUM('ativa','suspensa','concluida','cancelada')
                   NOT NULL DEFAULT 'ativa',
  PRIMARY KEY (id_prescricao),
  CONSTRAINT fk_presc_internamento
    FOREIGN KEY (id_internamento) REFERENCES INTERNAMENTO (id_internamento)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_presc_profissional
    FOREIGN KEY (id_profissional) REFERENCES PROFISSIONAL (id_profissional)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_presc_medicacao
    FOREIGN KEY (id_medicacao) REFERENCES MEDICACAO (id_medicacao)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_datas_prescricao
    CHECK (data_fim IS NULL OR data_fim >= data_inicio)
) ENGINE=InnoDB COMMENT='Prescrições médicas por internamento';


-- ------------------------------------------------------------
-- ADMINISTRACAO_MEDICACAO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ADMINISTRACAO_MEDICACAO (
  id_administracao         INT       NOT NULL AUTO_INCREMENT,
  id_prescricao            INT       NOT NULL,
  id_internamento          INT       NOT NULL COMMENT 'FK redundante para relatórios de consumo',
  id_profissional          INT       NOT NULL,
  data_hora                DATETIME  NOT NULL,
  administrada             BOOLEAN   NOT NULL DEFAULT TRUE,
  motivo_nao_administracao VARCHAR(200) NULL,
  efeitos_adversos         TEXT      NULL,
  PRIMARY KEY (id_administracao),
  CONSTRAINT fk_adm_prescricao
    FOREIGN KEY (id_prescricao) REFERENCES PRESCRICAO (id_prescricao)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_adm_internamento
    FOREIGN KEY (id_internamento) REFERENCES INTERNAMENTO (id_internamento)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_adm_profissional
    FOREIGN KEY (id_profissional) REFERENCES PROFISSIONAL (id_profissional)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='Registo de administração de medicação (enfermagem)';


-- ------------------------------------------------------------
-- EVENTO_CRITICO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS EVENTO_CRITICO (
  id_evento              INT       NOT NULL AUTO_INCREMENT,
  id_internamento        INT       NOT NULL,
  id_profissional        INT       NOT NULL,
  data_hora              DATETIME  NOT NULL,
  tipo_evento            ENUM('autoagressao','heteroagressao','fuga','queda',
                              'crise_convulsiva','recusa_alimentar','outro')
                         NOT NULL,
  descricao              TEXT      NOT NULL,
  intervencao_realizada  TEXT      NOT NULL,
  gravidade              ENUM('baixa','moderada','elevada','critica')
                         NOT NULL,
  PRIMARY KEY (id_evento),
  CONSTRAINT fk_ev_internamento
    FOREIGN KEY (id_internamento) REFERENCES INTERNAMENTO (id_internamento)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_ev_profissional
    FOREIGN KEY (id_profissional) REFERENCES PROFISSIONAL (id_profissional)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='Eventos críticos durante o internamento';


-- ------------------------------------------------------------
-- ALTA_CLINICA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ALTA_CLINICA (
  id_alta               INT       NOT NULL AUTO_INCREMENT,
  id_internamento       INT       NOT NULL,
  id_profissional       INT       NOT NULL,
  id_diagnostico_final  INT       NOT NULL COMMENT 'FK para DIAGNOSTICO_DSM (não texto livre)',
  data_alta             DATETIME  NOT NULL,
  plano_pos_alta        TEXT      NULL,
  encaminhamento        ENUM('consulta_externa','hospital_dia','cuidados_primarios',
                             'sem_encaminhamento','outra_instituicao')
                        NOT NULL,
  PRIMARY KEY (id_alta),
  UNIQUE KEY uk_alta_internamento (id_internamento),
  CONSTRAINT fk_alta_internamento
    FOREIGN KEY (id_internamento) REFERENCES INTERNAMENTO (id_internamento)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_alta_profissional
    FOREIGN KEY (id_profissional) REFERENCES PROFISSIONAL (id_profissional)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_alta_diagnostico
    FOREIGN KEY (id_diagnostico_final) REFERENCES DIAGNOSTICO_DSM (id_diagnostico)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='Nota de alta clínica (máx. 1 por internamento)';


-- ------------------------------------------------------------
-- LOG_ACESSO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS LOG_ACESSO (
  id_log          BIGINT       NOT NULL AUTO_INCREMENT,
  id_utilizador   INT          NOT NULL,
  id_paciente     INT          NULL COMMENT 'Paciente cujos dados foram acedidos (nullable)',
  tabela_acedida  VARCHAR(60)  NOT NULL,
  id_registo      INT          NULL,
  acao            ENUM('SELECT','INSERT','UPDATE','DELETE','LOGIN','LOGOUT','FALHA_LOGIN')
                  NOT NULL,
  data_hora       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_origem       VARCHAR(45)  NOT NULL COMMENT 'Suporta IPv4 e IPv6',
  PRIMARY KEY (id_log),
  CONSTRAINT fk_log_utilizador
    FOREIGN KEY (id_utilizador) REFERENCES UTILIZADOR (id_utilizador)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_log_paciente
    FOREIGN KEY (id_paciente) REFERENCES PACIENTE (id_paciente)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='Auditoria de todos os acessos — imutável por design (sem UPDATE/DELETE)';


-- ============================================================
-- 4. TRIGGER — Controlo de sobreposição temporal por cama
-- ============================================================
DELIMITER $$

CREATE TRIGGER trg_cama_disponivel
BEFORE INSERT ON INTERNAMENTO
FOR EACH ROW
BEGIN
  DECLARE n_ativos INT;

  SELECT COUNT(*) INTO n_ativos
  FROM INTERNAMENTO
  WHERE id_cama = NEW.id_cama
    AND data_alta IS NULL;

  IF n_ativos > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Cama já se encontra ocupada por um internamento ativo.';
  END IF;
END$$

DELIMITER ;


-- ============================================================
-- 5. TRIGGER — Atualizar estado da cama ao dar alta
-- ============================================================
DELIMITER $$

CREATE TRIGGER trg_liberta_cama
AFTER UPDATE ON INTERNAMENTO
FOR EACH ROW
BEGIN
  IF OLD.data_alta IS NULL AND NEW.data_alta IS NOT NULL THEN
    UPDATE CAMA
    SET estado = 'disponivel'
    WHERE id_cama = NEW.id_cama;
  END IF;
END$$

DELIMITER ;


-- ============================================================
-- 6. TRIGGER — Auditoria automática de acessos a INTERNAMENTO
-- ============================================================
DELIMITER $$

CREATE TRIGGER trg_audit_internamento_update
AFTER UPDATE ON INTERNAMENTO
FOR EACH ROW
BEGIN
  INSERT INTO LOG_ACESSO
    (id_utilizador, id_paciente, tabela_acedida, id_registo, acao, ip_origem)
  VALUES
    (1, NEW.id_paciente, 'INTERNAMENTO', NEW.id_internamento, 'UPDATE', '127.0.0.1');
    -- Nota: id_utilizador e ip_origem devem ser injetados pela aplicação via variável de sessão
END$$

DELIMITER ;


-- ============================================================
-- 7. VIEWS ÚTEIS
-- ============================================================

-- Internamentos ativos com dados do paciente e cama
CREATE OR REPLACE VIEW vw_internamentos_ativos AS
SELECT
  i.id_internamento,
  p.nome                AS paciente,
  p.num_utente,
  s.nome                AS servico,
  q.numero_quarto,
  c.numero_cama,
  i.data_admissao,
  i.tipo_episodio,
  i.risco_suicidario,
  i.estado_clinico
FROM INTERNAMENTO i
JOIN PACIENTE   p ON p.id_paciente = i.id_paciente
JOIN CAMA       c ON c.id_cama     = i.id_cama
JOIN QUARTO     q ON q.id_quarto   = c.id_quarto
JOIN SERVICO    s ON s.id_servico  = q.id_servico
WHERE i.data_alta IS NULL;


-- Medicação ativa por internamento
CREATE OR REPLACE VIEW vw_medicacao_ativa AS
SELECT
  pr.id_internamento,
  p.nome        AS paciente,
  m.nome        AS medicamento,
  m.classe,
  pr.dose,
  pr.via,
  pr.frequencia,
  pr.prn,
  pr.dose_maxima_dia,
  pr.data_inicio,
  prof.nome     AS prescrito_por
FROM PRESCRICAO pr
JOIN INTERNAMENTO i  ON i.id_internamento  = pr.id_internamento
JOIN PACIENTE     p  ON p.id_paciente      = i.id_paciente
JOIN MEDICACAO    m  ON m.id_medicacao     = pr.id_medicacao
JOIN PROFISSIONAL prof ON prof.id_profissional = pr.id_profissional
WHERE pr.estado = 'ativa';


-- ============================================================
-- 8. DADOS DE EXEMPLO
-- ============================================================

INSERT INTO SERVICO (nome) VALUES ('Psiquiatria Adultos');
INSERT INTO QUARTO  (id_servico, numero_quarto) VALUES (1, '101'), (1, '102');
INSERT INTO CAMA    (id_quarto, numero_cama, estado)
  VALUES (1,'1A','disponivel'),(1,'1B','disponivel'),(2,'2A','disponivel');

INSERT INTO PERFIL_ACESSO (nome_perfil)
  VALUES ('Médico'),('Enfermeiro'),('Psicólogo'),('Administrativo');

INSERT INTO DIAGNOSTICO_DSM (codigo_dsm, codigo_icd10, nome_diagnostico, descricao)
  VALUES
  ('296.40','F31.0','Perturbação Bipolar I — Episódio Maníaco Atual',
   'Episódio maníaco atual sem características psicóticas'),
  ('296.51','F31.31','Perturbação Bipolar I — Episódio Depressivo Moderado',
   'Episódio depressivo atual de gravidade moderada'),
  ('F10.20',NULL,'Perturbação por Uso de Álcool — Grave',
   'Comorbilidade frequente na perturbação bipolar');

INSERT INTO MEDICACAO (nome, classe, dosagem, forma_farmaceutica)
  VALUES
  ('Lítio','Estabilizador de humor','400mg','comprimido'),
  ('Olanzapina','Antipsicótico atípico','10mg','comprimido'),
  ('Lorazepam','Benzodiazepina','1mg','comprimido'),
  ('Haloperidol','Antipsicótico típico','5mg','injetavel'),
  ('Valproato de sódio','Anticonvulsivante / Estabilizador','500mg','comprimido');

INSERT INTO PROFISSIONAL (nome, funcao, num_cedula)
  VALUES
  ('Dra. Ana Ferreira','medico','C-12345'),
  ('Enf. João Santos','enfermeiro','E-67890'),
  ('Dr. Rui Mendes','medico','C-11111');

INSERT INTO UTILIZADOR (id_profissional, username, password_hash)
  VALUES
  (1,'ana.ferreira','$2b$12$examplehashmedico1...'),
  (2,'joao.santos','$2b$12$examplehashenfermeiro1...'),
  (3,'rui.mendes','$2b$12$examplehashmedico2...');

INSERT INTO UTILIZADOR_PERFIL (id_utilizador, id_perfil)
  VALUES (1,1),(2,2),(3,1);

INSERT INTO PACIENTE (nome, data_nascimento, num_utente, contacto, morada)
  VALUES ('Maria Oliveira','1985-03-12','123456789','912000001','Porto, Portugal');

INSERT INTO INTERNAMENTO
  (id_paciente, id_cama, data_admissao, motivo_internamento,
   tipo_episodio, risco_suicidario, risco_agressividade, estado_clinico)
  VALUES
  (1, 1, '2025-05-01 14:30:00',
   'Episódio maníaco com comportamento desorganizado e insónia há 5 dias.',
   'maníaco','baixo','moderado','instavel');

INSERT INTO INTERNAMENTO_DIAGNOSTICO (id_internamento, id_diagnostico, tipo, data)
  VALUES (1,1,'principal','2025-05-01'),(1,3,'comorbilidade','2025-05-01');

INSERT INTO PRESCRICAO
  (id_internamento, id_profissional, id_medicacao, dose, via, frequencia,
   prn, dose_maxima_dia, data_inicio, estado)
  VALUES
  (1,1,1,'400mg','oral','12/12h',FALSE,NULL,'2025-05-01','ativa'),
  (1,1,2,'10mg','oral','1x/dia',FALSE,NULL,'2025-05-01','ativa'),
  (1,1,4,'5mg','im','SOS',TRUE,'10mg/dia','2025-05-01','ativa');

INSERT INTO OBSERVACAO_COMPORTAMENTAL
  (id_internamento, id_profissional, data_hora, humor, sono, discurso,
   atividade_psicomotora, delirio, alucinacao, adesao_terapeutica, notas_clinicas)
  VALUES
  (1,2,'2025-05-01 22:00:00','expansivo','insonia','acelerado','agitado',
   1,0,'parcial','Doente agitada, recusou jantar. Administrado haloperidol IM.');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- FIM DO SCRIPT
-- ============================================================