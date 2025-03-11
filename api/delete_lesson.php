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

// Verifica che la lezione appartenga al professore
$check_query = "SELECT stato FROM Lezioni WHERE id = ? AND teacher_email = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("is", $lesson_id, $teacher_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lezione non trovata o non autorizzata']);
    exit;
}

$row = $result->fetch_assoc();
if ($row['stato'] === 'prenotata') {
    // Se la lezione è prenotata, aggiorna lo stato a 'cancellata'
    $update_query = "UPDATE Lezioni SET stato = 'cancellata' WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $lesson_id);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Lezione cancellata con successo']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Errore nella cancellazione della lezione: ' . $stmt->error]);
    }
} else {
    // Se la lezione non è prenotata, elimina la riga dal database
    $delete_query = "DELETE FROM Lezioni WHERE id = ? AND teacher_email = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("is", $lesson_id, $teacher_email);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Lezione eliminata con successo']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Errore nell\'eliminazione della lezione: ' . $stmt->error]);
    }
}

$stmt->close();
$conn->close();
?>
