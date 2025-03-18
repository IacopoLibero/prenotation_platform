<?php
// Add cache-busting headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore'){
    header('Location: ../index.php');
    exit;
}

require_once '../connessione.php';

// Get availability by joining both tables 
$email = $_SESSION['email'];

// First, get scheduled lessons from Lezioni table
$lessons_query = "SELECT 
                    DATE_FORMAT(start_time, '%Y-%m-%d') as data,
                    DATE_FORMAT(start_time, '%d/%m/%Y') as data_formattata,
                    DATE_FORMAT(start_time, '%H:%i') as ora_inizio,
                    DATE_FORMAT(end_time, '%H:%i') as ora_fine,
                    CASE
                        WHEN DAYOFWEEK(start_time) = 2 THEN 'lunedi'
                        WHEN DAYOFWEEK(start_time) = 3 THEN 'martedi'
                        WHEN DAYOFWEEK(start_time) = 4 THEN 'mercoledi'
                        WHEN DAYOFWEEK(start_time) = 5 THEN 'giovedi'
                        WHEN DAYOFWEEK(start_time) = 6 THEN 'venerdi'
                        WHEN DAYOFWEEK(start_time) = 7 THEN 'sabato'
                        WHEN DAYOFWEEK(start_time) = 1 THEN 'domenica'
                    END as giorno_settimana,
                    stato,
                    id as lezione_id
                FROM Lezioni
                WHERE teacher_email = ? AND stato = 'disponibile'
                ORDER BY start_time";
                
$lessons_stmt = $conn->prepare($lessons_query);
$lessons_stmt->bind_param("s", $email);
$lessons_stmt->execute();
$lessons_result = $lessons_stmt->get_result();

// Count lessons
$total_lessons = $lessons_result->num_rows;

// Get availability patterns from Disponibilita table
$query = "SELECT d.id, d.giorno_settimana, d.ora_inizio, d.ora_fine 
          FROM Disponibilita d 
          WHERE d.teacher_email = ? 
          ORDER BY FIELD(d.giorno_settimana, 'lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica'), 
          d.ora_inizio";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$disp_result = $stmt->get_result();

// Count available slots
$total_slots = $disp_result->num_rows;

// Recupera il link di calendario Google
$query = "SELECT google_calendar_link FROM Professori WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$calendar_result = $stmt->get_result();
$calendar_row = $calendar_result->fetch_assoc();
$has_google_calendar = !empty($calendar_row['google_calendar_link']);

// Get lessons into a more usable format organized by date
$lessons_by_date = [];
while ($row = $lessons_result->fetch_assoc()) {
    $date_str = $row['data'];
    if (!isset($lessons_by_date[$date_str])) {
        $lessons_by_date[$date_str] = [];
    }
    $lessons_by_date[$date_str][] = $row;
}

// Organize lessons by week and day
$availability_by_week = [];
$now = new DateTime();
$weeks_to_show = 3;

// Process each lesson and organize by week
foreach ($lessons_by_date as $date_str => $slots) {
    $lesson_date = new DateTime($date_str);
    $diff = $now->diff($lesson_date);
    $days_diff = $diff->days;
    
    // Skip if in the past
    if ($diff->invert) {
        continue;
    }
    
    // Calculate which week this belongs to (0 = current week, 1 = next week, etc.)
    $week_number = floor($days_diff / 7);
    
    // Only show up to 3 weeks ahead
    if ($week_number >= $weeks_to_show) {
        continue;
    }
    
    // Get the day of week
    $day_name = '';
    switch ($lesson_date->format('N')) {
        case 1: $day_name = 'lunedi'; break;
        case 2: $day_name = 'martedi'; break;
        case 3: $day_name = 'mercoledi'; break;
        case 4: $day_name = 'giovedi'; break;
        case 5: $day_name = 'venerdi'; break;
        case 6: $day_name = 'sabato'; break;
        case 7: $day_name = 'domenica'; break;
    }
    
    // Initialize the week structure if needed
    if (!isset($availability_by_week[$week_number])) {
        $availability_by_week[$week_number] = [];
    }
    
    // Initialize the day structure if needed
    if (!isset($availability_by_week[$week_number][$day_name])) {
        $availability_by_week[$week_number][$day_name] = [];
    }
    
    // Add all slots for this day
    foreach ($slots as $slot) {
        $availability_by_week[$week_number][$day_name][] = $slot;
    }
}

