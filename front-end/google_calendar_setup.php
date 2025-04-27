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

// Array per i calendari collegati
$linkedCalendars = [];
$calendario_selezionato_id = null;

// Per i professori, recupera le informazioni sui calendari collegati
if ($isTeacher) {
    require_once '../connessione.php';
    
    // Recupera tutti i calendari collegati per questo professore
    $query = "SELECT id, google_calendar_id, nome_calendario, ore_prima_evento, ore_dopo_evento, is_active 
              FROM Calendari_Professori 
              WHERE teacher_email = ?
              ORDER BY id ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $linkedCalendars[] = $row;
    }
    
    // Recupera anche le preferenze di disponibilità
    $query_pref = "SELECT weekend, mattina, pomeriggio, 
                   ora_inizio_mattina, ora_fine_mattina, 
                   ora_inizio_pomeriggio, ora_fine_pomeriggio, calendario_selezionato_id
                   FROM Preferenze_Disponibilita 
                   WHERE teacher_email = ?";
    $stmt_pref = $conn->prepare($query_pref);
    $stmt_pref->bind_param("s", $userEmail);
    $stmt_pref->execute();
    $result_pref = $stmt_pref->get_result();
    
    if ($result_pref->num_rows > 0) {
        $preferences = $result_pref->fetch_assoc();
        $calendario_selezionato_id = $preferences['calendario_selezionato_id'];
    } else {
        // Preferenze di default
        $preferences = [
            'weekend' => 0,
            'mattina' => 1,
            'pomeriggio' => 1,
            'ora_inizio_mattina' => '08:00:00',
            'ora_fine_mattina' => '13:00:00',
            'ora_inizio_pomeriggio' => '14:00:00',
            'ora_fine_pomeriggio' => '19:00:00',
            'calendario_selezionato_id' => null
        ];
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
            <h2>Impostazioni Calendari</h2>
            <div class="config-container">
                <form id="calendar-form">
                    <div id="calendarsContainer">
                    <?php if (count($linkedCalendars) > 0): ?>
                        <?php foreach ($linkedCalendars as $index => $cal): ?>
                            <div class="calendar-item" data-index="<?php echo $index; ?>">
                                <button type="button" class="btn-remove-calendar" data-calendar-id="<?php echo $cal['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                                
                                <div class="form-group">
                                    <label for="calendar-select-<?php echo $index; ?>">Calendario:</label>
                                    <select id="calendar-select-<?php echo $index; ?>" name="calendars[<?php echo $index; ?>][calendar_id]" required>
                                        <option value="">-- Seleziona un calendario --</option>
                                        <?php foreach ($calendars as $calendar): ?>
                                            <option value="<?php echo htmlspecialchars($calendar['id']); ?>" 
                                                    <?php echo ($calendar['id'] === $cal['google_calendar_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($calendar['summary']); ?> 
                                                <?php echo $calendar['primary'] ? '(Principale)' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="calendar-name-<?php echo $index; ?>" class="calendar-name-label">Nome del calendario:</label>
                                    <input type="text" id="calendar-name-<?php echo $index; ?>" 
                                           name="calendars[<?php echo $index; ?>][calendar_name]"
                                           value="<?php echo htmlspecialchars($cal['nome_calendario']); ?>" required>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group half">
                                        <label for="hours-before-<?php echo $index; ?>">Ore prima dell'evento:</label>
                                        <input type="number" id="hours-before-<?php echo $index; ?>" 
                                               name="calendars[<?php echo $index; ?>][hours_before]"
                                               value="<?php echo $cal['ore_prima_evento']; ?>" min="0" step="0.5">
                                        <small>Tempo di preparazione prima della lezione</small>
                                    </div>
                                    
                                    <div class="form-group half">
                                        <label for="hours-after-<?php echo $index; ?>">Ore dopo l'evento:</label>
                                        <input type="number" id="hours-after-<?php echo $index; ?>" 
                                               name="calendars[<?php echo $index; ?>][hours_after]"
                                               value="<?php echo $cal['ore_dopo_evento']; ?>" min="0" step="0.5">
                                        <small>Tempo di riposo dopo la lezione</small>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="calendars[<?php echo $index; ?>][is_active]" 
                                               <?php echo $cal['is_active'] ? 'checked' : ''; ?> value="1">
                                        Calendario attivo
                                    </label>
                                    <small>Se deselezionato, questo calendario verrà ignorato durante la sincronizzazione</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="radio" name="calendario_selezionato" 
                                               value="<?php echo $cal['id']; ?>"
                                               <?php echo ($calendario_selezionato_id == $cal['id']) ? 'checked' : ''; ?>>
                                        Utilizza questo calendario per le nuove lezioni
                                    </label>
                                    <small>Le nuove lezioni create verranno inserite in questo calendario</small>
                                </div>
                                
                                <input type="hidden" name="calendars[<?php echo $index; ?>][id]" value="<?php echo $cal['id']; ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="calendar-item" data-index="0">
                            <div class="form-group">
                                <label for="calendar-select-0">Seleziona il calendario:</label>
                                <select id="calendar-select-0" name="calendars[0][calendar_id]" required>
                                    <option value="">-- Seleziona un calendario --</option>
                                    <?php foreach ($calendars as $calendar): ?>
                                        <option value="<?php echo htmlspecialchars($calendar['id']); ?>">
                                            <?php echo htmlspecialchars($calendar['summary']); ?> 
                                            <?php echo $calendar['primary'] ? '(Principale)' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="calendar-name-0" class="calendar-name-label">Nome del calendario:</label>
                                <input type="text" id="calendar-name-0" name="calendars[0][calendar_name]" 
                                       value="Calendario Lezioni" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group half">
                                    <label for="hours-before-0">Ore prima dell'evento:</label>
                                    <input type="number" id="hours-before-0" name="calendars[0][hours_before]" 
                                           value="0" min="0" step="0.5">
                                    <small>Tempo di preparazione prima della lezione</small>
                                </div>
                                
                                <div class="form-group half">
                                    <label for="hours-after-0">Ore dopo l'evento:</label>
                                    <input type="number" id="hours-after-0" name="calendars[0][hours_after]" 
                                           value="0" min="0" step="0.5">
                                    <small>Tempo di riposo dopo la lezione</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="calendars[0][is_active]" checked value="1">
                                    Calendario attivo
                                </label>
                                <small>Se deselezionato, questo calendario verrà ignorato durante la sincronizzazione</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="radio" name="calendario_selezionato" value="new_0" checked>
                                    Utilizza questo calendario per le nuove lezioni
                                </label>
                                <small>Le nuove lezioni create verranno inserite in questo calendario</small>
                            </div>
                        </div>
                    <?php endif; ?>
                    </div>
                    
                    <button type="button" id="btn-add-calendar" class="btn-add">
                        <i class="fas fa-plus"></i> Aggiungi un altro calendario
                    </button>
                    
                    <h3 class="settings-title">Preferenze di Disponibilità</h3>
                    
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="weekend" name="weekend" value="1" 
                                   <?php echo isset($preferences) && $preferences['weekend'] ? 'checked' : ''; ?>>
                            Disponibile nei weekend (sabato e domenica)
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" id="mattina" name="mattina" value="1" 
                                   <?php echo isset($preferences) && $preferences['mattina'] ? 'checked' : ''; ?>>
                            Disponibile di mattina
                        </label>
                        
                        <div class="time-range-row" id="mattina-times" 
                             <?php echo isset($preferences) && !$preferences['mattina'] ? 'style="display:none"' : ''; ?>>
                            <div class="form-group">
                                <label for="ora-inizio-mattina">Ora inizio:</label>
                                <input type="time" id="ora-inizio-mattina" name="ora_inizio_mattina" 
                                       value="<?php echo isset($preferences) ? $preferences['ora_inizio_mattina'] : '08:00'; ?>">
                            </div>
                            <div class="form-group">
                                <label for="ora-fine-mattina">Ora fine:</label>
                                <input type="time" id="ora-fine-mattina" name="ora_fine_mattina" 
                                       value="<?php echo isset($preferences) ? $preferences['ora_fine_mattina'] : '13:00'; ?>">
                            </div>
                        </div>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" id="pomeriggio" name="pomeriggio" value="1" 
                                   <?php echo isset($preferences) && $preferences['pomeriggio'] ? 'checked' : ''; ?>>
                            Disponibile di pomeriggio
                        </label>
                        
                        <div class="time-range-row" id="pomeriggio-times" 
                             <?php echo isset($preferences) && !$preferences['pomeriggio'] ? 'style="display:none"' : ''; ?>>
                            <div class="form-group">
                                <label for="ora-inizio-pomeriggio">Ora inizio:</label>
                                <input type="time" id="ora-inizio-pomeriggio" name="ora_inizio_pomeriggio" 
                                       value="<?php echo isset($preferences) ? $preferences['ora_inizio_pomeriggio'] : '14:00'; ?>">
                            </div>
                            <div class="form-group">
                                <label for="ora-fine-pomeriggio">Ora fine:</label>
                                <input type="time" id="ora-fine-pomeriggio" name="ora_fine_pomeriggio" 
                                       value="<?php echo isset($preferences) ? $preferences['ora_fine_pomeriggio'] : '19:00'; ?>">
                            </div>
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
                <p>Le lezioni che crei sulla piattaforma verranno automaticamente sincronizzate con i tuoi Calendari Google.</p>
                <p>Il sistema terrà in considerazione:</p>
                <ul>
                    <li>Le tue lezioni programmate</li>
                    <li>Le prenotazioni degli studenti</li>
                    <li>Il tempo di preparazione e riposo impostato per ogni calendario</li>
                    <li>Le preferenze di disponibilità dei giorni della settimana</li>
                </ul>
                <p>Puoi sempre modificare i calendari selezionati o revocare l'accesso dalla tua <a href="user_account.php">pagina profilo</a>.</p>
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
