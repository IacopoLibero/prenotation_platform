<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'studente'){
    header('Location: ../index.php');
    exit;
}

require_once '../connessione.php';

// Ottieni tutti gli insegnanti
$query = "SELECT username, email, google_calendar_link FROM Professori ORDER BY username";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/home.css">
    <title>Orari Insegnanti</title>
    <style>
        .teachers-section {
            margin: 30px 0;
        }
        .teacher-selector {
            margin-bottom: 30px;
            text-align: center;
        }
        .teacher-select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            min-width: 300px;
        }
        .availability-container {
            margin-top: 30px;
        }
        .day-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        .day-header {
            color: #2da0a8;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 1.2rem;
        }
        .time-slot {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #f0f0f0;
        }
        .time-slot:last-child {
            border-bottom: none;
        }
        .time-range {
            font-weight: 500;
        }
        .no-availability {
            text-align: center;
            padding: 50px;
            color: #666;
        }
        .google-badge {
            display: inline-block;
            background-color: #4285F4;
            color: white;
            padding: 2px 6px;ont-size: 0.7rem;
            border-radius: 3px;
            font-size: 0.8rem;er-radius: 3px;
        }
    </style>
</head>
<body>
    <header>round-color: #e6f7ff;
        <nav>rder-left: 4px solid #4285F4;
            <div class="logo">Programma Lezioni</div>adding: 10px 15px;
            <ul>        margin-top: 15px;
                <li><a href="home.php">Home</a></li>  margin-bottom: 20px;
                <li><a href="user_account.php">Account</a></li>r-radius: 4px;
                <li><a href="prenota_lezioni.php">Prenota Lezioni</a></li>
                <li><a href="../login/logout.php">Logout</a></li>
            </ul>gle-calendar-icon {
        </nav>
    </header>
    
            vertical-align: text-bottom;
        }
    </style>
