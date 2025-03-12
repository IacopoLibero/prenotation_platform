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
$teacher_email = $_SESSION['email'];
$calendar_link = $_POST['calendar_link'];
$weekend = isset($_POST['weekend']) ? (bool)$_POST['weekend'] : false;
$mattina = isset($_POST['mattina']) ? (bool)$_POST['mattina'] : true;
$pomeriggio = isset($_POST['pomeriggio']) ? (bool)$_POST['pomeriggio'] : true;
$ora_inizio_mattina = $_POST['ora_inizio_mattina'] ?? '08:00';
$ora_fine_mattina = $_POST['ora_fine_mattina'] ?? '13:00';
$ora_inizio_pomeriggio = $_POST['ora_inizio_pomeriggio'] ?? '14:00';
$ora_fine_pomeriggio = $_POST['ora_fine_pomeriggio'] ?? '19:00';

// Validazione
if (empty($calendar_link)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Il link del calendario è obbligatorio']);
    exit;
}

// Verifica che il link sia un URL valido
if (!filter_var($calendar_link, FILTER_VALIDATE_URL)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Il link del calendario non è valido']);
    exit;
}

// Estrazione dell'ID del calendario dal link
$calendar_id = null;
if (preg_match('/\/([a-zA-Z0-9%@._-]+)\/public\/basic\.ics$/', $calendar_link, $matches)) {
    $calendar_id = urldecode($matches[1]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Formato del link calendario non valido. Assicurati di utilizzare il link iCal dal tuo Google Calendar.']);
    exit;
}

// Salvataggio del link del calendario
$update_query = "UPDATE Professori SET google_calendar_link = ?, google_calendar_id = ? WHERE email = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("sss", $calendar_link, $calendar_id, $teacher_email);
$result = $stmt->execute();

if (!$result) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore nel salvare il link del calendario: ' . $stmt->error]);
    exit;
}

// Salvataggio delle preferenze di disponibilità
$check_query = "SELECT id FROM Preferenze_Disponibilita WHERE teacher_email = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("s", $teacher_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Aggiorna le preferenze esistenti
    $update_query = "UPDATE Preferenze_Disponibilita 
                     SET weekend = ?, mattina = ?, pomeriggio = ?, 
                         ora_inizio_mattina = ?, ora_fine_mattina = ?,
                         ora_inizio_pomeriggio = ?, ora_fine_pomeriggio = ?
                     WHERE teacher_email = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("iiisssss", $weekend, $mattina, $pomeriggio, 
                     $ora_inizio_mattina, $ora_fine_mattina, 
                     $ora_inizio_pomeriggio, $ora_fine_pomeriggio,
                     $teacher_email);
} else {
    // Inserisce nuove preferenze
    $insert_query = "INSERT INTO Preferenze_Disponibilita 
                    (teacher_email, weekend, mattina, pomeriggio, 
                     ora_inizio_mattina, ora_fine_mattina,
                     ora_inizio_pomeriggio, ora_fine_pomeriggio)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("siisssss", $teacher_email, $weekend, $mattina, $pomeriggio, 
                     $ora_inizio_mattina, $ora_fine_mattina, 
                     $ora_inizio_pomeriggio, $ora_fine_pomeriggio);
}

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Preferenze salvate con successo']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore nel salvare le preferenze: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
