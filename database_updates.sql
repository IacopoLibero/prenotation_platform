-- Aggiungiamo le nuove colonne alla tabella delle preferenze esistente
ALTER TABLE Preferenze_Disponibilita 
ADD COLUMN ore_prima_evento FLOAT DEFAULT 0,
ADD COLUMN ore_dopo_evento FLOAT DEFAULT 0;
