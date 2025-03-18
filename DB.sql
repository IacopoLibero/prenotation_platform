

-- Elimina le tabelle se esistono già (in ordine inverso rispetto alle dipendenze)
DROP TABLE IF EXISTS Preferiti;
DROP TABLE IF EXISTS Preferenze_Disponibilita;
DROP TABLE IF EXISTS Disponibilita;
DROP TABLE IF EXISTS Lezioni;
DROP TABLE IF EXISTS Professori;
DROP TABLE IF EXISTS Studenti;

-- Tabella Studenti
CREATE TABLE `Studenti` (
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

-- Tabella Professori
CREATE TABLE `Professori` (
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `bio` text,
  `materie` varchar(255) DEFAULT NULL,
  `google_calendar_link` varchar(255) DEFAULT NULL,
  `google_calendar_id` varchar(100) DEFAULT NULL
);

-- Tabella Lezioni
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
);

-- Tabella Disponibilita
CREATE TABLE `Disponibilita` (
  `id` int NOT NULL,
  `teacher_email` varchar(100) NOT NULL,
  `giorno_settimana` enum('lunedi','martedi','mercoledi','giovedi','venerdi','sabato','domenica') NOT NULL,
  `ora_inizio` time NOT NULL,
  `ora_fine` time NOT NULL
);

-- Tabella Preferenze_Disponibilita
CREATE TABLE `Preferenze_Disponibilita` (
  `id` int NOT NULL,
  `teacher_email` varchar(100) NOT NULL,
  `weekend` tinyint(1) DEFAULT '0',
  `mattina` tinyint(1) DEFAULT '1',
  `pomeriggio` tinyint(1) DEFAULT '1',
  `ora_inizio_mattina` time DEFAULT '08:00:00',
  `ora_fine_mattina` time DEFAULT '13:00:00',
  `ora_inizio_pomeriggio` time DEFAULT '14:00:00',
  `ora_fine_pomeriggio` time DEFAULT '19:00:00',
  `ore_prima_evento` float DEFAULT '0',
  `ore_dopo_evento` float DEFAULT '0'
);

-- Tabella Preferiti
CREATE TABLE `Preferiti` (
  `id` int NOT NULL,
  `student_email` varchar(100) NOT NULL,
  `teacher_email` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

-- Indici tabella Studenti
ALTER TABLE `Studenti`
  ADD PRIMARY KEY (`email`),
  ADD UNIQUE KEY `email` (`email`);

-- Indici tabella Professori
ALTER TABLE `Professori`
  ADD PRIMARY KEY (`email`),
  ADD UNIQUE KEY `email` (`email`);

-- Indici tabella Lezioni
ALTER TABLE `Lezioni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_email` (`teacher_email`),
  ADD KEY `student_email` (`student_email`);

-- Indici tabella Disponibilita
ALTER TABLE `Disponibilita`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_email` (`teacher_email`,`giorno_settimana`,`ora_inizio`);

-- Indici tabella Preferenze_Disponibilita
ALTER TABLE `Preferenze_Disponibilita`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_email` (`teacher_email`);

-- Indici tabella Preferiti
ALTER TABLE `Preferiti`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_email` (`student_email`,`teacher_email`),
  ADD KEY `teacher_email` (`teacher_email`);

-- AUTO_INCREMENT
ALTER TABLE `Disponibilita`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `Lezioni`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `Preferenze_Disponibilita`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `Preferiti`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- Vincoli di chiave esterna
ALTER TABLE `Lezioni`
  ADD CONSTRAINT `lezioni_ibfk_1` FOREIGN KEY (`teacher_email`) REFERENCES `Professori` (`email`) ON DELETE CASCADE,
  ADD CONSTRAINT `lezioni_ibfk_2` FOREIGN KEY (`student_email`) REFERENCES `Studenti` (`email`) ON DELETE SET NULL;

ALTER TABLE `Disponibilita`
  ADD CONSTRAINT `disponibilita_ibfk_1` FOREIGN KEY (`teacher_email`) REFERENCES `Professori` (`email`) ON DELETE CASCADE;

ALTER TABLE `Preferenze_Disponibilita`
  ADD CONSTRAINT `preferenze_disponibilita_ibfk_1` FOREIGN KEY (`teacher_email`) REFERENCES `Professori` (`email`) ON DELETE CASCADE;

ALTER TABLE `Preferiti`
  ADD CONSTRAINT `preferiti_ibfk_1` FOREIGN KEY (`student_email`) REFERENCES `Studenti` (`email`) ON DELETE CASCADE,
  ADD CONSTRAINT `preferiti_ibfk_2` FOREIGN KEY (`teacher_email`) REFERENCES `Professori` (`email`) ON DELETE CASCADE;

COMMIT;

