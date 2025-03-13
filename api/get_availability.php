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
          CASE WHEN p.google_calendar_link IS NOT NULL THEN 1 ELSE 0 END as from_google_calendar,
          l.id as lesson_id, l.stato
          FROM Disponibilita d
          JOIN Professori p ON d.teacher_email = p.email
          LEFT JOIN Lezioni l ON d.teacher_email = l.teacher_email 
                AND DATE(l.start_time) = DATE(CURDATE() + INTERVAL 
                    CASE 
                        WHEN DAYOFWEEK(CURDATE()) <= CASE 
                            WHEN d.giorno_settimana = 'lunedi' THEN 2
                            WHEN d.giorno_settimana = 'martedi' THEN 3
                            WHEN d.giorno_settimana = 'mercoledi' THEN 4
                            WHEN d.giorno_settimana = 'giovedi' THEN 5
                            WHEN d.giorno_settimana = 'venerdi' THEN 6
                            WHEN d.giorno_settimana = 'sabato' THEN 7
                            WHEN d.giorno_settimana = 'domenica' THEN 1
                        END
                        THEN CASE 
                            WHEN d.giorno_settimana = 'lunedi' THEN 2
                            WHEN d.giorno_settimana = 'martedi' THEN 3
                            WHEN d.giorno_settimana = 'mercoledi' THEN 4
                            WHEN d.giorno_settimana = 'giovedi' THEN 5
                            WHEN d.giorno_settimana = 'venerdi' THEN 6
                            WHEN d.giorno_settimana = 'sabato' THEN 7
                            WHEN d.giorno_settimana = 'domenica' THEN 1
                        END - DAYOFWEEK(CURDATE())
                        ELSE CASE 
                            WHEN d.giorno_settimana = 'lunedi' THEN 9
                            WHEN d.giorno_settimana = 'martedi' THEN 10
                            WHEN d.giorno_settimana = 'mercoledi' THEN 11
                            WHEN d.giorno_settimana = 'giovedi' THEN 12
                            WHEN d.giorno_settimana = 'venerdi' THEN 13
                            WHEN d.giorno_settimana = 'sabato' THEN 14
                            WHEN d.giorno_settimana = 'domenica' THEN 8
                        END - DAYOFWEEK(CURDATE())
                    END DAY)
                AND TIME(l.start_time) = d.ora_inizio
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
    $row['days_from_today'] = calculate_days_from_today($row['giorno_settimana']);
    $availability[] = $row;
}

// Sort availability by days from today (closest first)
usort($availability, function($a, $b) {
    return $a['days_from_today'] <=> $b['days_from_today'];
});

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

// Function to calculate days from today (0 = today, 1 = tomorrow, etc.)
function calculate_days_from_today($weekday) {
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
    
    if ($target_day_num >= $current_day_num) {
        return $target_day_num - $current_day_num; // Days ahead this week
    } else {
        return 7 - ($current_day_num - $target_day_num); // Days ahead next week
    }
}

$stmt->close();
$conn->close();
?>
