<?php
session_start();
require_once '../connessione.php';

// Controllo se l'utente è loggato ed è un professore
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Recupero dei dati dal form
$titolo = trim($_POST['titolo']);
$descrizione = trim($_POST['descrizione']);
$start_time = $_POST['start_date'] . ' ' . $_POST['start_time'];
$end_time = $_POST['end_date'] . ' ' . $_POST['end_time'];
$teacher_email = $_SESSION['email'];

// Validazione dei dati
if (empty($titolo) || empty($start_time) || empty($end_time)) {
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

// Inserimento della lezione
$query = "INSERT INTO Lezioni (teacher_email, titolo, descrizione, start_time, end_time) 
          VALUES (?, ?, ?, ?, ?)";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("sssss", $teacher_email, $titolo, $descrizione, $start_time, $end_time);

if ($stmt->execute()) {
    $lesson_id = $conn->insert_id;
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Lezione creata con successo', 'lesson_id' => $lesson_id]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore nella creazione della lezione: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
