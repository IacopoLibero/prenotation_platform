<?php
session_start();
if(!isset($_SESSION['email'])){
    header('Location: ../index.php');
    exit;
}

$isTeacher = ($_SESSION['tipo'] === 'professore');
$userEmail = $_SESSION['email'];
$userType = $isTeacher ? 'professore' : 'studente';

// Includi le funzioni per interagire con Google Calendar
require_once '../google_calendar/token_storage.php';
require_once '../google_calendar/calendar_functions.php';

// Verifica se l'utente ha già autorizzato Google Calendar
$hasGoogleCalendar = hasValidOAuthTokens($userEmail, $userType);

// Se non è autorizzato, reindirizza alla pagina di autorizzazione
if (!$hasGoogleCalendar) {
    header('Location: ../google_calendar/google_auth.php');
    exit;
}

// Recupera i calendari dell'utente
$calendarsResponse = getUserCalendars($userEmail, $userType);
$calendars = $calendarsResponse['success'] ? $calendarsResponse['data'] : [];

// Per i professori, recupera le informazioni sul calendario collegato
$selectedCalendarId = '';
$calendarName = 'Calendario Lezioni';
$hoursBeforeEvent = 0;
$hoursAfterEvent = 0;

if ($isTeacher) {
    require_once '../connessione.php';
    
    $query = "SELECT google_calendar_id, nome_calendario, ore_prima_evento, ore_dopo_evento 
              FROM Calendari_Professori 
              WHERE teacher_email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $selectedCalendarId = $row['google_calendar_id'];
        $calendarName = $row['nome_calendario'];
        $hoursBeforeEvent = $row['ore_prima_evento'];
        $hoursAfterEvent = $row['ore_dopo_evento'];
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurazione Google Calendar</title>
    <link rel="stylesheet" href="../styles/home.css">
    <link rel="stylesheet" href="../styles/google_calendar_setup.css">
    <!-- Font Awesome per le icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Include ad handler script -->
    <script src="../js/ad-handler.js?v=<?php echo time(); ?>"></script>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Google Calendar</div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Il Mio Profilo</a></li>
                <?php if($isTeacher): ?>
                    <li><a href="gestione_lezioni.php">Gestisci Lezioni</a></li>
                    <li><a href="disponibilita.php">Disponibilità</a></li>
                <?php else: ?>
                    <li><a href="orari_insegnanti.php">Orari Insegnanti</a></li>
                    <li><a href="storico_lezioni.php">Storico Lezioni</a></li>
                <?php endif; ?>
                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section class="calendar-header">
            <h1>Configurazione Google Calendar</h1>
            <div class="user-type-badge <?php echo $isTeacher ? 'teacher' : 'student'; ?>">
                <?php echo $isTeacher ? 'Professore' : 'Studente'; ?>
            </div>
        </section>
        
        <!-- Contenitore annunci -->
        <?php include_once('../includes/ad-container.php'); ?>
        
        <section class="calendar-status">
            <h2>Stato della Connessione</h2>
            <div id="connection-status" class="status-loading">
                <div class="status-icon">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <div class="status-text">
                    Verifica della connessione in corso...
                </div>
            </div>
        </section>
        
        <?php if ($isTeacher): ?>
        <section class="calendar-config">
            <h2>Impostazioni Calendario</h2>
            <div class="config-container">
                <form id="calendar-form">
                    <div class="form-group">
                        <label for="calendar-select">Seleziona il calendario da utilizzare:</label>
                        <select id="calendar-select" name="calendar_id" required>
                            <option value="">-- Seleziona un calendario --</option>
                            <?php foreach ($calendars as $calendar): ?>
                                <option value="<?php echo htmlspecialchars($calendar['id']); ?>" 
                                        <?php echo ($calendar['id'] === $selectedCalendarId) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($calendar['summary']); ?> 
                                    <?php echo $calendar['primary'] ? '(Principale)' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="calendar-name">Nome del calendario:</label>
                        <input type="text" id="calendar-name" name="calendar_name" 
                               value="<?php echo htmlspecialchars($calendarName); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="hours-before">Ore prima dell'evento:</label>
                            <input type="number" id="hours-before" name="hours_before" 
                                   value="<?php echo $hoursBeforeEvent; ?>" min="0" step="0.5">
                            <small>Tempo di preparazione prima della lezione</small>
                        </div>
                        
                        <div class="form-group half">
                            <label for="hours-after">Ore dopo l'evento:</label>
                            <input type="number" id="hours-after" name="hours_after" 
                                   value="<?php echo $hoursAfterEvent; ?>" min="0" step="0.5">
                            <small>Tempo di riposo dopo la lezione</small>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" id="save-calendar" class="btn-primary">
                            <i class="fas fa-save"></i> Salva Impostazioni
                        </button>
                        <a href="#" id="btn-test-sync" class="btn-secondary">
                            <i class="fas fa-sync-alt"></i> Test Sincronizzazione
                        </a>
                    </div>
                </form>
            </div>
        </section>
        
        <section class="calendar-info">
            <h2>Come Funziona</h2>
            <div class="info-container">
                <p>Le lezioni che crei sulla piattaforma verranno automaticamente sincronizzate con il tuo Google Calendar.</p>
                <p>Il sistema terrà in considerazione:</p>
                <ul>
                    <li>Le tue lezioni programmate</li>
                    <li>Le prenotazioni degli studenti</li>
                    <li>Il tempo di preparazione e riposo impostato</li>
                </ul>
                <p>Puoi sempre modificare il calendario selezionato o revocare l'accesso dalla tua <a href="user_account.php">pagina profilo</a>.</p>
            </div>
        </section>
        <?php else: ?>
        <!-- Sezione per studenti -->
        <section class="calendar-info">
            <h2>Come Funziona</h2>
            <div class="info-container">
                <p>Le lezioni che prenoti verranno automaticamente aggiunte al tuo Google Calendar.</p>
                <p>Questo ti aiuterà a:</p>
                <ul>
                    <li>Tenere traccia di tutte le lezioni prenotate</li>
                    <li>Ricevere notifiche per le prossime lezioni</li>
                    <li>Gestire meglio il tuo tempo di studio</li>
                </ul>
                <p>Puoi sempre revocare l'accesso dalla tua <a href="user_account.php">pagina profilo</a>.</p>
                
                <div class="test-sync-container">
                    <a href="#" id="btn-test-sync" class="btn-secondary">
                        <i class="fas fa-sync-alt"></i> Test Sincronizzazione
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>
    
    <script src="../js/google_calendar_setup.js?v=<?php echo time(); ?>"></script>
</body>
</html>
