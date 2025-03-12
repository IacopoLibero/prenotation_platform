-- Aggiungiamo i campi per Google Calendar alla tabella Professori
ALTER TABLE Professori ADD COLUMN google_calendar_link VARCHAR(255) DEFAULT NULL;
ALTER TABLE Professori ADD COLUMN google_calendar_id VARCHAR(100) DEFAULT NULL;

-- Creiamo una tabella per le preferenze di disponibilità
CREATE TABLE IF NOT EXISTS Preferenze_Disponibilita (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_email VARCHAR(100) NOT NULL,
    weekend BOOLEAN DEFAULT FALSE,
    mattina BOOLEAN DEFAULT TRUE,
    pomeriggio BOOLEAN DEFAULT TRUE,
    ora_inizio_mattina TIME DEFAULT '08:00:00',
    ora_fine_mattina TIME DEFAULT '13:00:00',
    ora_inizio_pomeriggio TIME DEFAULT '14:00:00',
    ora_fine_pomeriggio TIME DEFAULT '19:00:00',
    FOREIGN KEY (teacher_email) REFERENCES Professori(email) ON DELETE CASCADE
);
