<?php
//disponibilità fiunziona
// Add cache-busting headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore'){
    header('Location: ../index.php');
    exit;
}
$isTeacher = ($_SESSION['tipo'] === 'professore');
require_once '../connessione.php';

// Get availability by joining both tables 
$email = $_SESSION['email'];

// Recupera i calendari Google
$query = "SELECT COUNT(*) AS calendar_count FROM Calendari_Professori WHERE teacher_email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$calendar_result = $stmt->get_result();
$calendar_row = $calendar_result->fetch_assoc();
$has_google_calendar = $calendar_row['calendar_count'] > 0;

// Forza sempre l'uso della modalità in tempo reale
$useRealTime = true;

// Week names for display
$week_names = [
    0 => 'Questa settimana',
    1 => 'Prossima settimana',
    2 => 'Tra due settimane',
    3 => 'Tra tre settimane'
];

// Day translations
$day_translations = [
    'lunedi' => 'Lunedì',
    'martedi' => 'Martedì',
    'mercoledi' => 'Mercoledì',
    'giovedi' => 'Giovedì',
    'venerdi' => 'Venerdì',
    'sabato' => 'Sabato',
    'domenica' => 'Domenica'
];

// Get current date for calculations
$current_date = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/home.css">
    <link rel="stylesheet" href="../styles/disponibilita.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Disponibilità</title>
    <!-- Include ad handler script -->
    <script src="../js/ad-handler.js?v=<?php echo time(); ?>"></script>
    <style>
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border-left-color: #2da0a8;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        .loading-container {
            text-align: center;
            padding: 40px 0;
        }
        .realtime-badge {
            background-color: #4285F4;
            color: white;
            font-size: 0.7em;
            padding: 2px 5px;
            border-radius: 3px;
            margin-left: 5px;
            vertical-align: middle;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body data-realtime="true">
    <header>
        <nav>
            <div class="logo">Disponibilita</div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>
                <li><a href="gestione_lezioni.php">Gestisci Lezioni</a></li>
                <?php if($has_google_calendar): ?>
                    <li><a href="google_calendar_setup.php">Google Calendar</a></li>
                <?php endif; ?>
                <li><a href="prenotazioni.php">Prenotazioni</a></li>
                <li><a href="gestione_studenti.php">Studenti</a></li>
                <li><a href="report.php">Report</a></li>
                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            
            <!-- Include standardized ad container -->
            <?php include_once('../includes/ad-container.php'); ?>
            
            <div class="availability-section">
                <?php if($has_google_calendar): ?>
                    <div class="status-box sync">
                        <p><strong>Google Calendar sincronizzato in tempo reale ✓</strong></p>
                        <p>Stai visualizzando la disponibilità in tempo reale dal tuo Google Calendar.</p>
                        <button id="syncBtn" class="btn-sync">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" alt="Google Calendar">
                            Aggiorna dati in tempo reale
                        </button>
                        <span id="syncStatus" style="display: none; margin-left: 10px;"></span>
                    </div>
                <?php else: ?>
                    <div class="status-box warning">
                        <p><strong>Configurazione Google Calendar richiesta</strong></p>
                        <p>È necessario collegare il tuo Google Calendar per visualizzare e gestire la tua disponibilità.</p>
                        <a href="google_calendar_setup.php" class="btn-sync">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" alt="Google Calendar">
                            Configura Google Calendar
                        </a>
                    </div>
                <?php endif; ?>
                
                <h2>Disponibilità in tempo reale</h2>
                
                <?php if($has_google_calendar): ?>
                    <div id="paginationControls" class="pagination-controls">
                        <!-- Will be populated by JavaScript -->
                    </div>
                    
                    <div id="weekContainer">
                        <div class="loading-container">
                            <div class="spinner"></div>
                            <p>Caricamento disponibilità in tempo reale da Google Calendar...</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-availability">
                        <p>Non puoi visualizzare la disponibilità senza configurare Google Calendar.</p>
                        <div class="setup-box">
                            <a href="google_calendar_setup.php" class="btn-sync">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" alt="Google Calendar">
                                Configura Google Calendar ora
                            </a>
                        </div>
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
            // Se c'è Google Calendar configurato, carica immediatamente i dati in tempo reale
            <?php if($has_google_calendar): ?>
            // Imposta un piccolo timeout per dare tempo alla pagina di renderizzarsi
            setTimeout(function() {
                if (typeof loadRealTimeAvailability === 'function') {
                    loadRealTimeAvailability();
                }
            }, 100);
            <?php endif; ?>
        });
    </script>
</body>
</html>