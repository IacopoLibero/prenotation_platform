<?php
session_start();
require_once '../connessione.php';

// Controllo se l'utente è loggato ed è un professore
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Recupero dell'ID della disponibilità
$availability_id = $_POST['availability_id'];
$teacher_email = $_SESSION['email'];

// Validazione
if (empty($availability_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID disponibilità mancante']);
    exit;
}

// Verifica che la disponibilità appartenga al professore
$check_query = "SELECT id FROM Disponibilita WHERE id = ? AND teacher_email = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("is", $availability_id, $teacher_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Disponibilità non trovata o non autorizzata']);
    exit;
}

// Elimina la disponibilità
$delete_query = "DELETE FROM Disponibilita WHERE id = ?";
$stmt = $conn->prepare($delete_query);
$stmt->bind_param("i", $availability_id);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Disponibilità eliminata con successo']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore nell\'eliminazione della disponibilità: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
