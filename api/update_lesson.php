<?php
session_start();
require_once '../connessione.php';

// Controllo se l'utente è loggato ed è un professore
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Recupero dei dati
$lesson_id = $_POST['lesson_id'];
$titolo = trim($_POST['titolo']);
$descrizione = trim($_POST['descrizione']);
$start_time = $_POST['start_date'] . ' ' . $_POST['start_time'];
$end_time = $_POST['end_date'] . ' ' . $_POST['end_time'];
$teacher_email = $_SESSION['email'];

// Validazione dei dati
if (empty($lesson_id) || empty($titolo) || empty($start_time) || empty($end_time)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Tutti i campi obbligatori devono essere compilati']);
    exit;
}

// Verifica che la data di fine sia successiva alla data di inizio
if (strtotime($end_time) <= strtotime($start_time)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'La data di fine deve essere successiva alla data di inizio']);
    exit;
}

// Verifica che la lezione appartenga al professore
$check_query = "SELECT id FROM Lezioni WHERE id = ? AND teacher_email = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("is", $lesson_id, $teacher_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lezione non trovata o non autorizzata']);
    exit;
}

// Aggiornamento della lezione
$update_query = "UPDATE Lezioni SET titolo = ?, descrizione = ?, start_time = ?, end_time = ? WHERE id = ? AND teacher_email = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("ssssss", $titolo, $descrizione, $start_time, $end_time, $lesson_id, $teacher_email);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Lezione aggiornata con successo']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiornamento della lezione: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
