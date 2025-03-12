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
$query = "SELECT d.giorno_settimana, d.ora_inizio, d.ora_fine, 
          CASE WHEN p.google_calendar_link IS NOT NULL THEN 1 ELSE 0 END as from_google_calendar 
          FROM Disponibilita d
          JOIN Professori p ON d.teacher_email = p.email
          WHERE d.teacher_email = ? 
          ORDER BY FIELD(d.giorno_settimana, 'lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica'), 
          d.ora_inizio";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $teacher_email);
$stmt->execute();
$result = $stmt->get_result();

$availability = [];
while ($row = $result->fetch_assoc()) {
    $availability[] = $row;
}

// Aggiungi anche l'informazione se l'insegnante usa Google Calendar
$query = "SELECT google_calendar_link FROM Professori WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $teacher_email);
$stmt->execute();
$result = $stmt->get_result();
$teacherInfo = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode([
    'success' => true, 
    'availability' => $availability,
    'uses_google_calendar' => !empty($teacherInfo['google_calendar_link'])
]);

$stmt->close();
$conn->close();
?>
