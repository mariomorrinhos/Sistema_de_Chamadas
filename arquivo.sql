CREATE DATABASE IF NOT EXISTS NOME_DO_BANCO_DE_DADOS CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE NOME_DO_BANCO_DE_DADOS;

-- Desativa checagem de chaves estrangeiras para permitir a criação/recriação na ordem correta
SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================
-- 1. TABELA DE USUÁRIOS
-- ==========================================
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE
);

-- Inserir APENAS o administrador padrão (Senha: 12345)
INSERT INTO usuarios (nome, email, senha, is_admin) 
VALUES (
    'Administrador do Sistema', 
    'admin@admin.com', 
    '$2y$10$3nkHPMgWAwhfsjK3D0vIOO9QWlClVW3gGuNQwO19opZFkg0tv50pa', 
    TRUE
);

-- ==========================================
-- 2. TABELA DE GRUPOS (SERVIÇOS)
-- ==========================================
DROP TABLE IF EXISTS grupos;
CREATE TABLE grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL
);

-- ==========================================
-- 3. TABELA DE MESAS (BALCÕES)
-- ==========================================
DROP TABLE IF EXISTS mesas;
CREATE TABLE mesas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    id_usuario_atual INT DEFAULT NULL,
    id_grupo_atual INT DEFAULT NULL,
    FOREIGN KEY (id_usuario_atual) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (id_grupo_atual) REFERENCES grupos(id) ON DELETE SET NULL
);

-- ==========================================
-- 4. TABELA DE FILA (CIDADÃOS AGUARDANDO)
-- ==========================================
DROP TABLE IF EXISTS fila;
CREATE TABLE fila (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_grupo INT NOT NULL,
    nome_pessoa VARCHAR(100) NOT NULL,
    status ENUM('aguardando', 'chamado') DEFAULT 'aguardando',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_grupo) REFERENCES grupos(id) ON DELETE CASCADE
);

-- ==========================================
-- 5. TABELA DE CHAMADAS (HISTÓRICO NO PAINEL)
-- ==========================================
DROP TABLE IF EXISTS chamadas;
CREATE TABLE chamadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_fila INT NOT NULL,
    id_usuario INT NOT NULL,
    id_mesa INT NOT NULL,
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    visivel BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_fila) REFERENCES fila(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_mesa) REFERENCES mesas(id) ON DELETE CASCADE
);

-- ==========================================
-- 6. TABELA DE PONTO ELETRÔNICO
-- ==========================================
DROP TABLE IF EXISTS ponto_eletronico;
CREATE TABLE ponto_eletronico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    hora_entrada DATETIME NOT NULL,
    hora_saida DATETIME DEFAULT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Reativa a checagem de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 1;
