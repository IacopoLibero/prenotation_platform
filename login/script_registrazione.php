<?php
var_dump($_POST);
die();

session_start();
include('../connessione.php'); // Assicurati che $conn sia la connessione MySQL

// Recupero dati dal form senza filtri
$username = $_POST['Username'];
$email = $_POST['Email'];
$password = $_POST['Password'];
$professore = isset($_POST['professore']) ? 1 : 0; // Converti il checkbox in 0 o 1

// Hash della password
$password = hash("sha256", $password);

// Controllo se l'utente è già registrato
$checkQuery = "SELECT email FROM Users WHERE email = '$email'";
$result = $conn->query($checkQuery);

if ($result->num_rows == 0) {
    // Query di inserimento diretta
    $query = "INSERT INTO Users (username, email, password, is_teacher) VALUES ('$username', '$email', '$password', $professore)";

    if ($conn->query($query)) {
        $_SESSION['status_reg'] = "Registrazione effettuata con successo!";
    } else {
        $_SESSION['status_reg'] = "Errore nella registrazione!";
    }
} else {
    $_SESSION['status_reg'] = "L'utente è già registrato!";
}

$conn->close();

header("Location: ../index.php");
exit();
?>
