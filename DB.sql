CREATE TABLE Lezioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_email VARCHAR(100) NOT NULL,
    student_email VARCHAR(100),
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    FOREIGN KEY (teacher_email) REFERENCES Users(email),
    FOREIGN KEY (student_email) REFERENCES Users(email)
);

CREATE TABLE Studenti (
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE PRIMARY KEY,
    password VARCHAR(255) NOT NULL 
);

CREATE TABLE Professori (
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE PRIMARY KEY,
    password VARCHAR(255) NOT NULL 
);

-- Example of inserting a user without specifying is_teacher
-- INSERT INTO Users (username, email, password) VALUES ('JohnDoe', 'john@example.com', SHA2('password', 256));
