<?php
session_start();
require_once '../connessione.php';

// Controllo se l'utente è loggato ed è un professore
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Recupero dell'ID della lezione
$lesson_id = $_POST['lesson_id'];
$teacher_email = $_SESSION['email'];

// Validazione
if (empty($lesson_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID lezione mancante']);
    exit;
}

// Verifica che la lezione appartenga al professore e sia prenotata
$check_query = "SELECT id FROM Lezioni WHERE id = ? AND teacher_email = ? AND stato = 'prenotata'";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("is", $lesson_id, $teacher_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lezione non trovata o non prenotata']);
    exit;
}

// Segna la lezione come completata
$update_query = "UPDATE Lezioni SET stato = 'completata' WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("i", $lesson_id);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Lezione segnata come completata con successo']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento della lezione: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
