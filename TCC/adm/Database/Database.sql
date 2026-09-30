CREATE DATABASE Academia DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Academia;

-- ==========================================
-- 1. GESTÃO DE USUÁRIOS E ACESSO
-- ==========================================

-- Tabela unificada para autenticação e dados pessoais
CREATE TABLE Usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(11) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(15),
    sexo ENUM('M', 'F', 'O') DEFAULT 'O',
    data_nascimento DATE,
    foto_perfil VARCHAR(255) DEFAULT NULL,
    tipo_usuario ENUM('ALUNO', 'PROFESSOR') NOT NULL DEFAULT 'ALUNO',
    ativo TINYINT(1) NOT NULL DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Dados específicos do perfil de Professor
CREATE TABLE Professores (
    id_professor INT PRIMARY KEY,
    cref VARCHAR(20) UNIQUE NOT NULL, -- Registro de Educação Física
    especializacao VARCHAR(50),
    FOREIGN KEY (id_professor) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE
);

-- ==========================================
-- 3. MODALIDADES, AULAS E AGENDAMENTOS
-- ==========================================

CREATE TABLE Modalidades (
    id_modalidade INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) UNIQUE NOT NULL, -- Ex: Musculação, Crossfit, Pilates, Spinning
    descricao TEXT
);

CREATE TABLE Professor_Aluno (
    id_professor INT,
    id_aluno INT,
    PRIMARY KEY (id_professor, id_aluno),
    FOREIGN KEY (id_professor) REFERENCES Professores(id_professor) ON DELETE CASCADE,
    FOREIGN KEY (id_aluno) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE
);

CREATE TABLE Aulas (
    id_aula INT AUTO_INCREMENT PRIMARY KEY,
    id_modalidade INT NOT NULL,
    id_professor INT NOT NULL,
    nome_turma VARCHAR(100) NOT NULL,
    dia_semana ENUM('SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB', 'DOM') NOT NULL,
    horario_inicio TIME NOT NULL,
    horario_fim TIME NOT NULL,
    capacidade INT DEFAULT NULL,
    ativa TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_modalidade) REFERENCES Modalidades(id_modalidade) ON DELETE CASCADE,
    FOREIGN KEY (id_professor) REFERENCES Professores(id_professor) ON DELETE CASCADE
);
-- Presença / Agendamento do Aluno na Aula
CREATE TABLE Agendamentos_Aula (
    id_agendamento INT AUTO_INCREMENT PRIMARY KEY,
    id_aula INT NOT NULL,
    id_aluno INT NOT NULL,
    data_agendamento DATE NOT NULL,
    presenca BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_aula) REFERENCES Aulas(id_aula) ON DELETE CASCADE,
    FOREIGN KEY (id_aluno) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE,
    UNIQUE KEY uk_aula_aluno_data (id_aula, id_aluno, data_agendamento)
);

-- ==========================================
-- 4. FICHAS DE TREINO (FICHA DO ALUNO)
-- ==========================================

CREATE TABLE Exercicios (
    id_exercicio INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL, -- Ex: Supino Reto, Agachamento Livre
    grupo_muscular VARCHAR(50) NOT NULL -- Ex: Peitoral, Quadríceps
);

CREATE TABLE Avaliacoes (
    id_avaliacao INT AUTO_INCREMENT PRIMARY KEY,
    id_aluno INT NOT NULL,
    id_professor INT NOT NULL,
    peso DECIMAL(5,2) NOT NULL,
    altura DECIMAL(3,2) NOT NULL,
    imc DECIMAL(4,2) NOT NULL,
    percentual_gordura DECIMAL(4,2) NULL,
    observacoes TEXT NULL,
    data_avaliacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    CONSTRAINT fk_avaliacao_aluno FOREIGN KEY (id_aluno) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_avaliacao_professor FOREIGN KEY (id_professor) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE
);

