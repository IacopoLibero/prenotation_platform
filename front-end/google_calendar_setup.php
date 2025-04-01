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

// Recupera il link di calendario Google già salvato
$query = "SELECT google_calendar_link FROM Professori WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$calendar_link = $row['google_calendar_link'] ?? '';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/home.css">
    <link rel="stylesheet" href="../styles/google_calendar_setup.css?v=<?php echo time(); ?>">
    <title>Configura Google Calendar</title>
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
            <p>Collega il tuo Google Calendar per gestire automaticamente la tua disponibilità</p>
            
            <!-- Ad container with proper styling -->
            <div class="ad-container" style="text-align: center; margin: 20px auto; max-width: 300px; min-height: 250px; overflow: hidden;">
                <script>
                    !function(d,l,e,s,c){
                        e=d.createElement("script");
                        e.src="//ad.altervista.org/js.ad/size=300X250/?ref="+encodeURIComponent(l.hostname+l.pathname)+"&r="+Date.now();
                        s=d.scripts;
                        c=d.currentScript||s[s.length-1];
                        c.parentNode.insertBefore(e,c)
                    }(document,location)
                </script>
            </div>
            
            <div class="calendar-section">
                <div class="form-container">
                    <h2 class="form-title">Collega il tuo calendario Google</h2>
                    
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
                        <div class="form-group">
                            <label for="calendar_link">Link iCal Google Calendar:</label>
                            <input type="text" id="calendar_link" name="calendar_link" value="<?php echo htmlspecialchars($calendar_link); ?>" placeholder="https://calendar.google.com/calendar/ical/..." required>
                        </div>
                        
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
