<?php
session_start();
include('../connessione.php'); // Assicurati che $conn sia la connessione MySQL

// Recupero e pulizia dati dal form
$username = trim($_POST['Username']);
$email = trim($_POST['Email']);
$password = $_POST['Password'];
$professore = isset($_POST['professore']) ? 1 : 0; // Converti il checkbox in 0 o 1

// Hash sicuro della password
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

// Controllo se l'utente è già registrato con Prepared Statement
if ($professore) {
    $checkQuery = "SELECT email FROM Professori WHERE email = ?";
} else {
    $checkQuery = "SELECT email FROM Studenti WHERE email = ?";
}
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Query di inserimento sicura
    if ($professore) {
        $query = "INSERT INTO Professori (username, email, password) VALUES (?, ?, ?)";
    } else {
        $query = "INSERT INTO Studenti (username, email, password) VALUES (?, ?, ?)";
    }
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $username, $email, $passwordHash);

    if ($stmt->execute()) {
        $_SESSION['status_reg'] = "Registrazione effettuata con successo!";
    } else {
        $_SESSION['status_reg'] = "Errore nella registrazione!";
    }
} else {
    $_SESSION['status_reg'] = "L'utente è già registrato!";
}

// Chiudi le connessioni
$stmt->close();
$conn->close();

// Reindirizzamento
header("Location: ../index.php");
exit();
?>