CREATE TABLE Fichas_Treino (
    id_ficha INT AUTO_INCREMENT PRIMARY KEY,
    id_aluno INT NOT NULL,
    id_professor INT NOT NULL,
    titulo VARCHAR(100) NOT NULL, -- Ex: "Treino ABC - Hipertrofia"
    data_inicio DATE NOT NULL,
    data_fim DATE NULL,
    FOREIGN KEY (id_aluno) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_professor) REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Itens_Treino (
    id_item INT AUTO_INCREMENT PRIMARY KEY,
    id_ficha INT NOT NULL,
    id_exercicio INT NOT NULL,
    dia_semana ENUM('SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB', 'DOM') NOT NULL,
    series INT NOT NULL DEFAULT 3,
    repeticoes VARCHAR(20) NOT NULL, -- Ex: "10 a 12" ou "Até a falha"
    carga_kg DECIMAL(5,2) NULL,
    descanso_segundos INT DEFAULT 60,
    FOREIGN KEY (id_ficha) REFERENCES Fichas_Treino(id_ficha) ON DELETE CASCADE,
    FOREIGN KEY (id_exercicio) REFERENCES Exercicios(id_exercicio)
);

CREATE TABLE IF NOT EXISTS Treinos_Concluidos (
    id_registro INT AUTO_INCREMENT PRIMARY KEY,
    id_aluno INT NOT NULL,
    id_ficha INT NOT NULL,
    dia_semana ENUM('SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB', 'DOM') NOT NULL,
    data_treino DATE NOT NULL,
    concluido_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_aluno) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_ficha) REFERENCES Fichas_Treino(id_ficha) ON DELETE CASCADE,
    UNIQUE KEY uk_aluno_data (id_aluno, data_treino)
);

CREATE TABLE Notificacoes (
    id_notificacao INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,                
    id_origem INT NULL,                     
    tipo VARCHAR(30) NOT NULL,              
    titulo VARCHAR(100) NOT NULL,
    mensagem VARCHAR(255) NOT NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_origem) REFERENCES Usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_usuario (id_usuario, lida, data_criacao)
);

INSERT INTO Modalidades (nome) VALUES 
('Musculação'),
('Dança'),
('Natação');

INSERT INTO Exercicios (grupo_muscular, nome) VALUES
-- PEITO
('Peito', 'Supino Reto com Barra'),
('Peito', 'Supino Reto com Halteres'),
('Peito', 'Supino Inclinado com Halteres'),
('Peito', 'Supino Inclinado com Barra'),
('Peito', 'Crucifixo Reto / Voador'),
('Peito', 'Crossover na Polia Alta'),
('Peito', 'Crossover na Polia Baixa'),

-- COSTAS
('Costas', 'Puxada Frontal na Polia'),
('Costas', 'Remada Curvada com Barra'),
('Costas', 'Remada Sentada na Polia (Triângulo)'),
('Costas', 'Remada Unilateral com Halter (Serrote)'),
('Costas', 'Pulldown na Polia'),
('Costas', 'Remada Articulada'),

-- PERNAS (QUADRÍCEPS / POSTERIOR / GLÚTEOS)
('Pernas', 'Agachamento Livre com Barra'),
('Pernas', 'Leg Press 45°'),
('Pernas', 'Cadeira Extensora'),
('Pernas', 'Mesa Flexora'),
('Pernas', 'Cadeira Flexora'),
('Pernas', 'Stiff com Halteres / Barra'),
('Pernas', 'Afundo / Passada com Halteres'),
('Pernas', 'Elevação Pélvica'),
('Pernas', 'Cadeira Abdu his/Adutora'),
('Pernas', 'Gêmeos em Pé (Panturrilha)'),
('Pernas', 'Gêmeos Sentado (Panturrilha)'),

-- OMBROS / TRAPÉZIO
('Ombros', 'Desenvolvimento com Halteres'),
('Ombros', 'Elevação Lateral com Halteres'),
('Ombros', 'Elevação Frontal'),
('Ombros', 'Crucifixo Invertido na Máquina / Halter'),
('Ombros', 'Encolhimento de Ombros (Trapézio)'),

-- BÍCEPS
('Bíceps', 'Rosca Direta com Barra'),
('Bíceps', 'Rosca Alternada com Halteres'),
('Bíceps', 'Rosca Martelo'),
('Bíceps', 'Rosca Scott'),

-- TRÍCEPS
('Tríceps', 'Tríceps Corda na Polia'),
('Tríceps', 'Tríceps Barra Reta na Polia'),
('Tríceps', 'Tríceps Testa'),
('Tríceps', 'Tríceps Francês'),

-- ABDÓMEN
('Abdómen', 'Abdominal Supra no Solo'),
('Abdómen', 'Abdominal Infra / Elevação de Pernas'),
('Abdómen', 'Prancha Isométrica');