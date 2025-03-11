CREATE TABLE Studenti (
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE PRIMARY KEY,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Professori (
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE PRIMARY KEY,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    bio TEXT,
    materie VARCHAR(255)
);

CREATE TABLE Lezioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_email VARCHAR(100) NOT NULL,
    student_email VARCHAR(100) NULL,
    titolo VARCHAR(100) NOT NULL,
    descrizione TEXT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    stato ENUM('disponibile', 'prenotata', 'completata', 'cancellata') DEFAULT 'disponibile',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_email) REFERENCES Professori(email) ON DELETE CASCADE,
    FOREIGN KEY (student_email) REFERENCES Studenti(email) ON DELETE SET NULL
);

CREATE TABLE Disponibilita (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_email VARCHAR(100) NOT NULL,
    giorno_settimana ENUM('lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica') NOT NULL,
    ora_inizio TIME NOT NULL,
    ora_fine TIME NOT NULL,
    FOREIGN KEY (teacher_email) REFERENCES Professori(email) ON DELETE CASCADE,
    UNIQUE(teacher_email, giorno_settimana, ora_inizio)
);

CREATE TABLE Preferiti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_email VARCHAR(100) NOT NULL,
    teacher_email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_email) REFERENCES Studenti(email) ON DELETE CASCADE,
    FOREIGN KEY (teacher_email) REFERENCES Professori(email) ON DELETE CASCADE,
    UNIQUE(student_email, teacher_email)
);
