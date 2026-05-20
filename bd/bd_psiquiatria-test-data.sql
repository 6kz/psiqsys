-- ============================================================
--  BD Internamento Psiquiátrico — Dados de Teste
--  10 Pacientes | Cobertura total
--  Executar APÓS bd_psiquiatria.sql
-- ============================================================

USE bd_psiquiatria;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- LIMPAR DADOS ANTERIORES (mantém estrutura e catálogos)
-- ============================================================
DELETE FROM LOG_ACESSO;
DELETE FROM ALTA_CLINICA;
DELETE FROM EVENTO_CRITICO;
DELETE FROM ADMINISTRACAO_MEDICACAO;
DELETE FROM PRESCRICAO;
DELETE FROM OBSERVACAO_COMPORTAMENTAL;
DELETE FROM INTERNAMENTO_DIAGNOSTICO;
DELETE FROM INTERNAMENTO;
DELETE FROM UTILIZADOR_PERFIL;
DELETE FROM UTILIZADOR;
DELETE FROM PACIENTE;
DELETE FROM PROFISSIONAL;
DELETE FROM MEDICACAO;
DELETE FROM DIAGNOSTICO_DSM;
DELETE FROM PERFIL_ACESSO;
DELETE FROM CAMA;
DELETE FROM QUARTO;
DELETE FROM SERVICO;

-- Reset auto_increment
ALTER TABLE LOG_ACESSO               AUTO_INCREMENT = 1;
ALTER TABLE ALTA_CLINICA             AUTO_INCREMENT = 1;
ALTER TABLE EVENTO_CRITICO           AUTO_INCREMENT = 1;
ALTER TABLE ADMINISTRACAO_MEDICACAO  AUTO_INCREMENT = 1;
ALTER TABLE PRESCRICAO               AUTO_INCREMENT = 1;
ALTER TABLE OBSERVACAO_COMPORTAMENTAL AUTO_INCREMENT = 1;
ALTER TABLE INTERNAMENTO             AUTO_INCREMENT = 1;
ALTER TABLE PACIENTE                 AUTO_INCREMENT = 1;
ALTER TABLE PROFISSIONAL             AUTO_INCREMENT = 1;
ALTER TABLE MEDICACAO                AUTO_INCREMENT = 1;
ALTER TABLE DIAGNOSTICO_DSM          AUTO_INCREMENT = 1;
ALTER TABLE PERFIL_ACESSO            AUTO_INCREMENT = 1;
ALTER TABLE CAMA                     AUTO_INCREMENT = 1;
ALTER TABLE QUARTO                   AUTO_INCREMENT = 1;
ALTER TABLE SERVICO                  AUTO_INCREMENT = 1;


-- ============================================================
-- 1. SERVIÇO, QUARTOS E CAMAS
-- ============================================================
INSERT INTO SERVICO (nome) VALUES
  ('Psiquiatria Adultos'),
  ('Psiquiatria Gerontopsiquiatria');

INSERT INTO QUARTO (id_servico, numero_quarto) VALUES
  (1,'101'),(1,'102'),(1,'103'),
  (2,'201'),(2,'202');

-- Quarto 101: camas 1A,1B | 102: 2A,2B | 103: 3A,3B
-- Quarto 201: 4A,4B       | 202: 5A
INSERT INTO CAMA (id_quarto, numero_cama, estado) VALUES
  (1,'1A','disponivel'),(1,'1B','disponivel'),
  (2,'2A','disponivel'),(2,'2B','disponivel'),
  (3,'3A','disponivel'),(3,'3B','disponivel'),
  (4,'4A','disponivel'),(4,'4B','disponivel'),
  (5,'5A','disponivel');
-- id_cama: 1..9


