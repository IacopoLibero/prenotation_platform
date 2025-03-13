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
    // Add date information to each slot
    $date = calculate_next_date_for_weekday($row['giorno_settimana']);
    $row['data'] = $date->format('Y-m-d');  // ISO format date
    $row['data_formattata'] = $date->format('d/m/Y');  // Localized format

    $availability[] = $row;
}

// Aggiungi anche l'informazione se l'insegnante usa Google Calendar
$query = "SELECT google_calendar_link FROM Professori WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $teacher_email);
$stmt->execute();
$result = $stmt->get_result();
$teacherInfo = $result->fetch_assoc();

// Log per debug
error_log("API get_availability.php - Teacher: $teacher_email, Has Calendar: " . 
          (!empty($teacherInfo['google_calendar_link']) ? 'Yes' : 'No') . 
          ", Availability count: " . count($availability));

header('Content-Type: application/json');
echo json_encode([
    'success' => true, 
    'availability' => $availability,
    'uses_google_calendar' => !empty($teacherInfo['google_calendar_link'])
]);

// Function to calculate the next occurrence of a given weekday
function calculate_next_date_for_weekday($weekday) {
    $weekdays = [
        'lunedi' => 1,
        'martedi' => 2,
        'mercoledi' => 3,
        'giovedi' => 4,
        'venerdi' => 5,
        'sabato' => 6,
        'domenica' => 7
    ];
    
    $today = new DateTime();
    $target_day_num = $weekdays[$weekday];
    $current_day_num = (int)$today->format('N'); // 1 (Monday) to 7 (Sunday)
    
    // Calculate days to add
    if ($target_day_num >= $current_day_num) {
        // The next occurrence is this week
        $days_to_add = $target_day_num - $current_day_num;
    } else {
        // The next occurrence is next week
        $days_to_add = 7 - ($current_day_num - $target_day_num);
    }
    
    // Create a new DateTime for the calculated date
    $next_date = clone $today;
    $next_date->modify("+$days_to_add days");
    
    return $next_date;
}

$stmt->close();
$conn->close();
?>
