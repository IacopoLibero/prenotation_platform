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

// Add debug function 
function debug_log($message) {
    echo "<!-- DEBUG: $message -->\n";
}

// CRITICAL FIX: Get availability by joining both tables to see complete picture
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

// Count lessons for debugging
$total_lessons = $lessons_result->num_rows;
debug_log("Lezioni disponibili trovate: $total_lessons");

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

// Count available slots for debugging
$total_slots = $disp_result->num_rows;
debug_log("Pattern di disponibilità trovati: $total_slots");

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
    debug_log("Lezione trovata: {$row['giorno_settimana']} {$row['data']} {$row['ora_inizio']}-{$row['ora_fine']}");
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

// Debug the availability data structure
$days_with_data = [];
foreach ($availability_by_week as $week => $days) {
    foreach ($days as $day => $slots) {
        if (!empty($slots)) {
            if (!isset($days_with_data[$day])) {
                $days_with_data[$day] = [];
            }
            $days_with_data[$day][] = $week;
        }
    }
}

foreach ($days_with_data as $day => $weeks) {
    debug_log("Giorno $day presente nelle settimane: " . implode(", ", $weeks));
}
debug_log("Settimane con dati: " . count($availability_by_week));

// Translate day names
$day_names = [
    'lunedi' => 'Lunedì',
    'martedi' => 'Martedì',
    'mercoledi' => 'Mercoledì',
    'giovedi' => 'Giovedì',
    'venerdi' => 'Venerdì',
    'sabato' => 'Sabato',
    'domenica' => 'Domenica'
];
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
    <title>Disponibilità</title>
    <style>
        .availability-section {
            margin: 30px 0;
        }
        .status-box {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .status-box.success {
            background-color: #d4edda;
            color: #155724;
        }
        .status-box.warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-box.sync {
            background-color: #cce5ff;
            color: #004085;
        }
        .availability-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        .day-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            min-width: 220px;
            flex: 1;
        }
        .day-header {
            color: #2da0a8;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 1.2rem;
        }
        .time-slot {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #f0f0f0;
        }
        .time-slot:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .time-range {
            display: block;
            font-weight: 500;
            margin-bottom: 3px;
        }
        .date-badge {
            background-color: #f8f9fa;
            color: #555;
            font-size: 0.85em;
            padding: 2px 6px;
            border-radius: 3px;
            margin-right: 5px;
            border: 1px solid #ddd;
            display: inline-block;
        }
        .empty-day {
            color: #999;
            text-align: center;
            padding: 15px 0;
        }
        .btn-sync {
            background-color: #4285F4;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            margin: 10px 0;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-sync:hover {
            background-color: #3367d6;
        }
        .btn-sync img {
            height: 18px;
            width: 18px;
        }
        .no-availability {
            padding: 50px 20px;
            text-align: center;
            color: #666;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .setup-box {
            margin-top: 25px;
            text-align: center;
        }
        .pagination-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }
        .pagination-btn {
            background-color: #2da0a8;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .pagination-btn:hover {
            background-color: #218a92;
        }
        .pagination-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .page-indicator {
            font-weight: 500;
            color: #333;
        }
        .week-header {
            text-align: center;
            margin-bottom: 20px;
            font-weight: 500;
            color: #333;
        }
        .week-dates {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }
        /* Add debug panel styles */
        .debug-panel {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
        }
        .debug-panel h3 {
            margin-top: 0;
            color: #555;
        }
    </style>
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
            
            <!-- Debug Panel for Teacher - with enhanced information -->
            <div class="debug-panel">
                <h3>Informazioni diagnostiche</h3>
                <p>Pattern di disponibilità: <?php echo $total_slots; ?></p>
                <p>Lezioni disponibili: <?php echo $total_lessons; ?></p>
                <p>Ultimo aggiornamento: <?php echo date('Y-m-d H:i:s'); ?></p>
                <p>Email: <?php echo $email; ?></p>
                <p>Settimane generate: <?php echo count($availability_by_week); ?></p>
            </div>
            
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
    
    <script>
        // Variabili globali
        let currentWeek = 0;
        const availabilityData = <?php echo json_encode($availability_by_week); ?>;
        const dayNames = <?php echo json_encode($day_names); ?>;
        const maxWeeks = <?php echo count($availability_by_week); ?>;
        
        // Log data for debugging
        console.log("Available weeks:", maxWeeks);
        console.log("Availability data:", availabilityData);
        
        // Inizializza la pagina
        document.addEventListener('DOMContentLoaded', function() {
            renderWeek(currentWeek);
            renderPaginationControls();
        });
        
        // Sincronizzazione Google Calendar - update to use direct API path
        function syncGoogleCalendar() {
            const syncBtn = document.getElementById('syncBtn');
            const syncBtnEmpty = document.getElementById('syncBtnEmpty');
            const syncStatus = document.getElementById('syncStatus');
            
            // Disabilita il pulsante e mostra loading
            const buttons = [syncBtn, syncBtnEmpty].filter(button => button !== null);
            
            buttons.forEach(button => {
                if (button) {
                    button.disabled = true;
                    button.innerHTML = `
                        <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" alt="Google Calendar">
                        Sincronizzazione...
                    `;
                }
            });
            
            if (syncStatus) {
                syncStatus.style.display = 'inline';
                syncStatus.textContent = 'Sincronizzazione in corso...';
            }
            
            // Use direct path to API
            fetch('../api/sync_google_calendar.php?nocache=' + new Date().getTime())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (syncStatus) {
                            syncStatus.textContent = 'Sincronizzazione completata! Aggiornamento pagina...';
                        }
                        // Force a hard refresh after successful sync
                        setTimeout(() => {
                            window.location.href = window.location.pathname + '?refresh=' + new Date().getTime();
                        }, 1500);
                    } else {
                        if (syncStatus) {
                            syncStatus.textContent = 'Errore: ' + data.message;
                        }
                        buttons.forEach(button => {
                            if (button) {
                                button.innerHTML = `
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" alt="Google Calendar">
                                    Riprova
                                `;
                                button.disabled = false;
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (syncStatus) {
                        syncStatus.textContent = 'Errore: ' + error.message;
                    }
                    buttons.forEach(button => {
                        if (button) {
                            button.innerHTML = `
                                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" alt="Google Calendar">
                                Riprova
                            `;
                            button.disabled = false;
                        }
                    });
                });
        }
        
        // Listener per i pulsanti di sincronizzazione
        const syncBtn = document.getElementById('syncBtn');
        if (syncBtn) {
            syncBtn.addEventListener('click', syncGoogleCalendar);
        }
        
        const syncBtnEmpty = document.getElementById('syncBtnEmpty');
        if (syncBtnEmpty) {
            syncBtnEmpty.addEventListener('click', syncGoogleCalendar);
        }
        
        // Funzione per renderizzare i controlli di paginazione
        function renderPaginationControls() {
            const paginationContainer = document.getElementById('paginationControls');
            if (!paginationContainer) return;
            
            const weekNames = {
                0: 'Questa settimana',
                1: 'Prossima settimana',
                2: 'Tra due settimane',
                3: 'Tra tre settimane'
            };
            
            paginationContainer.innerHTML = `
                <button 
                    class="pagination-btn" 
                    onclick="changeWeek(${currentWeek - 1})"
                    ${currentWeek <= 0 ? 'disabled' : ''}>
                    &laquo; Settimana precedente
                </button>
                <span class="page-indicator">${weekNames[currentWeek] || `Settimana ${currentWeek + 1}`}</span>
                <button 
                    class="pagination-btn" 
                    onclick="changeWeek(${currentWeek + 1})"
                    ${currentWeek >= maxWeeks - 1 ? 'disabled' : ''}>
                    Settimana successiva &raquo;
                </button>
            `;
        }
        
        // Funzione per cambiare settimana
        function changeWeek(newWeek) {
            if (newWeek >= 0 && newWeek < maxWeeks) {
                currentWeek = newWeek;
                renderWeek(currentWeek);
                renderPaginationControls();
                window.scrollTo(0, 0); // Scroll to top
            }
        }
        
        // Funzione per renderizzare la settimana corrente
        function renderWeek(weekNumber) {
            const container = document.getElementById('weekContainer');
            if (!container) return;
            
            const weekData = availabilityData[weekNumber] || {};
            
            // Get the date range for this week
            let weekStart, weekEnd;
            let foundDates = false;
            
            // Find first and last date in this week's data
            for (const day in weekData) {
                if (weekData[day].length > 0) {
                    for (const slot of weekData[day]) {
                        const date = new Date(slot.data);
                        if (!weekStart || date < weekStart) {
                            weekStart = date;
                        }
                        if (!weekEnd || date > weekEnd) {
                            weekEnd = date;
                        }
                        foundDates = true;
                    }
                }
            }
            
            // Default dates if no slots found
            if (!foundDates) {
                const today = new Date();
                weekStart = new Date();
                weekStart.setDate(today.getDate() + (weekNumber * 7));
                weekEnd = new Date(weekStart);
                weekEnd.setDate(weekStart.getDate() + 6);
            }
            
            const weekDateRange = `${weekStart.toLocaleDateString('it-IT')} - ${weekEnd.toLocaleDateString('it-IT')}`;
            
            // Check if there are any slots for this week
            let hasSlots = false;
            for (const day in weekData) {
                if (weekData[day].length > 0) {
                    hasSlots = true;
                    break;
                }
            }
            
            if (!hasSlots) {
                container.innerHTML = `
                    <div class="week-header">
                        ${weekNumber === 0 ? 'Settimana corrente' : weekNumber === 1 ? 'Prossima settimana' : 'Settimana ' + (weekNumber + 1)}
                        <div class="week-dates">${weekDateRange}</div>
                    </div>
                    <div class="no-availability">
                        <p>Nessuna disponibilità per questa settimana.</p>
                    </div>
                `;
                return;
            }
            
            // Build HTML for availability
            let html = `
                <div class="week-header">
                    ${weekNumber === 0 ? 'Settimana corrente' : weekNumber === 1 ? 'Prossima settimana' : 'Settimana ' + (weekNumber + 1)}
                    <div class="week-dates">${weekDateRange}</div>
                </div>
                <div class="availability-container">
            `;
            
            // Order days correctly (Monday to Sunday)
            const orderedDays = ['lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica'];
            
            for (const day of orderedDays) {
                const slots = weekData[day] || [];
                if (slots.length === 0) continue;
                
                // Sort slots by time
                slots.sort((a, b) => a.ora_inizio.localeCompare(b.ora_inizio));
                
                html += `
                    <div class="day-card">
                        <h3 class="day-header">
                            ${dayNames[day]}
                            <span class="date-badge">${slots[0].data_formattata}</span>
                        </h3>
                `;
                
                slots.forEach(slot => {
                    html += `
                        <div class="time-slot">
                            <span class="time-range">${slot.ora_inizio} - ${slot.ora_fine}</span>
                        </div>
                    `;
                });
                
                html += `</div>`;
            }
            
            html += `</div>`;
            container.innerHTML = html;
        }
    </script>
</body>
</html>
