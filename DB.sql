-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Apr 27, 2025 alle 16:19
-- Versione del server: 8.0.36
-- Versione PHP: 8.0.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `my_superipetizioni`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `Calendari_Professori`
--

CREATE TABLE `Calendari_Professori` (
  `id` int NOT NULL,
  `teacher_email` varchar(100) NOT NULL,
  `google_calendar_link` varchar(255) NOT NULL,
  `google_calendar_id` varchar(100) DEFAULT NULL,
  `nome_calendario` varchar(100) DEFAULT 'Calendario',
  `ore_prima_evento` float DEFAULT '0',
  `ore_dopo_evento` float DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `Calendari_Professori`
--

INSERT INTO `Calendari_Professori` (`id`, `teacher_email`, `google_calendar_link`, `google_calendar_id`, `nome_calendario`, `ore_prima_evento`, `ore_dopo_evento`, `is_active`, `created_at`) VALUES
(1, 'prova1@gmail.com', 'https://calendar.google.com/calendar/embed?src=78859b2513ad7e40512503b01415c82e795a1135df834c68d97bbdbc877a04a9%40group.calendar.google.com', '78859b2513ad7e40512503b01415c82e795a1135df834c68d97bbdbc877a04a9@group.calendar.google.com', 'corso', 2, 1, 1, '2025-04-27 13:41:22'),
(3, 'prova1@gmail.com', 'https://calendar.google.com/calendar/embed?src=classroom101560746385280479858%40group.calendar.google.com', 'classroom101560746385280479858@group.calendar.google.com', 'ripetizioni', 0, 0, 1, '2025-04-27 13:42:56');

-- --------------------------------------------------------

--
-- Struttura della tabella `Lezioni`
--

CREATE TABLE `Lezioni` (
  `id` int NOT NULL,
  `teacher_email` varchar(100) NOT NULL,
  `student_email` varchar(100) DEFAULT NULL,
  `titolo` varchar(100) NOT NULL,
  `descrizione` text,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `stato` enum('disponibile','prenotata','completata','cancellata') DEFAULT 'disponibile',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `OAuth_Tokens`
--

CREATE TABLE `OAuth_Tokens` (
  `id` int NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `user_type` enum('professore','studente') NOT NULL,
  `access_token` text,
  `refresh_token` text,
  `expiry_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `OAuth_Tokens`
--


-- --------------------------------------------------------

--
-- Struttura della tabella `Preferenze_Disponibilita`
--

CREATE TABLE `Preferenze_Disponibilita` (
  `id` int NOT NULL,
  `teacher_email` varchar(100) NOT NULL,
  `weekend` tinyint(1) DEFAULT '0',
  `calendario_selezionato_id` int DEFAULT NULL,
  `mattina` tinyint(1) DEFAULT '1',
  `pomeriggio` tinyint(1) DEFAULT '1',
  `ora_inizio_mattina` time DEFAULT '08:00:00',
  `ora_fine_mattina` time DEFAULT '13:00:00',
  `ora_inizio_pomeriggio` time DEFAULT '14:00:00',
  `ora_fine_pomeriggio` time DEFAULT '19:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `Preferenze_Disponibilita`
--

INSERT INTO `Preferenze_Disponibilita` (`id`, `teacher_email`, `weekend`, `calendario_selezionato_id`, `mattina`, `pomeriggio`, `ora_inizio_mattina`, `ora_fine_mattina`, `ora_inizio_pomeriggio`, `ora_fine_pomeriggio`) VALUES
(1, 'prova1@gmail.com', 0, NULL, 1, 1, '00:00:08', '13:00:00', '14:00:00', '19:00:00');

-- --------------------------------------------------------

--
-- Struttura della tabella `Professori`
--

CREATE TABLE `Professori` (
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `bio` text,
  `materie` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `Professori`
--

INSERT INTO `Professori` (`username`, `email`, `password`, `created_at`, `bio`, `materie`) VALUES
('IacopoLibero', 'prova1@gmail.com', '$2y$10$29yHKALm3d92TONZqQ8rs.JoNxwXEEPTePhc69e0wXGqaJxUiRqH.', '2025-04-27 10:31:52', '', 'informatica');

-- --------------------------------------------------------

--
-- Struttura della tabella `Studenti`
--

CREATE TABLE `Studenti` (
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `Calendari_Professori`
--
ALTER TABLE `Calendari_Professori`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_calendar` (`teacher_email`,`google_calendar_id`);

--
-- Indici per le tabelle `Lezioni`
--
ALTER TABLE `Lezioni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_email` (`teacher_email`),
  ADD KEY `student_email` (`student_email`);

--
-- Indici per le tabelle `OAuth_Tokens`
--
ALTER TABLE `OAuth_Tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_email` (`user_email`,`user_type`);

--
-- Indici per le tabelle `Preferenze_Disponibilita`
--
ALTER TABLE `Preferenze_Disponibilita`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_email` (`teacher_email`),
  ADD KEY `fk_calendario_selezionato` (`calendario_selezionato_id`);

--
-- Indici per le tabelle `Professori`
--
ALTER TABLE `Professori`
  ADD PRIMARY KEY (`email`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indici per le tabelle `Studenti`
--
ALTER TABLE `Studenti`
  ADD PRIMARY KEY (`email`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `Calendari_Professori`
--
ALTER TABLE `Calendari_Professori`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `Lezioni`
--
ALTER TABLE `Lezioni`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `OAuth_Tokens`
--
ALTER TABLE `OAuth_Tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `Preferenze_Disponibilita`
--
ALTER TABLE `Preferenze_Disponibilita`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