</head>
<body>
    <header>
        <nav>eacher-selector">
            <div class="logo">Programma Lezioni</div>elect id="teacherSelect" class="teacher-select">
            <ul>        <option value="">Seleziona un insegnante</option>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>mlspecialchars($row['email']) ?>" data-has-calendar="<?= $row['google_calendar_link'] ? '1' : '0' ?>">
                <li><a href="prenota_lezioni.php">Prenota Lezioni</a></li>rs($row['email']) ?>)
                <li><a href="../login/logout.php">Logout</a></li>  </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div id="calendarInfoBox" style="display: none; text-align: center; margin-bottom: 20px;"><main>
                    <p>Questo insegnante utilizza Google Calendar per gestire la sua disponibilità.</p>tion>
                </div>
                p>Visualizza gli orari disponibili degli insegnanti</p>
                <div class="availability-container" id="availabilityContainer">            
                    <div class="no-availability"><div class="teachers-section">
                        <p>Seleziona un insegnante per visualizzare la sua disponibilità.</p>
                    </div>t" class="teacher-select">
                </div>value="">Seleziona un insegnante</option>
            </div>
        </section>mlspecialchars($row['email']) ?>" 
    </main>]) ? '1' : '0' ?>">
          <?= htmlspecialchars($row['username']) ?> 
    <footer>              <?= !empty($row['google_calendar_link']) ? '(Google Calendar)' : '' ?>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>     </option>
    </footer>           <?php endwhile; ?>
        </select>
    <script>
        document.getElementById('teacherSelect').addEventListener('change', function() {
            const teacherEmail = this.value;arInfoBox" class="google-calendar-info" style="display:none;">
            const hasCalendar = this.options[this.selectedIndex].getAttribute('data-has-calendar') === '1';e_Calendar_icon_%282020%29.svg" class="google-calendar-icon" alt="Google Calendar">
            const calendarInfoBox = document.getElementById('calendarInfoBox');Questo insegnante sincronizza la sua disponibilità con Google Calendar.
            
            // Mostra o nascondi l'info box del calendario
            calendarInfoBox.style.display = hasCalendar ? 'block' : 'none';er" id="availabilityContainer">
            ility">
            if (!teacherEmail) {nte per visualizzare la sua disponibilità.</p>
                document.getElementById('availabilityContainer').innerHTML = `
                    <div class="no-availability">
                        <p>Seleziona un insegnante per visualizzare la sua disponibilità.</p>
                    </div>
                `;
                return;
            }
            rogramma Lezioni. Tutti i diritti riservati.</p>
            fetch(`../api/get_availability.php?email=${encodeURIComponent(teacherEmail)}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('availabilityContainer');Id('teacherSelect').addEventListener('change', function() {
                    
                    if (data.success && data.availability && data.availability.length > 0) {ar = this.options[this.selectedIndex].getAttribute('data-has-calendar') === '1';
                        // Group availability by day of the weekarInfoBox = document.getElementById('calendarInfoBox');
                        const groupedByDay = {};
                        const dayNames = {o box del calendario
                            'lunedi': 'Lunedì', 'block' : 'none';
                            'martedi': 'Martedì',
            if (!teacherEmail) {
                document.getElementById('availabilityContainer').innerHTML = `
                    <div class="no-availability">enerdi': 'Venerdì',
                        <p>Seleziona un insegnante per visualizzare la sua disponibilità.</p>'sabato': 'Sabato',
                    </div>
                `;
                return;
            }
            Day[slot.giorno_settimana]) {
            fetch(`../api/get_availability.php?email=${encodeURIComponent(teacherEmail)}`)oupedByDay[slot.giorno_settimana] = [];
                .then(response => response.json())
                .then(data => {groupedByDay[slot.giorno_settimana].push(slot);
                    const container = document.getElementById('availabilityContainer');
                    
                    if (data.success && data.availability && data.availability.length > 0) {// Generate HTML for each day
                        // Group availability by day of the week
                        const groupedByDay = {};(const day in groupedByDay) {
                        const dayNames = {
                            'lunedi': 'Lunedì',
                            'martedi': 'Martedì',
                            'mercoledi': 'Mercoledì',
                            'giovedi': 'Giovedì',  
                            'venerdi': 'Venerdì',       groupedByDay[day].forEach(slot => {
                            'sabato': 'Sabato',              html += `
                            'domenica': 'Domenica'   <div class="time-slot">
                        };="time-range">${slot.ora_inizio} - ${slot.ora_fine}</span>
                        class="google-badge">Google Calendar</span>' : ''}
                        data.availability.forEach(slot => {
                            if (!groupedByDay[slot.giorno_settimana]) {
                                groupedByDay[slot.giorno_settimana] = [];;
                            }      
                            groupedByDay[slot.giorno_settimana].push(slot);         html += `</div>`;
                        });             }
                                   
                        // Generate HTML for each day                 container.innerHTML = html;
                        let html = '';             } else {
                        container.innerHTML = `                        for (const day in groupedByDay) {



















</html></body>    </script>        });                });                    `;                        </div>                            <p>Si è verificato un errore durante il recupero della disponibilità.</p>                        <div class="no-availability">                    document.getElementById('availabilityContainer').innerHTML = `                    console.error('Error:', error);                .catch(error => {                })                    }                        `;                            </div>                                <p>Questo insegnante non ha ancora impostato la sua disponibilità.</p>                            <div class="no-availability">                            html += `
                                <div class="day-card">
                                    <h3 class="day-header">${dayNames[day]}</h3>
                            `;
                            
                            groupedByDay[day].forEach(slot => {
                                html += `
                                    <div class="time-slot">
                                        <span class="time-range">${slot.ora_inizio} - ${slot.ora_fine}</span>
                                    </div>
                                `;
                            });
                            
                            html += `</div>`;
                        }
                        
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `
                            <div class="no-availability">
                                <p>Questo insegnante non ha ancora impostato la sua disponibilità.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('availabilityContainer').innerHTML = `
                        <div class="no-availability">
                            <p>Si è verificato un errore durante il recupero della disponibilità.</p>
                        </div>
                    `;
                });
        });
    </script>
</body>
</html>
