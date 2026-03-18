-- ============================================================
-- IPCA - Sistema de Gestão Académica
-- Script de criação da base de dados
-- ============================================================

CREATE DATABASE IF NOT EXISTS `ipca` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ipca`;

-- ============================================================
-- TABELAS
-- ============================================================

-- Grupos / Perfis de utilizador
CREATE TABLE IF NOT EXISTS `grupos` (
    `ID` int(11) NOT NULL AUTO_INCREMENT,
    `GRUPO` varchar(20) NOT NULL,
    PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Utilizadores (autenticação)
CREATE TABLE IF NOT EXISTS `users` (
    `login` varchar(20) NOT NULL,
    `pwd` varchar(250) NOT NULL,
    `grupo` int(11) NOT NULL,
    PRIMARY KEY (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Cursos
CREATE TABLE IF NOT EXISTS `cursos` (
    `ID` int(11) NOT NULL AUTO_INCREMENT,
    `Nome` text NOT NULL,
    `ativo` tinyint(4) DEFAULT 1,
    `descricao` text DEFAULT NULL,
    PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Disciplinas (Unidades Curriculares)
CREATE TABLE IF NOT EXISTS `disciplinas` (
    `ID` int(11) NOT NULL AUTO_INCREMENT,
    `Nome_disc` text NOT NULL,
    PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Plano de Estudos (associação Curso ↔ Disciplina com ano/semestre)
CREATE TABLE IF NOT EXISTS `plano_estudos` (
    `CURSOS` int(11) NOT NULL,
    `DISCIPLINA` int(11) NOT NULL,
    `semestre` int(11) NOT NULL DEFAULT 1,
    `ano` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ficha de Aluno
CREATE TABLE IF NOT EXISTS `ficha_aluno` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `login` varchar(20) NOT NULL,
    `nome_completo` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `telefone` varchar(20) DEFAULT NULL,
    `morada` text DEFAULT NULL,
    `data_nascimento` date DEFAULT NULL,
    `foto` varchar(255) DEFAULT NULL,
    `curso_id` int(11) DEFAULT NULL,
    `estado` enum('rascunho','submetida','aprovada','rejeitada') DEFAULT 'rascunho',
    `observacoes` text DEFAULT NULL,
    `data_submissao` datetime DEFAULT NULL,
    `data_validacao` datetime DEFAULT NULL,
    `validado_por` varchar(20) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_email` (`email`),
    KEY `login` (`login`),
    KEY `curso_id` (`curso_id`),
    CONSTRAINT `ficha_aluno_ibfk_1` FOREIGN KEY (`login`) REFERENCES `users` (`login`) ON DELETE CASCADE,
    CONSTRAINT `ficha_aluno_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Épocas de Avaliação
CREATE TABLE IF NOT EXISTS `epocas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome` varchar(50) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Pedidos de Matrícula/Inscrição
CREATE TABLE IF NOT EXISTS `pedidos_matricula` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `login_aluno` varchar(20) NOT NULL,
    `curso_id` int(11) NOT NULL,
    `curso_id2` int(11) DEFAULT NULL,
    `curso_id3` int(11) DEFAULT NULL,
    `data_pedido` datetime DEFAULT current_timestamp(),
    `estado` enum('pendente','aprovado','rejeitado') DEFAULT 'pendente',
    `observacoes` text DEFAULT NULL,
    `data_decisao` datetime DEFAULT NULL,
    `decisor_login` varchar(20) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `login_aluno` (`login_aluno`),
    KEY `curso_id` (`curso_id`),
    KEY `decisor_login` (`decisor_login`),
    CONSTRAINT `pedidos_matricula_ibfk_1` FOREIGN KEY (`login_aluno`) REFERENCES `users` (`login`) ON DELETE CASCADE,
    CONSTRAINT `pedidos_matricula_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`ID`) ON DELETE CASCADE,
    CONSTRAINT `pedidos_matricula_ibfk_3` FOREIGN KEY (`decisor_login`) REFERENCES `users` (`login`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Pautas de Avaliação
CREATE TABLE IF NOT EXISTS `pautas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `disciplina_id` int(11) NOT NULL,
    `epoca_id` int(11) NOT NULL,
    `ano_letivo` varchar(9) NOT NULL,
    `data_criacao` datetime DEFAULT current_timestamp(),
    `criado_por` varchar(20) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_pauta` (`disciplina_id`,`epoca_id`,`ano_letivo`),
    KEY `epoca_id` (`epoca_id`),
    KEY `criado_por` (`criado_por`),
    CONSTRAINT `pautas_ibfk_1` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`ID`) ON DELETE CASCADE,
    CONSTRAINT `pautas_ibfk_2` FOREIGN KEY (`epoca_id`) REFERENCES `epocas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `pautas_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `users` (`login`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Notas (avaliações individuais)
CREATE TABLE IF NOT EXISTS `notas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `pauta_id` int(11) NOT NULL,
    `aluno_login` varchar(20) NOT NULL,
    `nota` decimal(4,1) DEFAULT NULL,
    `data_lancamento` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `lancado_por` varchar(20) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_nota` (`pauta_id`,`aluno_login`),
    KEY `aluno_login` (`aluno_login`),
    KEY `lancado_por` (`lancado_por`),
    CONSTRAINT `notas_ibfk_1` FOREIGN KEY (`pauta_id`) REFERENCES `pautas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `notas_ibfk_2` FOREIGN KEY (`aluno_login`) REFERENCES `users` (`login`) ON DELETE CASCADE,
    CONSTRAINT `notas_ibfk_3` FOREIGN KEY (`lancado_por`) REFERENCES `users` (`login`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- DADOS INICIAIS
-- ============================================================

-- Grupos (perfis)
INSERT INTO `grupos` (`ID`, `GRUPO`) VALUES
(1, 'ADMIN'),
(2, 'ALUNO'),
(3, 'FUNCIONARIO');

-- Utilizadores padrão
-- Passwords: gestor1 → gestor1 | Funcionario1 → Funcionario1 | aluno1 → aluno1
INSERT INTO `users` (`login`, `pwd`, `grupo`) VALUES
('gestor1', '$2y$10$hExAPmgV5rV4UpUgZsXX2eLP6MUeaCzixn0dXKYxkl2tJhc6dZBom', 1),
('Funcionario1', '$2y$10$smKsNe5bE73/S4GKI1paL.IHjmklNkcX0flw/jrGDrES/7Sm63QN2', 3),
('aluno1', '$2y$10$Nz2fMI9FB73x/1ntTgMkFu1VPG3xT8qG6HxBmrMeyAr67ZqWgK77u', 2);

-- Épocas de avaliação
INSERT INTO `epocas` (`id`, `nome`) VALUES
(1, 'Normal'),
(2, 'Recurso'),
(3, 'Especial');

-- Cursos
INSERT INTO `cursos` (`ID`, `Nome`, `ativo`, `descricao`) VALUES
(1, 'Desenvolvimento Web e Multimédia', 1, 'Formação em desenvolvimento de aplicações web, design interativo e produção de conteúdos multimédia.'),
(2, 'Comércio Eletrónico', 1, 'Estratégias de negócio digital, marketing online e gestão de plataformas de e-commerce.'),
(3, 'Redes de Computadores', 1, 'Administração de redes, cibersegurança e infraestruturas de comunicação.'),
(5, 'Mecatronica', 1, 'Integração de mecânica, eletrónica e programação para sistemas automatizados.');

-- Disciplinas (Unidades Curriculares)
INSERT INTO `disciplinas` (`ID`, `Nome_disc`) VALUES
(1,'Matemática'),(2,'Programação WEB I'),(3,'Linguagens de Programação'),(4,'Português'),
(6,'Introdução à Programação'),(7,'Matemática Discreta'),(8,'Fundamentos de Web'),
(9,'Design Digital'),(10,'Sistemas Operativos'),(11,'Inglês Técnico'),
(12,'Programação Orientada a Objetos'),(13,'Bases de Dados'),(14,'HTML e CSS Avançado'),
(15,'Multimédia e Animação'),(16,'Redes de Computadores I'),(17,'Estatística Aplicada'),
(18,'Programação Web'),(19,'Desenvolvimento Mobile'),(20,'UX/UI Design'),
(21,'Frameworks JavaScript'),(22,'Segurança Informática'),(23,'Gestão de Projetos'),
(24,'Estágio Curricular'),(25,'Introdução à Gestão'),(26,'Fundamentos de Marketing'),
(27,'Matemática Aplicada'),(28,'Economia Digital'),(29,'Marketing Digital'),
(30,'Comportamento do Consumidor'),(31,'Contabilidade'),(32,'Logística e Distribuição'),
(33,'Plataformas de E-Commerce'),(34,'SEO e Analítica Web'),(35,'Gestão de Redes Sociais'),
(36,'Direito Digital'),(37,'Empreendedorismo'),(38,'Arquitetura de Computadores'),
(39,'Fundamentos de Redes'),(40,'Administração de Sistemas'),(41,'Programação de Scripts'),
(42,'Eletrónica Digital'),(43,'Redes Avançadas'),(44,'Cibersegurança'),
(45,'Virtualização e Cloud'),(46,'Administração de Servidores'),
(47,'Matemática I'),(48,'Física Aplicada'),(49,'Desenho Técnico'),(50,'Eletrotecnia'),
(51,'Matemática II'),(52,'Mecânica dos Materiais'),(53,'Programação de Microcontroladores'),
(54,'Sistemas de Controlo'),(55,'Robótica'),(56,'Automação Industrial'),
(57,'Sensores e Atuadores'),(58,'Instrumentação'),(59,'Hidráulica e Pneumática');

-- Plano de Estudos: Desenvolvimento Web e Multimédia (Curso 1)
INSERT INTO `plano_estudos` (`CURSOS`,`DISCIPLINA`,`ano`,`semestre`) VALUES
(1,6,1,1),(1,7,1,1),(1,8,1,1),(1,9,1,1),(1,10,1,1),(1,11,1,1),
(1,12,1,2),(1,13,1,2),(1,14,1,2),(1,15,1,2),(1,16,1,2),(1,17,1,2),
(1,18,2,1),(1,19,2,1),(1,20,2,1),(1,21,2,1),(1,22,2,1),(1,23,2,1),
(1,24,2,2);

-- Plano de Estudos: Comércio Eletrónico (Curso 2)
INSERT INTO `plano_estudos` (`CURSOS`,`DISCIPLINA`,`ano`,`semestre`) VALUES
(2,8,1,1),(2,11,1,1),(2,25,1,1),(2,26,1,1),(2,27,1,1),(2,28,1,1),
(2,13,1,2),(2,17,1,2),(2,29,1,2),(2,30,1,2),(2,31,1,2),(2,32,1,2),
(2,23,2,1),(2,33,2,1),(2,34,2,1),(2,35,2,1),(2,36,2,1),(2,37,2,1),
(2,24,2,2);

-- Plano de Estudos: Redes de Computadores (Curso 3)
INSERT INTO `plano_estudos` (`CURSOS`,`DISCIPLINA`,`ano`,`semestre`) VALUES
(3,6,1,1),(3,7,1,1),(3,10,1,1),(3,11,1,1),(3,38,1,1),(3,39,1,1),
(3,13,1,2),(3,16,1,2),(3,17,1,2),(3,40,1,2),(3,41,1,2),(3,42,1,2),
(3,22,2,1),(3,23,2,1),(3,43,2,1),(3,44,2,1),(3,45,2,1),(3,46,2,1),
(3,24,2,2);

-- Plano de Estudos: Mecatronica (Curso 5)
INSERT INTO `plano_estudos` (`CURSOS`,`DISCIPLINA`,`ano`,`semestre`) VALUES
(5,6,1,1),(5,11,1,1),(5,47,1,1),(5,48,1,1),(5,49,1,1),(5,50,1,1),
(5,17,1,2),(5,42,1,2),(5,51,1,2),(5,52,1,2),(5,53,1,2),(5,54,1,2),
(5,23,2,1),(5,55,2,1),(5,56,2,1),(5,57,2,1),(5,58,2,1),(5,59,2,1),
(5,24,2,2);
