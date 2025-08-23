-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 23/08/2025 às 21:17
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `escala_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `bombeiros`
--

CREATE TABLE `bombeiros` (
  `id` int(11) NOT NULL,
  `nome_completo` varchar(255) NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL COMMENT 'Formato 000.000.000-00',
  `endereco` varchar(255) DEFAULT NULL COMMENT 'Endereço residencial completo',
  `telefone` varchar(20) DEFAULT NULL COMMENT 'Número de telefone principal',
  `telefone_emergencia` varchar(20) DEFAULT NULL COMMENT 'Contato de emergência (nome e telefone)',
  `tipo` enum('BC','Fixo') NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `fixo_ref_data` date DEFAULT NULL,
  `fixo_ref_dia_ciclo` tinyint(1) DEFAULT NULL CHECK (`fixo_ref_dia_ciclo` between 1 and 4),
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `endereco_rua` varchar(255) DEFAULT NULL,
  `endereco_numero` varchar(20) DEFAULT NULL,
  `endereco_bairro` varchar(100) DEFAULT NULL,
  `endereco_cidade` varchar(100) DEFAULT NULL,
  `endereco_uf` varchar(2) DEFAULT NULL,
  `endereco_cep` varchar(10) DEFAULT NULL,
  `telefone_principal` varchar(20) DEFAULT NULL,
  `contato_emergencia_nome` varchar(255) DEFAULT NULL,
  `contato_emergencia_fone` varchar(20) DEFAULT NULL,
  `dados_bancarios` text DEFAULT NULL COMMENT 'Ex: Banco XPTO, Ag: 0001, C/C: 12345-6',
  `tamanho_gandola` varchar(20) DEFAULT NULL,
  `tamanho_camiseta` varchar(20) DEFAULT NULL,
  `tamanho_calca` varchar(20) DEFAULT NULL,
  `tamanho_calcado` varchar(10) DEFAULT NULL,
  `banco_nome` varchar(100) DEFAULT NULL COMMENT 'Nome do Banco',
  `banco_agencia` varchar(20) DEFAULT NULL COMMENT 'Número da Agência (com dígito, se houver)',
  `banco_conta` varchar(30) DEFAULT NULL COMMENT 'Número da Conta Corrente/Poupança (com dígito)',
  `banco_pix` varchar(100) DEFAULT NULL COMMENT 'Chave PIX principal (CPF, e-mail, telefone, aleatória)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `bombeiros`
--

INSERT INTO `bombeiros` (`id`, `nome_completo`, `email`, `cpf`, `endereco`, `telefone`, `telefone_emergencia`, `tipo`, `ativo`, `fixo_ref_data`, `fixo_ref_dia_ciclo`, `data_cadastro`, `endereco_rua`, `endereco_numero`, `endereco_bairro`, `endereco_cidade`, `endereco_uf`, `endereco_cep`, `telefone_principal`, `contato_emergencia_nome`, `contato_emergencia_fone`, `dados_bancarios`, `tamanho_gandola`, `tamanho_camiseta`, `tamanho_calca`, `tamanho_calcado`, `banco_nome`, `banco_agencia`, `banco_conta`, `banco_pix`) VALUES
(1, 'ANDREA ZART', NULL, NULL, NULL, NULL, NULL, 'BC', 1, NULL, NULL, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'ANELI MIOTTO TERNUS', NULL, NULL, NULL, NULL, NULL, 'BC', 1, NULL, NULL, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'ANGELICA BOETTCHER', NULL, NULL, NULL, NULL, NULL, 'BC', 1, NULL, NULL, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'BRIAN DEIV HENRICH COSMAN', NULL, NULL, NULL, NULL, NULL, 'Fixo', 1, '2025-08-01', 1, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'CLEIMAR BOETTCHER', NULL, NULL, NULL, NULL, NULL, 'Fixo', 1, '2025-08-01', 3, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'CLEIDIVAN IVAN BENEDIX', NULL, NULL, NULL, NULL, NULL, 'BC', 0, NULL, NULL, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'CRISTIAN KONCZIKOSKI', NULL, NULL, NULL, NULL, NULL, 'Fixo', 1, '2025-08-01', 2, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'CRISTIANE BOETTCHER', NULL, NULL, NULL, NULL, NULL, 'BC', 1, NULL, NULL, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'KELVIN KERKHOFF', '', NULL, NULL, NULL, NULL, 'Fixo', 1, '2025-08-01', 4, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'LUIZ FERNANDO HOHN', 'luiizhohn@gmail.com\r\n', NULL, NULL, NULL, NULL, 'BC', 1, NULL, NULL, '2025-08-20 01:52:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'DOUGLAS LUBENOW', NULL, NULL, NULL, NULL, NULL, 'BC', 1, NULL, NULL, '2025-08-20 02:03:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'ELDI GELSI NICHTERWITZ PORTELA', NULL, NULL, NULL, NULL, NULL, 'BC', 1, NULL, NULL, '2025-08-20 02:03:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 'JOSÉ NELSO BOTT', NULL, NULL, NULL, NULL, NULL, 'BC', 1, NULL, NULL, '2025-08-20 02:03:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, 'MAICON MOHR', NULL, NULL, NULL, NULL, NULL, 'BC', 1, NULL, NULL, '2025-08-20 02:03:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 'MARCLEI NICHTERVITZ', NULL, NULL, NULL, NULL, NULL, 'BC', 1, NULL, NULL, '2025-08-20 02:03:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 'PATRICIA MARIA BOSING HOFFMANN', NULL, NULL, NULL, NULL, NULL, 'BC', 1, NULL, NULL, '2025-08-20 02:03:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, 'PATRICIA BERTOLDI', NULL, NULL, NULL, NULL, NULL, 'BC', 1, NULL, NULL, '2025-08-20 02:03:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `chat_mensagens`
--

CREATE TABLE `chat_mensagens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `destinatario_id` int(11) NOT NULL,
  `mensagem` text NOT NULL,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `chave` varchar(50) NOT NULL,
  `valor` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`chave`, `valor`) VALUES
