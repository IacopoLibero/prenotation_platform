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
$lesson_id = $_POST['lesson_id'];
$student_email = $_SESSION['email'];

// Validazione
if (empty($lesson_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID lezione mancante']);
    exit;
}

// Verifica che la lezione sia disponibile
$check_query = "SELECT id FROM Lezioni WHERE id = ? AND stato = 'disponibile'";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lezione non disponibile per la prenotazione']);
    exit;
}

// Prenota la lezione
$update_query = "UPDATE Lezioni SET stato = 'prenotata', student_email = ? WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("si", $student_email, $lesson_id);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Lezione prenotata con successo']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore nella prenotazione della lezione: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
