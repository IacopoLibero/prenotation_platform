CREATE TABLE Studenti (
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE PRIMARY KEY,
    password VARCHAR(255) NOT NULL, -- Password hash con BCRYPT
);
CREATE TABLE Professori (
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE PRIMARY KEY,
    password VARCHAR(255) NOT NULL, -- Password hash con BCRYPT
);

-- Example of inserting a user without specifying is_teacher
-- INSERT INTO Users (username, email, password) VALUES ('JohnDoe', 'john@example.com', SHA2('password', 256));
