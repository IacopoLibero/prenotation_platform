<?php
session_start();
include('../connessione.php');

$username = htmlspecialchars($_POST['Username']);
$email = htmlspecialchars($_POST['Email']);
$password = $_POST['Password'];
$professore = isset($_POST['professore']) && $_POST['professore'] == "on" ? 1 : 0; // Converti in 0 o 1

$password = hash("sha256", $password);

// Controllo se l'utente è già registrato
$checkQuery = "SELECT * FROM `Users` WHERE email = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Query di inserimento con Prepared Statement
    $query = "INSERT INTO `Users`(`username`, `email`, `password`, `is_teacher`) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $username, $email, $password, $professore);

    if ($stmt->execute()) {
        $_SESSION['status_reg'] = "Registrazione effettuata";
    } else {
        $_SESSION['status_reg'] = "Errore nella registrazione";
    }
} else {
    $_SESSION['status_reg'] = "Utente già registrato";
}

$stmt->close();
$conn->close();

header("Location: ../index.php");
exit();
?>