-- ============================================================
-- 2. DIAGNÓSTICOS DSM-5 / ICD-10
-- ============================================================
INSERT INTO DIAGNOSTICO_DSM (codigo_dsm, codigo_icd10, nome_diagnostico, descricao) VALUES
  ('296.40','F31.0', 'Perturbação Bipolar I — Episódio Maníaco Atual',       'Episódio maníaco sem características psicóticas'),
  ('296.41','F31.2', 'Perturbação Bipolar I — Maníaco com Psicose',          'Episódio maníaco com características psicóticas congruentes com o humor'),
  ('296.51','F31.31','Perturbação Bipolar I — Episódio Depressivo Moderado', 'Episódio depressivo de gravidade moderada'),
  ('296.52','F31.4', 'Perturbação Bipolar I — Episódio Depressivo Grave',    'Episódio depressivo grave sem características psicóticas'),
  ('296.89','F31.81','Perturbação Bipolar II',                               'Padrão de episódios hipomaníacos e depressivos'),
  ('301.13','F34.0', 'Perturbação Ciclotímica',                              'Flutuações crónicas do humor sem critérios para episódio major'),
  ('F10.20', NULL,   'Perturbação por Uso de Álcool — Grave',                'Comorbilidade frequente na perturbação bipolar'),
  ('F19.20', NULL,   'Perturbação por Uso de Múltiplas Substâncias',         'Uso problemático de múltiplas substâncias'),
  ('300.02','F41.1', 'Perturbação de Ansiedade Generalizada',                'Ansiedade e preocupação excessivas persistentes'),
  ('309.81','F43.10','Perturbação de Stress Pós-Traumático',                 'Comorbilidade após exposição a evento traumático');
-- id_diagnostico: 1..10


-- ============================================================
-- 3. MEDICAÇÃO
-- ============================================================
INSERT INTO MEDICACAO (nome, classe, dosagem, forma_farmaceutica) VALUES
  ('Lítio',               'Estabilizador de humor',       '400mg',  'comprimido'),
  ('Valproato de sódio',  'Estabilizador / Antiepilético','500mg',  'comprimido'),
  ('Olanzapina',          'Antipsicótico atípico',         '10mg',  'comprimido'),
  ('Quetiapina',          'Antipsicótico atípico',         '200mg', 'comprimido'),
  ('Aripiprazol',         'Antipsicótico atípico',         '15mg',  'comprimido'),
  ('Haloperidol',         'Antipsicótico típico',          '5mg',   'injetavel'),
  ('Lorazepam',           'Benzodiazepina',                '1mg',   'comprimido'),
  ('Diazepam',            'Benzodiazepina',                '5mg',   'comprimido'),
  ('Sertralina',          'ISRS',                         '50mg',  'comprimido'),
  ('Lamotrigina',         'Estabilizador / Antiepilético','100mg', 'comprimido'),
  ('Clonazepam',          'Benzodiazepina',                '0.5mg', 'comprimido'),
  ('Biperideno',          'Anticolinérgico',               '2mg',   'comprimido');
-- id_medicacao: 1..12


-- ============================================================
-- 4. PERFIS DE ACESSO
-- ============================================================
INSERT INTO PERFIL_ACESSO (nome_perfil) VALUES
  ('Médico'),('Enfermeiro'),('Psicólogo'),('Administrativo');
-- id_perfil: 1..4


-- ============================================================
-- 5. PROFISSIONAIS
-- ============================================================
INSERT INTO PROFISSIONAL (nome, funcao, num_cedula) VALUES
  ('Dra. Ana Ferreira',     'medico',      'C-10001'),
  ('Dr. Rui Mendes',        'medico',      'C-10002'),
  ('Dra. Sofia Carvalho',   'medico',      'C-10003'),
  ('Enf. João Santos',      'enfermeiro',  'E-20001'),
  ('Enf. Carla Rodrigues',  'enfermeiro',  'E-20002'),
  ('Enf. Miguel Fonseca',   'enfermeiro',  'E-20003'),
  ('Psi. Beatriz Lima',     'psicologo',   'P-30001'),
  ('Adm. Teresa Neves',     'administrativo','A-40001');
-- id_profissional: 1..8


