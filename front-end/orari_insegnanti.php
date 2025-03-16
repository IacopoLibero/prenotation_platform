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

        .week-header {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f1f8ff;
            border-left: 4px solid #2da0a8;
            border-radius: 4px;
            font-size: 1.2rem;
        }
        
        .week-dates {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
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
        let currentWeek = 0; // Week offset (0 = current week, 1 = next week, etc.)
        let allAvailability = [];
        let currentTeacherEmail = '';
        let groupedByWeek = {}; // Store availability grouped by week
        
        document.getElementById('teacherSelect').addEventListener('change', function() {
            currentTeacherEmail = this.value;
            const hasCalendar = this.options[this.selectedIndex].getAttribute('data-has-calendar') === '1';
            const calendarInfoBox = document.getElementById('calendarInfoBox');
            
            // Reset pagination to current week
            currentWeek = 0;
            
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
                        
                        // Group availability data by week
                        groupAvailabilityByWeek();
                        
                        // Render the current week
                        renderWeekAvailability(currentWeek);
                    } else {
                        // Messaggio personalizzato in base a se l'insegnante usa Google Calendar o meno
                        const container = document.getElementById('availabilityContainer');
                        if (data.uses_google_calendar) {
                            container.innerHTML = `
                                <div class="no-availability">
                                    <p>Questo insegnante utilizza Google Calendar ma non ha ancora lezioni disponibili.</p>
                                    <p>Ti consigliamo di riprovare più tardi o contattare direttamente l'insegnante.</p>
                                </div>
                            `;
                        } else {
                            container.innerHTML = `
                                <div class="no-availability">
                                    <p>Questo insegnante non ha ancora impostato lezioni disponibili.</p>
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
        
        function groupAvailabilityByWeek() {
            groupedByWeek = {};
            
            // Get today's date
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Calculate the start of the current week (Sunday/Monday depending on locale)
            const firstDayOfWeek = today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1); // Adjust for Monday as first day
            const weekStart = new Date(today);
            weekStart.setDate(firstDayOfWeek);
            
            // Create default empty weeks for better pagination
            // Show at least 4 weeks even if there's no availability
            for (let i = 0; i < 4; i++) {
                groupedByWeek[i] = [];
            }
            
            allAvailability.forEach(slot => {
                const slotDate = new Date(slot.data);
                
                // Calculate which week this slot belongs to (0 = current week, 1 = next week, etc.)
                // How many weeks from current week
                const weekOffset = Math.floor((slotDate - weekStart) / (7 * 24 * 60 * 60 * 1000));
                
                // Initialize this week's array if it doesn't exist
                if (!groupedByWeek[weekOffset]) {
                    groupedByWeek[weekOffset] = [];
                }
                
                // Add slot to appropriate week
                groupedByWeek[weekOffset].push(slot);
            });
            
            console.log("Grouped by week:", groupedByWeek);
        }
        
        function renderWeekAvailability(weekOffset) {
            const container = document.getElementById('availabilityContainer');
            
            // Get slots for the specified week
            const weekSlots = groupedByWeek[weekOffset] || [];
            
            // Get the date range for this week
            const today = new Date();
            const firstDayOfWeek = today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1);
            const weekStart = new Date(today);
            weekStart.setDate(firstDayOfWeek + (weekOffset * 7));
            
            const weekEnd = new Date(weekStart);
            weekEnd.setDate(weekStart.getDate() + 6);
            
            const weekDateRange = `${weekStart.toLocaleDateString('it-IT')} - ${weekEnd.toLocaleDateString('it-IT')}`;
            
            if (weekSlots.length === 0) {
                container.innerHTML = `
                    <div class="week-header">
                        Settimana ${weekOffset === 0 ? 'corrente' : weekOffset === 1 ? 'prossima' : weekOffset + 1}
                        <div class="week-dates">${weekDateRange}</div>
                    </div>
                    <div class="no-availability">
                        <p>Nessuna disponibilità per questa settimana.</p>
                    </div>
                    ${renderPagination(weekOffset)}
                `;
                return;
            }
            
            // Group by date
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
            
            weekSlots.forEach(slot => {
                if (!groupedByDate[slot.data]) {
                    groupedByDate[slot.data] = {
                        day: dayNames[slot.giorno_settimana],
                        date: slot.data_formattata,
                        slots: []
                    };
                }
                groupedByDate[slot.data].slots.push(slot);
            });
            
            // Generate HTML for this week's dates
            let html = `
                <div class="week-header">
                    Settimana ${weekOffset === 0 ? 'corrente' : weekOffset === 1 ? 'prossima' : weekOffset + 1}
                    <div class="week-dates">${weekDateRange}</div>
                </div>
            `;
            
            // Sort dates chronologically
            const sortedDates = Object.keys(groupedByDate).sort();
            
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
            
            // Add pagination controls
            html += renderPagination(weekOffset);
            
            container.innerHTML = html;
        }
        
        function renderPagination(currentWeek) {
            // Find min and max week numbers - always include at least 4 weeks
            let minWeek = 0;
            let maxWeek = Math.max(3, Object.keys(groupedByWeek).length - 1);
            
            // Build pagination HTML
            let paginationHtml = `
                <div class="pagination">
                    <button 
                        class="pagination-btn" 
                        onclick="changeWeek(${currentWeek - 1})"
                        ${currentWeek <= minWeek ? 'disabled' : ''}>
                        &laquo; Settimana precedente
                    </button>
                    <span class="page-indicator">Settimana ${currentWeek === 0 ? 'corrente' : currentWeek === 1 ? 'prossima' : currentWeek + 1}</span>
                    <button 
                        class="pagination-btn" 
                        onclick="changeWeek(${currentWeek + 1})"
                        ${currentWeek >= maxWeek ? 'disabled' : ''}>
                        Settimana successiva &raquo;
                    </button>
                </div>
            `;
            
            return paginationHtml;
        }
        
        function changeWeek(newWeek) {
            // Allow navigating to any week in the range, even if there's no availability
            if (newWeek >= 0 && newWeek <= Math.max(3, Object.keys(groupedByWeek).length - 1)) {
                currentWeek = newWeek;
                renderWeekAvailability(currentWeek);
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
