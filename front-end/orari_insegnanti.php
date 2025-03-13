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
            font-size: 0.7rem;
            padding: 2px 5px;
            border-radius: 3px;
            margin-left: 6px;
            vertical-align: middle;
        }
        .google-calendar-info {
            background-color: #e6f7ff;
            border-left: 4px solid #4285F4;
            padding: 10px 15px;
            margin-top: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .google-calendar-icon {
            height: 16px;
            width: 16px;
            margin-right: 5px;
            vertical-align: text-bottom;
        }
        .date-badge {
            background-color: #f8f9fa;
            color: #555;
            font-size: 0.9em;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 8px;
            border: 1px solid #ddd;
        }
        .booking-btn {
            background-color: #2da0a8;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-left: 10px;
        }
        
        .booking-btn:hover {
            background-color: #259199;
        }
        
        .booking-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 10px;
        }
        
        .pagination-btn {
            background-color: #f1f1f1;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .pagination-btn:hover {
            background-color: #e9e9e9;
        }
        
        .pagination-btn:disabled {
            background-color: #f9f9f9;
            color: #aaa;
            cursor: not-allowed;
        }
        
        .page-indicator {
            padding: 8px 15px;
            color: #666;
        }

        .booked-slot {
            background-color: #f8f9fa;
            opacity: 0.7;
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
                <li><a href="prenota_lezioni.php">Prenota Lezioni</a></li>
                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <h1>Orari Insegnanti</h1>
            <p>Visualizza gli orari disponibili degli insegnanti</p>
            
            <div class="teachers-section">
                <div class="teacher-selector">
                    <select id="teacherSelect" class="teacher-select">
                        <option value="">Seleziona un insegnante</option>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['email']) ?>" 
                                    data-has-calendar="<?= !empty($row['google_calendar_link']) ? '1' : '0' ?>">
                                <?= htmlspecialchars($row['username']) ?> 
                                <?= !empty($row['google_calendar_link']) ? '(Google Calendar)' : '' ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div id="calendarInfoBox" class="google-calendar-info" style="display:none;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" class="google-calendar-icon" alt="Google Calendar">
                    Questo insegnante sincronizza la sua disponibilità con Google Calendar.
                </div>
                
                <div class="availability-container" id="availabilityContainer">
                    <div class="no-availability">
                        <p>Seleziona un insegnante per visualizzare la sua disponibilità.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>

    <script>
        // Global variables for pagination
        let currentPage = 1;
        let daysPerPage = 7;
        let allAvailability = [];
        let currentTeacherEmail = '';
        
        document.getElementById('teacherSelect').addEventListener('change', function() {
            currentTeacherEmail = this.value;
            const hasCalendar = this.options[this.selectedIndex].getAttribute('data-has-calendar') === '1';
            const calendarInfoBox = document.getElementById('calendarInfoBox');
            
            // Reset pagination
            currentPage = 1;
            
            // Mostra o nascondi l'info box del calendario
            calendarInfoBox.style.display = hasCalendar ? 'block' : 'none';
            
            if (!currentTeacherEmail) {
                document.getElementById('availabilityContainer').innerHTML = `
                    <div class="no-availability">
                        <p>Seleziona un insegnante per visualizzare la sua disponibilità.</p>
                    </div>
                `;
                return;
            }
            
            // Mostra un messaggio di caricamento
            document.getElementById('availabilityContainer').innerHTML = `
                <div class="no-availability">
                    <p>Caricamento disponibilità in corso...</p>
                </div>
            `;
            
            loadAvailability();
        });
        
        function loadAvailability() {
            fetch(`../api/get_availability.php?email=${encodeURIComponent(currentTeacherEmail)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Errore nella risposta del server');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log("Dati ricevuti:", data); // Debug
                    
                    if (data.success && data.availability && data.availability.length > 0) {
                        // Store all availability data
                        allAvailability = data.availability;
                        
                        // Render the current page
                        renderAvailabilityPage(currentPage);
                    } else {
                        // Messaggio personalizzato in base a se l'insegnante usa Google Calendar o meno
                        const container = document.getElementById('availabilityContainer');
                        if (data.uses_google_calendar) {
                            container.innerHTML = `
                                <div class="no-availability">
                                    <p>Questo insegnante utilizza Google Calendar ma non ha ancora disponibilità sincronizzate.</p>
                                    <p>Ti consigliamo di riprovare più tardi o contattare direttamente l'insegnante.</p>
                                </div>
                            `;
                        } else {
                            container.innerHTML = `
                                <div class="no-availability">
                                    <p>Questo insegnante non ha ancora impostato la sua disponibilità.</p>
                                </div>
                            `;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('availabilityContainer').innerHTML = `
                        <div class="no-availability">
                            <p>Si è verificato un errore durante il recupero della disponibilità: ${error.message}</p>
                        </div>
                    `;
                });
        }
        
        function renderAvailabilityPage(page) {
            const container = document.getElementById('availabilityContainer');
            const startIdx = (page - 1) * daysPerPage;
            const endIdx = Math.min(startIdx + daysPerPage, allAvailability.length);
            
            // Group availability by date
            const groupedByDate = {};
            const dayNames = {
                'lunedi': 'Lunedì',
                'martedi': 'Martedì',
                'mercoledi': 'Mercoledì',
                'giovedi': 'Giovedì',
                'venerdi': 'Venerdì',
                'sabato': 'Sabato',
                'domenica': 'Domenica'
            };
            
            // Only group the dates for current page
            const currentPageAvailability = allAvailability.slice(startIdx, endIdx);
            
            currentPageAvailability.forEach(slot => {
                if (!groupedByDate[slot.data]) {
                    groupedByDate[slot.data] = {
                        day: dayNames[slot.giorno_settimana],
                        date: slot.data_formattata,
                        slots: []
                    };
                }
                groupedByDate[slot.data].slots.push(slot);
            });
            
            // Generate HTML for this page's dates
            let html = '';
            const sortedDates = Object.keys(groupedByDate).sort(); // Sort by date
            
            sortedDates.forEach(date => {
                const dayData = groupedByDate[date];
                html += `
                    <div class="day-card">
                        <h3 class="day-header">${dayData.day} ${dayData.date}</h3>
                `;
                
                dayData.slots.forEach(slot => {
                    const isAvailable = slot.stato !== 'prenotata' && slot.stato !== 'completata';
                    const slotClass = isAvailable ? '' : 'booked-slot';
                    const bookingBtn = isAvailable ? 
                        `<button class="booking-btn" onclick="bookSlot('${slot.id || ''}', '${slot.data}', '${slot.ora_inizio}', '${slot.ora_fine}')">Prenota</button>` :
                        `<span class="booking-status">Prenotato</span>`;
                    
                    html += `
                        <div class="time-slot ${slotClass}">
                            <span class="time-range">${slot.ora_inizio} - ${slot.ora_fine}</span>
                            ${bookingBtn}
                            ${slot.from_google_calendar == 1 ? '<span class="google-badge">Google Calendar</span>' : ''}
                        </div>
                    `;
                });
                
                html += `</div>`;
            });
            
            // Add pagination controls if needed
            if (allAvailability.length > daysPerPage) {
                const totalPages = Math.ceil(allAvailability.length / daysPerPage);
                
                html += `
                    <div class="pagination">
                        <button 
                            class="pagination-btn" 
                            onclick="changePage(${page - 1})"
                            ${page === 1 ? 'disabled' : ''}>
                            &laquo; Precedente
                        </button>
                        <span class="page-indicator">Pagina ${page} di ${totalPages}</span>
                        <button 
                            class="pagination-btn" 
                            onclick="changePage(${page + 1})"
                            ${page >= totalPages ? 'disabled' : ''}>
                            Successivo &raquo;
                        </button>
                    </div>
                `;
            }
            
            container.innerHTML = html;
        }
        
        function changePage(newPage) {
            if (newPage >= 1 && newPage <= Math.ceil(allAvailability.length / daysPerPage)) {
                currentPage = newPage;
                renderAvailabilityPage(currentPage);
                window.scrollTo(0, 0); // Scroll to top
            }
        }
        
        function bookSlot(slotId, date, startTime, endTime) {
            if (!confirm(`Vuoi prenotare la lezione di ${date} dalle ${startTime} alle ${endTime}?`)) {
                return;
            }
            
            fetch('../api/book_lesson.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `teacher_email=${encodeURIComponent(currentTeacherEmail)}&date=${encodeURIComponent(date)}&start_time=${encodeURIComponent(startTime)}&end_time=${encodeURIComponent(endTime)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Errore nella risposta del server');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Lezione prenotata con successo!');
                    // Refresh the availability to reflect the booking
                    loadAvailability();
                } else {
                    alert('Errore: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Si è verificato un errore durante la prenotazione: ' + error.message);
            });
        }
    </script>
</body>
</html>
