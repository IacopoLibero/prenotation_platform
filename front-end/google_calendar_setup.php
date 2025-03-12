<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore'){
    header('Location: ../index.php');
    exit;
}

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
    <title>Configura Google Calendar</title>
    <style>
        .calendar-section {
            margin: 30px 0;
        }
        .form-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        .form-title {
            color: #2da0a8;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        input[type="text"], input[type="time"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn-add {
            background-color: #2da0a8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-add:hover {
            background-color: #259199;
        }
        .checkbox-group {
            margin: 15px 0;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .checkbox-label input {
            margin-right: 10px;
        }
        .time-range-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }
        .time-range-row .form-group {
            flex: 1;
        }
        .info-box {
            background-color: #e6f7ff;
            border-left: 4px solid #1890ff;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
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
                <li><a href="google_calendar_setup.php">Google Calendar</a></li>
                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <h1>Integrazione con Google Calendar</h1>
            <p>Collega il tuo Google Calendar per gestire automaticamente la tua disponibilità</p>
            
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
                        
                        <button type="submit" class="btn-add">Salva e Sincronizza</button>
                    </form>
                </div>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>
    
    <script>
        document.getElementById('mattina').addEventListener('change', function() {
            document.getElementById('mattina_orari').style.display = this.checked ? 'flex' : 'none';
        });
        
        document.getElementById('pomeriggio').addEventListener('change', function() {
            document.getElementById('pomeriggio_orari').style.display = this.checked ? 'flex' : 'none';
        });
        
        document.getElementById('calendarForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const calendarLink = document.getElementById('calendar_link').value;
            const weekend = document.getElementById('weekend').checked;
            const mattina = document.getElementById('mattina').checked;
            const pomeriggio = document.getElementById('pomeriggio').checked;
            const oraInizioMattina = document.getElementById('ora_inizio_mattina').value;
            const oraFineMattina = document.getElementById('ora_fine_mattina').value;
            const oraInizioPomeriggio = document.getElementById('ora_inizio_pomeriggio').value;
            const oraFinePomeriggio = document.getElementById('ora_fine_pomeriggio').value;
            
            // Validazione base
            if (!calendarLink) {
                alert('Inserisci il link del tuo Google Calendar');
                return;
            }
            
            if (!mattina && !pomeriggio) {
                alert('Seleziona almeno una fascia oraria (mattina o pomeriggio)');
                return;
            }
            
            if (mattina && oraFineMattina <= oraInizioMattina) {
                alert('L\'ora di fine mattina deve essere successiva all\'ora di inizio');
                return;
            }
            
            if (pomeriggio && oraFinePomeriggio <= oraInizioPomeriggio) {
                alert('L\'ora di fine pomeriggio deve essere successiva all\'ora di inizio');
                return;
            }
            
            const formData = new FormData();
            formData.append('calendar_link', calendarLink);
            formData.append('weekend', weekend ? 1 : 0);
            formData.append('mattina', mattina ? 1 : 0);
            formData.append('pomeriggio', pomeriggio ? 1 : 0);
            formData.append('ora_inizio_mattina', oraInizioMattina);
            formData.append('ora_fine_mattina', oraFineMattina);
            formData.append('ora_inizio_pomeriggio', oraInizioPomeriggio);
            formData.append('ora_fine_pomeriggio', oraFinePomeriggio);
            
            // Mostriamo un messaggio di attesa
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Sincronizzazione in corso...';
            submitBtn.disabled = true;
            
            fetch('../api/save_google_calendar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                
                if (data.success) {
                    alert(data.message);
                    // Dopo il salvataggio, sincronizziamo il calendario
                    return fetch('../api/sync_google_calendar.php');
                } else {
                    alert(data.message);
                    throw new Error(data.message);
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Calendario sincronizzato con successo. Nuove disponibilità generate.');
                    window.location.href = 'disponibilita.php';
                } else {
                    alert('Errore durante la sincronizzazione: ' + data.message);
                }
            })
            .catch(error => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>
