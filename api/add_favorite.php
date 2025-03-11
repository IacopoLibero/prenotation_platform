<?php
session_start();
require_once '../connessione.php';

// Controllo se l'utente è loggato ed è uno studente
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'studente') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Recupero dei dati
$teacher_email = $_POST['teacher_email'];
$student_email = $_SESSION['email'];

// Validazione
if (empty($teacher_email)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Email insegnante mancante']);
    exit;
}

// Verifica che il professore esista
$check_query = "SELECT email FROM Professori WHERE email = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("s", $teacher_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Insegnante non trovato']);
    exit;
}

// Aggiungi ai preferiti
$insert_query = "INSERT IGNORE INTO Preferiti (student_email, teacher_email) VALUES (?, ?)";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("ss", $student_email, $teacher_email);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Insegnante aggiunto ai preferiti']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiunta ai preferiti: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
