CREATE TABLE Users (
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE PRIMARY KEY,
    password CHAR(64) NOT NULL, -- SHA-256 produces a 64-character hash
    is_teacher BOOLEAN NOT NULL DEFAULT FALSE
);

-- Example of inserting a user without specifying is_teacher
-- INSERT INTO Users (username, email, password) VALUES ('JohnDoe', 'john@example.com', SHA2('password', 256));
