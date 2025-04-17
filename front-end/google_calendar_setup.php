<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore'){
    header('Location: ../index.php');
    
}
$isTeacher = ($_SESSION['tipo'] === 'professore');
require_once '../connessione.php';

// Recupera le preferenze esistenti del professore
$email = $_SESSION['email'];
$query = "SELECT * FROM Preferenze_Disponibilita WHERE teacher_email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$preferences = $result->num_rows > 0 ? $result->fetch_assoc() : null;

// Recupera tutti i calendari Google già salvati per questo professore
$query = "SELECT id, google_calendar_link, nome_calendario FROM Calendari_Professori WHERE teacher_email = ? ORDER BY id";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$calendari = [];
while ($row = $result->fetch_assoc()) {
    $calendari[] = $row;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/home.css">
    <link rel="stylesheet" href="../styles/google_calendar_setup.css?v=<?php echo time(); ?>">
    <title>Configura Google Calendar</title>
    <!-- Include ad handler script -->
    <script src="../js/ad-handler.js?v=<?php echo time(); ?>"></script>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Google calendar</div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>
                <?php if($isTeacher): ?>
                    <li><a href="gestione_lezioni.php">Gestisci Lezioni</a></li>
                    <li><a href="disponibilita.php">Disponibilità</a></li>
                    <li><a href="prenotazioni.php">Prenotazioni</a></li>
                    <li><a href="gestione_studenti.php">Studenti</a></li>
                    <li><a href="report.php">Report</a></li>
                <?php endif; ?>

                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
   
    <main>
        <section>
            
            <!-- Include standardized ad container -->
            <?php include_once('../includes/ad-container.php'); ?>
            
            <div class="calendar-section">
                <div class="form-container">
                    <h2 class="form-title">Collega i tuoi calendari Google</h2>
                    
                    <div class="info-box">
                        <p>Per collegare il tuo Google Calendar:</p>
                        <ol>
                            <li>Apri Google Calendar</li>
                            <li>Clicca sull'icona delle impostazioni (⚙️) e seleziona "Impostazioni"</li>
                            <li>Nella colonna di sinistra, sotto "Impostazioni per i miei calendari", seleziona il tuo calendario</li>
                            <li>Scorri fino a "Integrazione calendario" e copia il link "Indirizzo pubblico in formato iCal"</li>
                            <li>Incolla il link qui sotto</li>
                        </ol>
                    </div>
                    
                    <form id="calendarForm">
                        <div id="calendarsContainer">
                            <?php if (empty($calendari)): ?>
                            <!-- Primo calendario (sempre presente) -->
                            <div class="calendar-item">
                                <div class="form-group">
                                    <label for="calendar_link_0">Link iCal Google Calendar:</label>
                                    <input type="text" id="calendar_link_0" name="calendar_links[]" value="" placeholder="https://calendar.google.com/calendar/ical/..." required>
                                    <input type="hidden" name="calendar_ids[]" value="0">
                                    <label for="calendar_nome_0" class="calendar-name-label">Nome (opzionale):</label>
                                    <input type="text" id="calendar_nome_0" name="calendar_names[]" value="Calendario" placeholder="Es: Personale, Lavoro, ecc.">
                                </div>
                            </div>
                            <?php else: ?>
                            <!-- Calendari esistenti -->
                            <?php foreach ($calendari as $index => $calendario): ?>
                            <div class="calendar-item">
                                <div class="form-group">
                                    <label for="calendar_link_<?php echo $index; ?>">Link iCal Google Calendar:</label>
                                    <input type="text" id="calendar_link_<?php echo $index; ?>" name="calendar_links[]" value="<?php echo htmlspecialchars($calendario['google_calendar_link']); ?>" placeholder="https://calendar.google.com/calendar/ical/..." required>
                                    <input type="hidden" name="calendar_ids[]" value="<?php echo $calendario['id']; ?>">
                                    <label for="calendar_nome_<?php echo $index; ?>" class="calendar-name-label">Nome (opzionale):</label>
                                    <input type="text" id="calendar_nome_<?php echo $index; ?>" name="calendar_names[]" value="<?php echo htmlspecialchars($calendario['nome_calendario']); ?>" placeholder="Es: Personale, Lavoro, ecc.">
                                    <?php if ($index > 0): ?>
                                    <button type="button" class="btn-remove-calendar" onclick="removeCalendarItem(this)">Rimuovi</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" id="addCalendarBtn" class="btn-secondary">Aggiungi un altro calendario</button>
                        
                        <h3>Preferenze di disponibilità</h3>
                        
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="weekend" name="weekend" <?php echo ($preferences && $preferences['weekend']) ? 'checked' : ''; ?>>
                                Includi fine settimana (sabato e domenica)
                            </label>
                        </div>
                        
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="mattina" name="mattina" <?php echo (!$preferences || $preferences['mattina']) ? 'checked' : ''; ?>>
                                Disponibile di mattina
                            </label>
                            
                            <div class="time-range-row" id="mattina_orari" <?php echo (!$preferences || $preferences['mattina']) ? '' : 'style="display:none;"'; ?>>
                                <div class="form-group">
                                    <label for="ora_inizio_mattina">Dalle:</label>
                                    <input type="time" id="ora_inizio_mattina" name="ora_inizio_mattina" value="<?php echo $preferences ? $preferences['ora_inizio_mattina'] : '08:00'; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="ora_fine_mattina">Alle:</label>
                                    <input type="time" id="ora_fine_mattina" name="ora_fine_mattina" value="<?php echo $preferences ? $preferences['ora_fine_mattina'] : '13:00'; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="pomeriggio" name="pomeriggio" <?php echo (!$preferences || $preferences['pomeriggio']) ? 'checked' : ''; ?>>
                                Disponibile di pomeriggio
                            </label>
                            
                            <div class="time-range-row" id="pomeriggio_orari" <?php echo (!$preferences || $preferences['pomeriggio']) ? '' : 'style="display:none;"'; ?>>
                                <div class="form-group">
                                    <label for="ora_inizio_pomeriggio">Dalle:</label>
                                    <input type="time" id="ora_inizio_pomeriggio" name="ora_inizio_pomeriggio" value="<?php echo $preferences ? $preferences['ora_inizio_pomeriggio'] : '14:00'; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="ora_fine_pomeriggio">Alle:</label>
                                    <input type="time" id="ora_fine_pomeriggio" name="ora_fine_pomeriggio" value="<?php echo $preferences ? $preferences['ora_fine_pomeriggio'] : '19:00'; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="buffer-options">
                            <h3>Tempo di buffer tra eventi</h3>
                            <p class="info-text">Imposta un tempo di buffer prima e dopo gli eventi del tuo calendario per evitare sovrapposizioni.</p>
                            
                            <div class="buffer-option">
                                <label for="ore_prima_evento">Non disponibile prima di un evento per:</label>
                                <input type="number" id="ore_prima_evento" name="ore_prima_evento" min="0" max="12" step="0.5" class="buffer-input" value="<?php echo $preferences ? $preferences['ore_prima_evento'] : '0'; ?>"> ore
                                <p class="help-text">Ti darà tempo per prepararti prima dell'evento.</p>
                            </div>
                            
                            <div class="buffer-option">
                                <label for="ore_dopo_evento">Non disponibile dopo un evento per:</label>
                                <input type="number" id="ore_dopo_evento" name="ore_dopo_evento" min="0" max="12" step="0.5" class="buffer-input" value="<?php echo $preferences ? $preferences['ore_dopo_evento'] : '0'; ?>"> ore
                                <p class="help-text">Ti darà tempo per riposare dopo l'evento.</p>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-add">Salva e Sincronizza</button>
                    </form>
                </div>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>
    
    <script src="../js/google_calendar_setup.js?v=<?php echo time(); ?>"></script>
</body>
</html>
