<?php
include('../connessione.php');  // Include il file di connessione per utilizzare $conn

session_start();

// Recupero e sanificazione dei dati
$mail = trim(htmlspecialchars($_POST['Email']));
$password = $_POST['Password'];

// Imposta il login a false e l'utente a vuoto
$_SESSION['log'] = false;
$_SESSION['user'] = "";
$_SESSION['pass'] = $password; // Non hashare la password prima di verificarla

// Query preparata per verificare se l'utente esiste
$checkQuery = "SELECT *, 'professore' as tipo FROM `Professori` WHERE email = ? UNION SELECT *, 'studente' as tipo FROM `Studenti` WHERE email = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("ss", $mail, $mail);
$stmt->execute();
$result = $stmt->get_result();

// Verifica se l'utente esiste nel database
if ($result->num_rows > 0) {
    // Ottieni la riga dell'utente
    $row = $result->fetch_assoc();

    // Verifica se la password è corretta utilizzando password_verify
    if (password_verify($password, $row['password'])) {
        // Salva il login e l'utente nella sessione
        $_SESSION['log'] = true;
        $_SESSION['user'] = $row['username'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['tipo'] = $row['tipo']; // Salva il tipo di utente (professore o studente)
        header("Location: ../front-end/home.php");
        exit();
    } else {
        // Imposta il messaggio di errore e reindirizza
        $_SESSION['status'] = "Password errata";
        header("Location: ../index.php");
        exit();
    }
} else {
    // Imposta il messaggio di errore e reindirizza
    $_SESSION['status'] = "Email non registrata";
    header("Location: ../index.php");
    exit();
}

// Chiudi la connessione
$stmt->close();
$conn->close();
?>
