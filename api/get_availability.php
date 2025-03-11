<?php
session_start();
require_once '../connessione.php';

// Controllo se l'utente è loggato
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Recupero dell'email del professore
$teacher_email = $_GET['email'];

// Validazione
if (empty($teacher_email)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Email professore mancante']);
    exit;
}

// Ottieni la disponibilità
$query = "SELECT giorno_settimana, ora_inizio, ora_fine FROM Disponibilita 
          WHERE teacher_email = ? 
          ORDER BY FIELD(giorno_settimana, 'lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica'), 
          ora_inizio";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $teacher_email);
$stmt->execute();
$result = $stmt->get_result();

$availability = [];
while ($row = $result->fetch_assoc()) {
    $availability[] = $row;
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'availability' => $availability]);

$stmt->close();
$conn->close();
?>
