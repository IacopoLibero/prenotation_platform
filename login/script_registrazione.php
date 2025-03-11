<?php
session_start();
include('../connessione.php');

// Debug: Stampiamo i dati ricevuti dal form
// var_dump($_POST); die(); // <-- Decommenta questa riga per fare un test

// Recupero e sanificazione input
$username = isset($_POST['Username']) ? trim($_POST['Username']) : '';
$email = isset($_POST['Email']) ? trim($_POST['Email']) : '';
$password = isset($_POST['Password']) ? $_POST['Password'] : '';

$professore = isset($_POST['professore']) && $_POST['professore'] == "on" ? 1 : 0; // Converti in 0 o 1

// Controllo che i dati non siano vuoti
if (empty($username) || empty($email) || empty($password)) {
    $_SESSION['status_reg'] = "Errore: tutti i campi sono obbligatori!";
    header("Location: ../index.php");
    exit();
}

// Hash della password
$password = hash("sha256", $password);

// Controllo se l'utente è già registrato
$checkQuery = "SELECT * FROM `Users` WHERE email = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Query di inserimento
    $query = "INSERT INTO `Users` (`username`, `email`, `password`, `is_teacher`) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $username, $email, $password, $professore);

    if ($stmt->execute()) {
        $_SESSION['status_reg'] = "Registrazione effettuata con successo!";
    } else {
        $_SESSION['status_reg'] = "Errore nella registrazione!";
    }
} else {
    $_SESSION['status_reg'] = "L'utente è già registrato!";
}

$stmt->close();
$conn->close();

header("Location: ../index.php");
exit();
?>
