<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore'){
    header('Location: ../index.php');
    exit;
}

require_once '../connessione.php';

// Ottieni le disponibilità del professore
$email = $_SESSION['email'];
$query = "SELECT d.id, d.giorno_settimana, d.ora_inizio, d.ora_fine 
          FROM Disponibilita d 
          WHERE d.teacher_email = ? 
          ORDER BY FIELD(d.giorno_settimana, 'lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica'), 
          d.ora_inizio";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Recupera il link di calendario Google
$query = "SELECT google_calendar_link FROM Professori WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$calendar_result = $stmt->get_result();
$calendar_row = $calendar_result->fetch_assoc();
$has_google_calendar = !empty($calendar_row['google_calendar_link']);

// Funzione per ottenere la prossima data per un giorno della settimana
function get_next_date_for_weekday($weekday) {
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

// Organizza le disponibilità per giorno della settimana
$availability_by_day = [];
$days_of_week = ['lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica'];

// Get today's day number (1 = Monday, 7 = Sunday)
$today_day_num = (int)(new DateTime())->format('N');

// Reorder days to start from tomorrow
$reordered_days = [];
for ($i = 0; $i < 7; $i++) {
    $day_index = ($today_day_num + $i) % 7; // Wrap around after 7
    if ($day_index == 0) $day_index = 7; // Convert 0 to 7 for Sunday
    $reordered_days[] = $days_of_week[$day_index - 1];
}

// Initialize empty arrays for each day
foreach ($days_of_week as $day) {
    $availability_by_day[$day] = [];
}

while ($row = $result->fetch_assoc()) {
    $day = $row['giorno_settimana'];
    // Calculate date for this weekday
    $next_date = get_next_date_for_weekday($day);
    $row['data'] = $next_date->format('Y-m-d');
    $row['data_formattata'] = $next_date->format('d/m/Y');
    
    $availability_by_day[$day][] = $row;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/home.css">
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
                <li><a href="disponibilita.php">Disponibilità</a></li>
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
                foreach($availability_by_day as $day => $slots) {
                    if (!empty($slots)) {
                        $has_any_availability = true;
                        break;
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
                    <div class="availability-container">
                        <?php 
                        $day_names = [
                            'lunedi' => 'Lunedì',
                            'martedi' => 'Martedì',
                            'mercoledi' => 'Mercoledì',
                            'giovedi' => 'Giovedì',
                            'venerdi' => 'Venerdì',
                            'sabato' => 'Sabato',
                            'domenica' => 'Domenica'
                        ];
                        
                        // Use the reordered days array
                        foreach($reordered_days as $day): 
                            // Skip today as we want to show from tomorrow forward
                            if ($day == $days_of_week[$today_day_num - 1]) continue;
                            
                            $slots = $availability_by_day[$day];
                            if (empty($slots)) continue; // Skip days with no slots
                        ?>
                            <div class="day-card">
                                <h3 class="day-header">
                                    <?= $day_names[$day] ?>
                                    <?php if(!empty($slots)): ?>
                                        <span class="date-badge"><?= $slots[0]['data_formattata'] ?></span>
                                    <?php endif; ?>
                                </h3>
                                <?php if(!empty($slots)): ?>
                                    <?php foreach($slots as $slot): ?>
                                        <div class="time-slot">
                                            <span class="time-range"><?= $slot['ora_inizio'] ?> - <?= $slot['ora_fine'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-day">Nessuna disponibilità</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>
    
    <script>
        // Sincronizzazione Google Calendar
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
            
            // Chiamata API per la sincronizzazione
            fetch('../api/sync_google_calendar.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (syncStatus) {
                            syncStatus.textContent = 'Sincronizzazione completata!';
                        }
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
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
    </script>
</body>
</html>