-- ============================================================
-- 6. UTILIZADORES
-- ============================================================
INSERT INTO UTILIZADOR (id_profissional, username, password_hash) VALUES
  (1,'ana.ferreira',   '$2b$12$testHashMedico1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'),
  (2,'rui.mendes',     '$2b$12$testHashMedico2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'),
  (3,'sofia.carvalho', '$2b$12$testHashMedico3xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'),
  (4,'joao.santos',    '$2b$12$testHashEnf1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'),
  (5,'carla.rodrigues','$2b$12$testHashEnf2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'),
  (6,'miguel.fonseca', '$2b$12$testHashEnf3xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'),
  (7,'beatriz.lima',   '$2b$12$testHashPsi1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'),
  (8,'teresa.neves',   '$2b$12$testHashAdm1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
-- id_utilizador: 1..8

INSERT INTO UTILIZADOR_PERFIL (id_utilizador, id_perfil) VALUES
  (1,1),(2,1),(3,1),     -- médicos
  (4,2),(5,2),(6,2),     -- enfermeiros
  (7,3),                 -- psicóloga
  (8,4);                 -- administrativo


-- ============================================================
-- 7. PACIENTES (10)
-- ============================================================
INSERT INTO PACIENTE (nome, data_nascimento, num_utente, contacto, morada) VALUES
  ('Maria João Oliveira',  '1985-03-12','100000001','912111001','Rua das Flores 10, Porto'),
  ('António Pereira Silva','1972-07-24','100000002','912111002','Av. da Liberdade 55, Lisboa'),
  ('Filipa Costa Marques', '1993-11-05','100000003','912111003','Rua do Almada 33, Porto'),
  ('Carlos Manuel Sousa',  '1968-01-30','100000004','912111004','Travessa da Sé 7, Braga'),
  ('Rita Fernandes Lopes', '1990-09-18','100000005','912111005','Rua de Santa Catarina 120, Porto'),
  ('Fernando Gomes Ramos', '1980-05-14','100000006','912111006','Av. dos Aliados 88, Porto'),
  ('Inês Rodrigues Pinto', '1975-12-02','100000007','912111007','Rua Direita 45, Coimbra'),
  ('Luís Alberto Monteiro','1960-08-09','100000008','912111008','Rua Nova 22, Guimarães'),
  ('Ana Beatriz Nunes',    '2000-02-28','100000009','912111009','Rua do Bonjardim 67, Porto'),
  ('Pedro Jorge Azevedo',  '1955-06-17','100000010','912111010','Largo do Paço 3, Viana do Castelo');
-- id_paciente: 1..10


-- ============================================================
-- 8. INTERNAMENTOS
-- ============================================================
-- Ativos: pacientes 1,3,5,6,9
-- Concluídos: pacientes 2,4,7,8,10

INSERT INTO INTERNAMENTO
  (id_paciente, id_cama, data_admissao, motivo_internamento,
   tipo_episodio, risco_suicidario, risco_agressividade, estado_clinico, data_alta)
VALUES
-- ATIVOS
(1, 1,'2025-05-01 14:30:00','Episódio maníaco com agitação, insónia há 5 dias e comportamento desorganizado.',
  'maníaco','baixo','moderado','estabilizando', NULL),

(3, 2,'2025-05-05 09:15:00','Episódio depressivo grave com ideação suicida passiva e recusa alimentar.',
  'depressivo','elevado','nenhum','instavel', NULL),

(5, 3,'2025-05-08 16:45:00','Episódio misto com labilidade emocional intensa e comportamento impulsivo.',
  'misto','moderado','baixo','instavel', NULL),

(6, 4,'2025-05-10 11:00:00','Episódio maníaco com psicose. Delírios de grandiosidade e alucinações auditivas.',
  'maníaco','nenhum','elevado','instavel', NULL),

(9, 5,'2025-05-12 08:30:00','Primeiro episódio psicótico em contexto de humor expansivo. Estudo diagnóstico.',
  'maníaco','baixo','baixo','instavel', NULL),

-- CONCLUÍDOS
(2, 6,'2025-03-10 10:00:00','Episódio depressivo moderado. Sem resposta ambulatória a antidepressivos.',
  'depressivo','moderado','nenhum','estavel','2025-03-28 11:00:00'),

(4, 7,'2025-02-14 13:30:00','Episódio maníaco com insónia total, gastos excessivos e fuga de ideias.',
  'maníaco','nenhum','baixo','estavel','2025-03-01 10:00:00'),

(7, 8,'2025-01-20 09:00:00','Descompensação hipomaníaca com abandono de medicação.',
  'hipomaníaco','nenhum','nenhum','estavel','2025-02-03 14:00:00'),

(8, 9,'2024-11-05 17:00:00','Episódio depressivo grave com tentativa de autoagressão prévia.',
  'depressivo','elevado','nenhum','estavel','2024-11-30 10:00:00'),

(10,6,'2024-12-01 08:00:00','Episódio maníaco misto em doente com longa história de bipolar tipo I.',
  'misto','moderado','moderado','estavel','2024-12-22 15:00:00');
-- id_internamento: 1..10


-- ============================================================
-- 9. DIAGNÓSTICOS POR INTERNAMENTO
-- ============================================================
INSERT INTO INTERNAMENTO_DIAGNOSTICO (id_internamento, id_diagnostico, tipo, data) VALUES
-- Internamento 1 (Maria, maníaco)
(1,1,'principal','2025-05-01'),(1,7,'comorbilidade','2025-05-02'),
-- Internamento 2 (António, depressivo)
(2,3,'principal','2025-03-10'),(2,9,'comorbilidade','2025-03-11'),
-- Internamento 3 (Filipa, depressivo grave)
(3,4,'principal','2025-05-05'),(3,9,'comorbilidade','2025-05-06'),
-- Internamento 4 (Carlos, maníaco)
(4,1,'principal','2025-02-14'),
-- Internamento 5 (Rita, misto)
(5,1,'principal','2025-05-08'),(5,9,'comorbilidade','2025-05-09'),
-- Internamento 6 (Fernando, maníaco com psicose)
(6,2,'principal','2025-05-10'),(6,8,'comorbilidade','2025-05-10'),
-- Internamento 7 (Inês, hipomaníaco)
(7,5,'principal','2025-01-20'),
-- Internamento 8 (Luís, depressivo grave)
(8,4,'principal','2024-11-05'),(8,10,'comorbilidade','2024-11-06'),
-- Internamento 9 (Ana, maníaco)
(9,2,'principal','2025-05-12'),
-- Internamento 10 (Pedro, misto)
(10,1,'principal','2024-12-01'),(10,7,'comorbilidade','2024-12-02');


-- ============================================================
-- 10. PRESCRIÇÕES
-- ============================================================
INSERT INTO PRESCRICAO
  (id_internamento, id_profissional, id_medicacao, dose, via, frequencia,
   prn, dose_maxima_dia, data_inicio, data_fim, estado)
VALUES
-- Internamento 1 (Maria) — ativo
(1,1,1,'400mg','oral','12/12h',FALSE,NULL,'2025-05-01',NULL,'ativa'),
(1,1,3,'10mg','oral','1x/dia (noite)',FALSE,NULL,'2025-05-01',NULL,'ativa'),
(1,1,6,'5mg','im','SOS agitação',TRUE,'10mg/dia','2025-05-01',NULL,'ativa'),

-- Internamento 2 (António) — concluído
(2,2,4,'200mg','oral','12/12h',FALSE,NULL,'2025-03-10','2025-03-28','concluida'),
(2,2,9,'50mg','oral','1x/dia (manhã)',FALSE,NULL,'2025-03-12','2025-03-28','concluida'),
(2,2,7,'1mg','oral','SOS ansiedade',TRUE,'2mg/dia','2025-03-10','2025-03-28','concluida'),

-- Internamento 3 (Filipa) — ativo
(3,3,2,'500mg','oral','12/12h',FALSE,NULL,'2025-05-05',NULL,'ativa'),
(3,3,4,'100mg','oral','1x/dia (noite)',FALSE,NULL,'2025-05-05',NULL,'ativa'),
(3,3,11,'0.5mg','oral','SOS insónia',TRUE,'1mg/dia','2025-05-05',NULL,'ativa'),

-- Internamento 4 (Carlos) — concluído
(4,1,1,'400mg','oral','8/8h',FALSE,NULL,'2025-02-14','2025-03-01','concluida'),
(4,1,3,'5mg','oral','1x/dia (noite)',FALSE,NULL,'2025-02-14','2025-03-01','concluida'),

-- Internamento 5 (Rita) — ativo
(5,2,2,'750mg','oral','12/12h',FALSE,NULL,'2025-05-08',NULL,'ativa'),
(5,2,3,'10mg','oral','1x/dia (noite)',FALSE,NULL,'2025-05-08',NULL,'ativa'),
(5,2,7,'1mg','oral','SOS agitação',TRUE,'3mg/dia','2025-05-08',NULL,'ativa'),

-- Internamento 6 (Fernando) — ativo
(6,1,3,'20mg','oral','1x/dia',FALSE,NULL,'2025-05-10',NULL,'ativa'),
(6,1,2,'500mg','oral','12/12h',FALSE,NULL,'2025-05-10',NULL,'ativa'),
(6,1,6,'5mg','im','SOS agitação grave',TRUE,'15mg/dia','2025-05-10',NULL,'ativa'),
(6,1,12,'2mg','oral','12/12h (anti-EPS)',FALSE,NULL,'2025-05-10',NULL,'ativa'),

-- Internamento 7 (Inês) — concluído
(7,3,10,'100mg','oral','1x/dia',FALSE,NULL,'2025-01-20','2025-02-03','concluida'),
(7,3,4,'50mg','oral','1x/dia (noite)',FALSE,NULL,'2025-01-20','2025-02-03','concluida'),

-- Internamento 8 (Luís) — concluído
(8,2,2,'500mg','oral','12/12h',FALSE,NULL,'2024-11-05','2024-11-30','concluida'),
(8,2,4,'300mg','oral','1x/dia (noite)',FALSE,NULL,'2024-11-05','2024-11-30','concluida'),
(8,2,7,'1mg','oral','SOS',TRUE,'2mg/dia','2024-11-05','2024-11-30','concluida'),

-- Internamento 9 (Ana) — ativo
(9,3,3,'10mg','oral','1x/dia',FALSE,NULL,'2025-05-12',NULL,'ativa'),
(9,3,2,'500mg','oral','12/12h',FALSE,NULL,'2025-05-12',NULL,'ativa'),

-- Internamento 10 (Pedro) — concluído
(10,1,1,'600mg','oral','12/12h',FALSE,NULL,'2024-12-01','2024-12-22','concluida'),
(10,1,5,'15mg','oral','1x/dia',FALSE,NULL,'2024-12-01','2024-12-22','concluida'),
(10,1,6,'5mg','im','SOS',TRUE,'10mg/dia','2024-12-01','2024-12-22','concluida');


-- ============================================================
-- 11. ADMINISTRAÇÕES DE MEDICAÇÃO
-- ============================================================
INSERT INTO ADMINISTRACAO_MEDICACAO
  (id_prescricao, id_internamento, id_profissional, data_hora,
   administrada, motivo_nao_administracao, efeitos_adversos)
VALUES
-- Internamento 1 (Maria) — prescrições 1,2,3
(1,1,4,'2025-05-01 22:00:00',TRUE,NULL,NULL),
(1,1,4,'2025-05-02 08:00:00',TRUE,NULL,NULL),
(2,1,4,'2025-05-02 22:00:00',TRUE,NULL,'Sonolência matinal referida'),
(3,1,4,'2025-05-02 03:00:00',TRUE,NULL,'Sedação eficaz em 20 min'),
(1,1,5,'2025-05-02 22:00:00',TRUE,NULL,NULL),
(1,1,5,'2025-05-03 08:00:00',TRUE,NULL,NULL),
(2,1,5,'2025-05-03 22:00:00',FALSE,'Doente recusou medicação oral',''),

-- Internamento 3 (Filipa) — prescrições 7,8,9
(7,3,6,'2025-05-05 22:00:00',TRUE,NULL,NULL),
(8,3,6,'2025-05-05 22:00:00',TRUE,NULL,'Sedação moderada'),
(9,3,6,'2025-05-06 02:00:00',TRUE,NULL,NULL),
(7,3,4,'2025-05-06 08:00:00',TRUE,NULL,NULL),
(7,3,4,'2025-05-06 22:00:00',FALSE,'Doente adormecida, não acordar per protocolo',''),

-- Internamento 5 (Rita) — prescrições 12,13,14
(12,5,5,'2025-05-08 22:00:00',TRUE,NULL,NULL),
(13,5,5,'2025-05-08 22:00:00',TRUE,NULL,NULL),
(14,5,5,'2025-05-09 10:30:00',TRUE,NULL,'Doente mais calma após 30 min'),
(12,5,5,'2025-05-09 08:00:00',TRUE,NULL,NULL),

-- Internamento 6 (Fernando) — prescrições 15,16,17,18
(15,6,4,'2025-05-10 22:00:00',TRUE,NULL,NULL),
(16,6,4,'2025-05-10 22:00:00',TRUE,NULL,NULL),
(17,6,4,'2025-05-11 01:00:00',TRUE,NULL,'Necessária contenção física prévia. Haloperidol IM eficaz.'),
(18,6,4,'2025-05-11 08:00:00',TRUE,NULL,NULL),
(15,6,6,'2025-05-11 22:00:00',TRUE,NULL,NULL),

-- Internamento 8 (Luís) — prescrições 21,22,23
(21,8,5,'2024-11-05 22:00:00',TRUE,NULL,NULL),
(22,8,5,'2024-11-05 22:00:00',TRUE,NULL,'Ligeira sedação matinal'),
(21,8,5,'2024-11-06 08:00:00',TRUE,NULL,NULL),
(21,8,5,'2024-11-06 22:00:00',TRUE,NULL,NULL);


-- ============================================================
-- 12. OBSERVAÇÕES COMPORTAMENTAIS
-- ============================================================
INSERT INTO OBSERVACAO_COMPORTAMENTAL
  (id_internamento, id_profissional, data_hora, humor, sono, discurso,
   atividade_psicomotora, delirio, alucinacao, adesao_terapeutica, notas_clinicas)
VALUES
-- Maria (internamento 1) — evolução maníaca → estabilizando
(1,4,'2025-05-01 22:00:00','expansivo','insonia','acelerado','agitado',1,0,'parcial',
  'Doente agitada, discurso acelerado ininterrupto. Recusou jantar. Haloperidol IM administrado com boa resposta.'),
(1,5,'2025-05-02 08:00:00','expansivo','insonia','acelerado','agitado',1,0,'parcial',
  'Dormiu apenas 2h. Mantém discurso acelerado. Aceitou pequeno-almoço com persuasão.'),
(1,4,'2025-05-02 22:00:00','irritavel','insonia','acelerado','agitado',0,0,'total',
  'Mais irritável, menos expansiva. Aceitou medicação oral. Ligeira melhoria.'),
(1,6,'2025-05-03 08:00:00','irritavel','fragmentado','normal','normal',0,0,'total',
  'Dormiu 5h. Discurso dentro da normalidade. Colaborante. Boa adesão terapêutica.'),
(1,5,'2025-05-04 08:00:00','eutimico','normal','normal','normal',0,0,'total',
  'Franca melhoria. Doente descansa, participou em atividade de grupo.'),

-- Filipa (internamento 3) — depressivo grave com risco suicida
(3,6,'2025-05-05 22:00:00','deprimido','insonia','lento','retardado',0,0,'parcial',
  'Doente apática, olhar vago. Responde em monossílabos. Recusou jantar. Mantém ideação suicida passiva — sem plano. Vigilância reforçada.'),
(3,4,'2025-05-06 08:00:00','deprimido','insonia','lento','retardado',0,0,'parcial',
  'Não saiu da cama. Recusa higiene. Chorou durante avaliação. Sem alterações de perceção. Risco mantido.'),
(3,5,'2025-05-07 08:00:00','deprimido','fragmentado','lento','retardado',0,0,'total',
  'Aceitou tomar medicação sem resistência. Dormiu 4h fragmentadas. Verbaliza não querer morrer hoje.'),
(3,6,'2025-05-08 08:00:00','ansioso','fragmentado','normal','retardado',0,0,'total',
  'Ligeira melhoria do humor. Aceitou pequeno-almoço. Participou em sessão com psicóloga.'),

-- Fernando (internamento 6) — maníaco com psicose
(6,4,'2025-05-10 22:00:00','expansivo','insonia','acelerado','agitado',3,2,'recusa',
  'Doente muito agitado. Refere ser "escolhido para uma missão divina". Alucinações auditivas confirmadas. Recusa toda a medicação oral. Haloperidol IM e contenção física necessária.'),
(6,6,'2025-05-11 08:00:00','expansivo','insonia','acelerado','agitado',2,1,'parcial',
  'Ligeira atenuação dos delírios após haloperidol. Alucinações persistem. Aceitou medicação oral esta manhã.'),
(6,5,'2025-05-12 08:00:00','irritavel','fragmentado','acelerado','agitado',2,1,'parcial',
  'Discurso menos fragmentado. Mantém alguma grandiosidade. Dormiu 3h. Colaboração parcial.'),

-- Rita (internamento 5) — misto
(5,5,'2025-05-08 22:00:00','labil','insonia','acelerado','agitado',0,0,'parcial',
  'Oscilações rápidas entre choro e euforia. Impulsividade marcada. Recusou jantar.'),
(5,4,'2025-05-09 08:00:00','labil','fragmentado','normal','normal',0,0,'total',
  'Mais calma após lorazepam SOS. Dormiu 4h. Aceitou pequeno-almoço e medicação.'),

-- Ana (internamento 9) — primeiro episódio
(9,6,'2025-05-12 22:00:00','expansivo','insonia','acelerado','agitado',1,2,'parcial',
  'Primeiro internamento. Família muito ansiosa. Doente com discurso acelerado, relata ouvir vozes que "a elogiam". Sem insight para a doença.'),

-- Luís (internamento 8 — concluído)
(8,5,'2024-11-05 22:00:00','deprimido','insonia','lento','retardado',0,0,'parcial',
  'Doente prostrado. Antecedentes de autoagressão. Vigilância horária noturna iniciada.'),
(8,4,'2024-11-15 08:00:00','deprimido','fragmentado','normal','retardado',0,0,'total',
  'Melhoria progressiva com valproato. Já sorri ocasionalmente. Dorme 6h.'),
(8,5,'2024-11-25 08:00:00','eutimico','normal','normal','normal',0,0,'total',
  'Critérios de alta preenchidos. Alta planeada para dia 30.');


-- ============================================================
-- 13. EVENTOS CRÍTICOS
-- ============================================================
INSERT INTO EVENTO_CRITICO
  (id_internamento, id_profissional, data_hora, tipo_evento,
   descricao, intervencao_realizada, gravidade)
VALUES
-- Filipa (internamento 3) — ideação suicida + recusa alimentar
(3,4,'2025-05-06 14:00:00','autoagressao',
  'Doente encontrada na casa de banho com objeto cortante (tampa de lata). Sem lesões.',
  'Objeto removido. Avaliação psiquiátrica urgente. Vigilância individualizada iniciada. Comunicado médico de guardia.',
  'elevada'),

(3,5,'2025-05-07 07:30:00','recusa_alimentar',
  'Terceiro dia consecutivo com recusa alimentar. Peso com descida de 1.5kg.',
  'Nutrição entérica por SNG ponderada. Médica notificada. Reforço de persuasão com sucesso parcial ao jantar.',
  'moderada'),

-- Fernando (internamento 6) — agressividade
(6,4,'2025-05-10 23:30:00','heteroagressao',
  'Doente tentou agredir enfermeiro durante administração de medicação. Atirou objetos do quarto.',
  'Contenção física por 3 elementos. Haloperidol IM 5mg administrado. Quarto isolado. Comunicado ao médico de urgência.',
  'elevada'),

(6,6,'2025-05-11 15:00:00','outro',
  'Doente tentou sair do serviço alegando estar "em missão". Família no exterior do hospital.',
  'Porta de serviço trancada. Doente reencaminhado ao quarto com apoio de dois enfermeiros. Médica informada.',
  'moderada'),

-- Maria (internamento 1) — agitação noturna
(1,4,'2025-05-02 03:00:00','outro',
  'Doente acordou doentes do quarto vizinho com discurso alto e música a alto volume.',
  'Haloperidol IM SOS administrado. Doente retirada para quarto individual. Acalmou em 20 minutos.',
  'baixa'),

-- Luís (internamento 8 — concluído)
(8,5,'2024-11-07 02:00:00','autoagressao',
  'Doente encontrado com ligaduras do colchão enroladas no pescoço. Sem lesões graves.',
  'Objeto removido imediatamente. Médico chamado. Transferência para quarto com vigilância contínua. Psiquiatra de urgência avaliou.',
  'critica');


-- ============================================================
-- 14. ALTAS CLÍNICAS (internamentos concluídos: 6,7,8,9,10)
-- ============================================================
INSERT INTO ALTA_CLINICA
  (id_internamento, id_profissional, id_diagnostico_final, data_alta,
   plano_pos_alta, encaminhamento)
VALUES
-- António (internamento 2)
(2,2,3,'2025-03-28 11:00:00',
  'Manter quetiapina 200mg 12/12h e sertralina 50mg. Consulta de psiquiatria em 2 semanas. Psicoeducação familiar realizada.',
  'consulta_externa'),

-- Carlos (internamento 4)
(4,1,1,'2025-03-01 10:00:00',
  'Alta com lítio 400mg 8/8h. Doseamento de litemia em 7 dias. Consulta de psiquiatria em 10 dias. Doente com boa adesão.',
  'consulta_externa'),

-- Inês (internamento 7)
(7,3,5,'2025-02-03 14:00:00',
  'Alta com lamotrigina 100mg e quetiapina 50mg noite. Retomar acompanhamento em hospital de dia. Reforçar importância da adesão.',
  'hospital_dia'),

-- Luís (internamento 8)
(8,2,4,'2024-11-30 10:00:00',
  'Alta com valproato 500mg 12/12h e quetiapina 300mg noite. Seguimento em consulta externa semanal. Plano de segurança elaborado com o doente.',
  'consulta_externa'),

-- Pedro (internamento 10)
(10,1,1,'2024-12-22 15:00:00',
  'Alta com lítio 600mg 12/12h e aripiprazol 15mg. Consulta em 2 semanas. Família instruída sobre sinais de alerta.',
  'consulta_externa');


-- ============================================================
-- 15. LOGS DE ACESSO
-- ============================================================
INSERT INTO LOG_ACESSO
  (id_utilizador, id_paciente, tabela_acedida, id_registo, acao, data_hora, ip_origem)
VALUES
(1,1,'INTERNAMENTO',1,'SELECT','2025-05-01 14:35:00','192.168.1.10'),
(1,1,'INTERNAMENTO',1,'INSERT','2025-05-01 14:36:00','192.168.1.10'),
(4,1,'OBSERVACAO_COMPORTAMENTAL',1,'INSERT','2025-05-01 22:05:00','192.168.1.21'),
(4,1,'ADMINISTRACAO_MEDICACAO',3,'INSERT','2025-05-02 03:05:00','192.168.1.21'),
(3,3,'INTERNAMENTO',3,'INSERT','2025-05-05 09:20:00','192.168.1.11'),
(6,3,'OBSERVACAO_COMPORTAMENTAL',6,'INSERT','2025-05-05 22:10:00','192.168.1.23'),
(6,3,'EVENTO_CRITICO',1,'INSERT','2025-05-06 14:05:00','192.168.1.23'),
(1,6,'INTERNAMENTO',6,'INSERT','2025-05-10 11:05:00','192.168.1.10'),
(4,6,'EVENTO_CRITICO',3,'INSERT','2025-05-10 23:35:00','192.168.1.21'),
(8,NULL,'PACIENTE',NULL,'SELECT','2025-05-11 09:00:00','192.168.1.30'),
(1,NULL,'INTERNAMENTO',NULL,'SELECT','2025-05-11 10:00:00','192.168.1.10'),
-- Tentativa de login falhada (teste de segurança)
(8,NULL,'UTILIZADOR',8,'FALHA_LOGIN','2025-05-11 03:22:00','10.0.0.99'),
(8,NULL,'UTILIZADOR',8,'FALHA_LOGIN','2025-05-11 03:22:15','10.0.0.99'),
(8,NULL,'UTILIZADOR',8,'FALHA_LOGIN','2025-05-11 03:22:31','10.0.0.99'),
-- Logins normais
(2,NULL,'UTILIZADOR',2,'LOGIN','2025-05-12 08:00:00','192.168.1.12'),
(5,NULL,'UTILIZADOR',5,'LOGIN','2025-05-12 07:55:00','192.168.1.22'),
(2,2,'ALTA_CLINICA',1,'INSERT','2025-03-28 11:05:00','192.168.1.12'),
(2,4,'ALTA_CLINICA',2,'INSERT','2025-03-01 10:05:00','192.168.1.12');


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- QUERIES DE VERIFICAÇÃO
-- ============================================================

-- Ver todos os internamentos ativos
SELECT * FROM vw_internamentos_ativos;

-- Ver medicação ativa por internamento
SELECT * FROM vw_medicacao_ativa;

-- Contar registos por tabela
SELECT 'PACIENTE'                   AS tabela, COUNT(*) AS total FROM PACIENTE
UNION ALL SELECT 'INTERNAMENTO',      COUNT(*) FROM INTERNAMENTO
UNION ALL SELECT 'PRESCRICAO',        COUNT(*) FROM PRESCRICAO
UNION ALL SELECT 'OBSERVACAO',        COUNT(*) FROM OBSERVACAO_COMPORTAMENTAL
UNION ALL SELECT 'ADMINISTRACAO',     COUNT(*) FROM ADMINISTRACAO_MEDICACAO
UNION ALL SELECT 'EVENTO_CRITICO',    COUNT(*) FROM EVENTO_CRITICO
UNION ALL SELECT 'ALTA_CLINICA',      COUNT(*) FROM ALTA_CLINICA
UNION ALL SELECT 'LOG_ACESSO',        COUNT(*) FROM LOG_ACESSO;

-- Falhas de login suspeitas (3 seguidas do mesmo IP)
SELECT ip_origem, COUNT(*) AS tentativas, MIN(data_hora) AS primeira, MAX(data_hora) AS ultima
FROM LOG_ACESSO
WHERE acao = 'FALHA_LOGIN'
GROUP BY ip_origem
HAVING COUNT(*) >= 3;

-- ============================================================
-- FIM DOS DADOS DE TESTE
-- ============================================================
