<?php
session_start();
include('../connessione.php');

if (!isset($_SESSION['user']) || !isset($_SESSION['email']) || !isset($_SESSION['tipo'])) {
    header('Location: ../index.php');
    exit;
}

$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$current_email = $_SESSION['email'];
$tipo = $_SESSION['tipo'];
$table = ($tipo === 'professore') ? 'Professori' : 'Studenti';

// Validazione dei dati
if (empty($username) || empty($email)) {
    $_SESSION['status'] = "Username ed email sono campi obbligatori!";
    header('Location: ../front-end/user_account.php');
    exit;
}

// Se l'email è cambiata, verifica che non sia già in uso
if ($email !== $current_email) {
    $check_query = "SELECT email FROM $table WHERE email = ? AND email != ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ss", $email, $current_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['status'] = "L'email è già in uso da un altro utente!";
        header('Location: ../front-end/user_account.php');
        exit;
    }
}

// Aggiornamento del profilo
if (!empty($password)) {
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $query = "UPDATE $table SET username = ?, email = ?, password = ? WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $username, $email, $passwordHash, $current_email);
} else {
    $query = "UPDATE $table SET username = ?, email = ? WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $username, $email, $current_email);
}

if ($stmt->execute()) {
    // Aggiorna la sessione con i nuovi dati
    $_SESSION['user'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['status'] = "Profilo aggiornato con successo!";
    
    // Se il profilo è di un professore, aggiorna anche campi aggiuntivi se esistono
    if ($tipo === 'professore' && isset($_POST['bio']) && isset($_POST['materie'])) {
        $bio = trim($_POST['bio']);
        $materie = trim($_POST['materie']);
        
        $update_query = "UPDATE Professori SET bio = ?, materie = ? WHERE email = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sss", $bio, $materie, $email);
        $stmt->execute();
    }
} else {
    $_SESSION['status'] = "Errore nell'aggiornamento del profilo: " . $conn->error;
}

$stmt->close();
$conn->close();

header('Location: ../front-end/user_account.php');
exit;
?>