('bc_da_vez_id', '8'),
('bc_inicio_ordem_id', '3'),
('ultimo_bc_iniciou_mes', '2');

-- --------------------------------------------------------

--
-- Estrutura para tabela `excecoes_ciclo_fixo`
--

CREATE TABLE `excecoes_ciclo_fixo` (
  `id` int(11) NOT NULL,
  `bombeiro_id` int(11) NOT NULL,
  `data` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `excecoes_ciclo_fixo`
--

INSERT INTO `excecoes_ciclo_fixo` (`id`, `bombeiro_id`, `data`) VALUES
(1, 4, '2025-08-01'),
(2, 12, '2025-08-02');

-- --------------------------------------------------------

--
-- Estrutura para tabela `plantoes`
--

CREATE TABLE `plantoes` (
  `id` int(11) NOT NULL,
  `bombeiro_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `turno` enum('D','N','I') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `plantoes`
--

INSERT INTO `plantoes` (`id`, `bombeiro_id`, `data`, `turno`) VALUES
(22, 1, '2025-08-01', 'N'),
(29, 1, '2025-08-05', 'D'),
(14, 3, '2025-08-02', 'D'),
(23, 4, '2025-08-01', ''),
(20, 4, '2025-08-02', 'N'),
(21, 8, '2025-08-06', 'D'),
(31, 8, '2025-09-01', 'I'),
(13, 12, '2025-08-02', ''),
(33, 13, '2025-08-03', 'D'),
(25, 13, '2025-08-07', 'I'),
(28, 20, '2025-08-01', 'D');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `tipo` enum('admin','bc') NOT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `usuario`, `senha_hash`, `tipo`, `ativo`) VALUES
(1, 'Administrador', 'admin', '$2y$10$Lvq0pDDvLBe3C86zZT0ma.KWzqEB0i19dMbNJrK1xwGRI8oFQpssa', 'admin', 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `bombeiros`
--
ALTER TABLE `bombeiros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bombeiros_email` (`email`);

--
-- Índices de tabela `chat_mensagens`
--
ALTER TABLE `chat_mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `destinatario_id` (`destinatario_id`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`chave`);

--
-- Índices de tabela `excecoes_ciclo_fixo`
--
ALTER TABLE `excecoes_ciclo_fixo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_excecao` (`bombeiro_id`,`data`);

--
-- Índices de tabela `plantoes`
--
ALTER TABLE `plantoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_bombeiro_data_turno` (`bombeiro_id`,`data`,`turno`),
  ADD KEY `idx_data` (`data`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `bombeiros`
--
ALTER TABLE `bombeiros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `chat_mensagens`
--
ALTER TABLE `chat_mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `excecoes_ciclo_fixo`
--
ALTER TABLE `excecoes_ciclo_fixo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `plantoes`
--
ALTER TABLE `plantoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `chat_mensagens`
--
ALTER TABLE `chat_mensagens`
  ADD CONSTRAINT `chat_mensagens_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `chat_mensagens_ibfk_2` FOREIGN KEY (`destinatario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `excecoes_ciclo_fixo`
--
ALTER TABLE `excecoes_ciclo_fixo`
  ADD CONSTRAINT `excecoes_ciclo_fixo_ibfk_1` FOREIGN KEY (`bombeiro_id`) REFERENCES `bombeiros` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `plantoes`
--
ALTER TABLE `plantoes`
  ADD CONSTRAINT `plantoes_ibfk_1` FOREIGN KEY (`bombeiro_id`) REFERENCES `bombeiros` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
