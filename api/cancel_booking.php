<?php
session_start();
require_once '../connessione.php';

// Controllo se l'utente è loggato e sia studente
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

// Verifica che la lezione sia prenotata dallo studente
$check_query = "SELECT id FROM Lezioni WHERE id = ? AND student_email = ? AND stato = 'prenotata'";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("is", $lesson_id, $student_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lezione non trovata o non prenotata da te']);
    exit;
}

// Annulla la prenotazione
$update_query = "UPDATE Lezioni SET stato = 'disponibile', student_email = NULL WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("i", $lesson_id);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Prenotazione cancellata con successo']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore nella cancellazione della prenotazione: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
