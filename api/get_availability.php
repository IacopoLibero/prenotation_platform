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

// Ottieni le lezioni disponibili direttamente dalla tabella Lezioni
$query = "SELECT 
          l.id,
          l.titolo,
          l.start_time,
          l.end_time,
          l.stato,
          DATE_FORMAT(l.start_time, '%Y-%m-%d') as data,
          DATE_FORMAT(l.start_time, '%d/%m/%Y') as data_formattata,
          CASE
              WHEN DAYOFWEEK(l.start_time) = 2 THEN 'lunedi'
              WHEN DAYOFWEEK(l.start_time) = 3 THEN 'martedi'
              WHEN DAYOFWEEK(l.start_time) = 4 THEN 'mercoledi'
              WHEN DAYOFWEEK(l.start_time) = 5 THEN 'giovedi'
              WHEN DAYOFWEEK(l.start_time) = 6 THEN 'venerdi'
              WHEN DAYOFWEEK(l.start_time) = 7 THEN 'sabato'
              WHEN DAYOFWEEK(l.start_time) = 1 THEN 'domenica'
          END as giorno_settimana,
          DATE_FORMAT(l.start_time, '%H:%i') as ora_inizio,
          DATE_FORMAT(l.end_time, '%H:%i') as ora_fine,
          CASE WHEN p.google_calendar_link IS NOT NULL THEN 1 ELSE 0 END as from_google_calendar
          FROM Lezioni l
          JOIN Professori p ON l.teacher_email = p.email
          WHERE l.teacher_email = ? 
          AND l.stato = 'disponibile'
          AND l.start_time > NOW()
          ORDER BY l.start_time";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $teacher_email);
$stmt->execute();
$result = $stmt->get_result();

$availability = [];
while ($row = $result->fetch_assoc()) {
    $availability[] = $row;
}

// Recupera se l'insegnante usa Google Calendar
$query = "SELECT google_calendar_link FROM Professori WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $teacher_email);
$stmt->execute();
$result = $stmt->get_result();
$teacherInfo = $result->fetch_assoc();

// Log per debug
error_log("API get_availability.php - Teacher: $teacher_email, Has Calendar: " . 
          (!empty($teacherInfo['google_calendar_link']) ? 'Yes' : 'No') . 
          ", Lesson availability count: " . count($availability));

header('Content-Type: application/json');
echo json_encode([
    'success' => true, 
    'availability' => $availability,
    'uses_google_calendar' => !empty($teacherInfo['google_calendar_link'])
]);

$stmt->close();
$conn->close();
?>