// Translate day names
$day_translations = [
    'lunedi' => 'Lunedì',
    'martedi' => 'Martedì',
    'mercoledi' => 'Mercoledì',
    'giovedi' => 'Giovedì',
    'venerdi' => 'Venerdì',
    'sabato' => 'Sabato',
    'domenica' => 'Domenica'
];

// Get preferences
$query = "SELECT * FROM Preferenze_Disponibilita WHERE teacher_email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$preferences = $result->fetch_assoc() ?: [
    'weekend' => false,
    'mattina' => true,
    'pomeriggio' => true
];

// Week names for display
$week_names = [
    0 => 'Questa settimana',
    1 => 'Prossima settimana',
    2 => 'Tra due settimane',
    3 => 'Tra tre settimane'
];

// Get current date for calculations
$current_date = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Add cache-busting meta tags -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="stylesheet" href="../styles/home.css?v=<?php echo time(); ?>">
    <!-- Add the new external stylesheet -->
    <link rel="stylesheet" href="../styles/disponibilita.css?v=<?php echo time(); ?>">
    <title>Disponibilità</title>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Programma Lezioni</div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>
                <li><a href="gestione_lezioni.php">Gestisci Lezioni</a></li>
                <li><a href="disponibilita.php?refresh=<?php echo time(); ?>">Disponibilità</a></li>
                <?php if($has_google_calendar): ?>
                    <li><a href="google_calendar_setup.php">Google Calendar</a></li>
                <?php endif; ?>
                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <h1>La tua disponibilità</h1>
            <p>Visualizza e gestisci gli orari in cui sei disponibile per le lezioni</p>
            
            <div class="availability-section">
                <?php if($has_google_calendar): ?>
                    <div class="status-box sync">
                        <p><strong>Google Calendar sincronizzato ✓</strong></p>
                        <p>Stai sincronizzando la tua disponibilità con Google Calendar. Le nuove lezioni saranno generate in base al tuo calendario.</p>
                        <button id="syncBtn" class="btn-sync">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" alt="Google Calendar">
                            Sincronizza adesso
                        </button>
                        <span id="syncStatus" style="display: none; margin-left: 10px;"></span>
                    </div>
                <?php else: ?>
                    <div class="status-box warning">
                        <p><strong>Migliora con Google Calendar</strong></p>
                        <p>Collega il tuo Google Calendar per sincronizzare automaticamente la tua disponibilità con il tuo calendario personale.</p>
                        <a href="google_calendar_setup.php" class="btn-sync">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" alt="Google Calendar">
                            Configura Google Calendar
                        </a>
                    </div>
                <?php endif; ?>
                
                <h2>Disponibilità Attuali</h2>
                
                <?php 
                $has_any_availability = false;
                foreach($availability_by_week as $week_data) {
                    foreach($week_data as $day => $slots) {
                        if (!empty($slots)) {
                            $has_any_availability = true;
                            break 2;
                        }
                    }
                }
                
                if (!$has_any_availability): 
                ?>
                    <div class="no-availability">
                        <p>Non hai ancora impostato orari di disponibilità.</p>
                        <?php if($has_google_calendar): ?>
                            <p>Sincronizza il tuo Google Calendar per generare automaticamente le tue disponibilità.</p>
                            <button id="syncBtnEmpty" class="btn-sync">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" alt="Google Calendar">
                                Sincronizza adesso
                            </button>
                        <?php else: ?>
                            <p>Configura Google Calendar o aggiungi orari manualmente.</p>
                            <div class="setup-box">
                                <a href="google_calendar_setup.php" class="btn-sync">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" alt="Google Calendar">
                                    Configura Google Calendar
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Week navigation controls -->
                    <div id="paginationControls" class="pagination-controls">
                        <!-- Will be populated by JavaScript -->
                    </div>
                    
                    <div id="weekContainer">
                        <!-- Week content will be displayed here -->
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>
    
    <!-- Add the external JavaScript file -->
    <script src="../js/disponibilita.js?v=<?php echo time(); ?>"></script>
    <script>
        // Initialize JavaScript with PHP data
        document.addEventListener('DOMContentLoaded', function() {
            // Pass PHP data to JavaScript
            initAvailability(
                <?php echo json_encode($availability_by_week); ?>,
                <?php echo json_encode($day_translations); ?>,
                <?php echo count($availability_by_week); ?>
            );
        });
    </script>
</body>
</html>