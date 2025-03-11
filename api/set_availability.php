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
$giorno_settimana = $_POST['giorno'];
$ora_inizio = $_POST['ora_inizio'];
$ora_fine = $_POST['ora_fine'];
$teacher_email = $_SESSION['email'];

// Validazione dei dati
if (empty($giorno_settimana) || empty($ora_inizio) || empty($ora_fine)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Tutti i campi sono obbligatori']);
    exit;
}

// Verifica che l'ora di fine sia successiva all'ora di inizio
if ($ora_fine <= $ora_inizio) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'L\'ora di fine deve essere successiva all\'ora di inizio']);
    exit;
}

// Inserimento della disponibilità (con REPLACE per gestire le duplicazioni)
$query = "REPLACE INTO Disponibilita (teacher_email, giorno_settimana, ora_inizio, ora_fine) 
          VALUES (?, ?, ?, ?)";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("ssss", $teacher_email, $giorno_settimana, $ora_inizio, $ora_fine);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Disponibilità impostata con successo']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore nell\'impostazione della disponibilità: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
