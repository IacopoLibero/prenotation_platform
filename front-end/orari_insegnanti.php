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
            position: relative;
        }

        .booking-status {
            display: inline-block;
            padding: 5px 10px;
            background-color: #f0f0f0;
            border-radius: 4px;
            color: #666;
            font-size: 0.9rem;
        }

        .booked-by-me {
            background-color: #e8f4ff;
            opacity: 1;
            border-left: 3px solid #1890ff;
        }

        .my-booking { .booked-by-me {
            color: #1890ff;      background-color: #e8f4ff;  /* Light blue background */
            font-weight: bold;border-left: 3px solid #1890ff;
            background-color: #e6f7ff;
        }
r: #1890ff;
        .week-header {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f1f8ff;
            border-left: 4px solid #2da0a8;
            border-radius: 4px;
            font-size: 1.2rem;        <div class="logo">Programma Lezioni</div>
        }  <ul>
        li><a href="home.php">Home</a></li>
        .week-dates {ount.php">Account</a></li>
            font-size: 0.9rem;></li>
            color: #666;    <li><a href="../login/logout.php">Logout</a></li>
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Programma Lezioni</div>
            <ul>
                <li><a href="home.php">Home</a></li>ion">
                <li><a href="user_account.php">Account</a></li>or">
                <li><a href="prenota_lezioni.php">Prenota Lezioni</a></li>d="teacherSelect" class="teacher-select">
                <li><a href="../login/logout.php">Logout</a></li>  <option value="">Seleziona un insegnante</option>
            </ul>        <?php while ($row = $result->fetch_assoc()): ?>
        </nav>
    </header>
    
    <main>          <?= !empty($row['google_calendar_link']) ? '(Google Calendar)' : '' ?>
        <section>            </option>
            <h1>Orari Insegnanti</h1>
            <p>Visualizza gli orari disponibili degli insegnanti</p>
            
            <div class="teachers-section">
                <div class="teacher-selector">d="calendarInfoBox" class="google-calendar-info" style="display:none;">
                    <select id="teacherSelect" class="teacher-select">  <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" class="google-calendar-icon" alt="Google Calendar">
                        <option value="">Seleziona un insegnante</option>  Questo insegnante sincronizza la sua disponibilità con Google Calendar.
                        <?php while ($row = $result->fetch_assoc()): ?>     </div>
                            <option value="<?= htmlspecialchars($row['email']) ?>"             
                                    data-has-calendar="<?= !empty($row['google_calendar_link']) ? '1' : '0' ?>">    <div class="availability-container" id="availabilityContainer">
                                <?= htmlspecialchars($row['username']) ?> 
                                <?= !empty($row['google_calendar_link']) ? '(Google Calendar)' : '' ?>           <p>Seleziona un insegnante per visualizzare la sua disponibilità.</p>
                            </option>                    </div>
                        <?php endwhile; ?>    </div>
                    </select>
                </div>
                
                <div id="calendarInfoBox" class="google-calendar-info" style="display:none;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" class="google-calendar-icon" alt="Google Calendar">
                    Questo insegnante sincronizza la sua disponibilità con Google Calendar.<p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
                </div>
                
                <div class="availability-container" id="availabilityContainer">
                    <div class="no-availability">
                        <p>Seleziona un insegnante per visualizzare la sua disponibilità.</p>currentWeek = 0; // Week offset (0 = current week, 1 = next week, etc.)
                    </div>
                </div>ail = '';
            </div>groupedByWeek = {}; // Store availability grouped by week
        </section>
    </main> function() {
    currentTeacherEmail = this.value;
    <footer>tions[this.selectedIndex].getAttribute('data-has-calendar') === '1';
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>;
    </footer>

    <script>0;
        // Global variables for pagination
        let currentWeek = 0; // Week offset (0 = current week, 1 = next week, etc.) nascondi l'info box del calendario
        let allAvailability = [];alendarInfoBox.style.display = hasCalendar ? 'block' : 'none';
        let currentTeacherEmail = '';
        let groupedByWeek = {}; // Store availability grouped by week
         = `
        document.getElementById('teacherSelect').addEventListener('change', function() {ty">
            currentTeacherEmail = this.value;are la sua disponibilità.</p>
            const hasCalendar = this.options[this.selectedIndex].getAttribute('data-has-calendar') === '1';div>
            const calendarInfoBox = document.getElementById('calendarInfoBox');  `;
                return;
            // Reset pagination to current week
            currentWeek = 0; 
                // Mostra un messaggio di caricamento
            // Mostra o nascondi l'info box del calendarioavailabilityContainer').innerHTML = `
            calendarInfoBox.style.display = hasCalendar ? 'block' : 'none';
            disponibilità in corso...</p>
            if (!currentTeacherEmail) {
                document.getElementById('availabilityContainer').innerHTML = `
                    <div class="no-availability">
                        <p>Seleziona un insegnante per visualizzare la sua disponibilità.</p>
                    </div>
                `;
                return;
            }./api/get_availability.php?email=${encodeURIComponent(currentTeacherEmail)}`)
            
            // Mostra un messaggio di caricamento
            document.getElementById('availabilityContainer').innerHTML = `ta del server');
                <div class="no-availability">
                    <p>Caricamento disponibilità in corso...</p>
                </div>
            `;ta => {
            data); // Debug
            loadAvailability();
        });.success && data.availability && data.availability.length > 0) {
        
        function loadAvailability() {
            fetch(`../api/get_availability.php?email=${encodeURIComponent(currentTeacherEmail)}`)
                .then(response => {by week
                    if (!response.ok) {
                        throw new Error('Errore nella risposta del server');
                    }
                    return response.json();lability(currentWeek);
                })
                .then(data => {ggio personalizzato in base a se l'insegnante usa Google Calendar o meno
                    console.log("Dati ricevuti:", data); // DebuggetElementById('availabilityContainer');
                    
                    if (data.success && data.availability && data.availability.length > 0) {
                        // Store all availability datalass="no-availability">
                        allAvailability = data.availability;      <p>Questo insegnante utilizza Google Calendar ma non ha ancora lezioni disponibili.</p>
                                   <p>Ti consigliamo di riprovare più tardi o contattare direttamente l'insegnante.</p>
                        // Group availability data by week           </div>
                        groupAvailabilityByWeek();          `;
                        
                        // Render the current week
                        renderWeekAvailability(currentWeek);
                    } else {nte non ha ancora impostato lezioni disponibili.</p>
                        // Messaggio personalizzato in base a se l'insegnante usa Google Calendar o meno
                        const container = document.getElementById('availabilityContainer');
                        if (data.uses_google_calendar) {  }
                            container.innerHTML = ` }
                                <div class="no-availability">       })
                                    <p>Questo insegnante utilizza Google Calendar ma non ha ancora lezioni disponibili.</p>        .catch(error => {
                                    <p>Ti consigliamo di riprovare più tardi o contattare direttamente l'insegnante.</p>error);
                                </div>tElementById('availabilityContainer').innerHTML = `
                            `;            <div class="no-availability">
                        } else {Si è verificato un errore durante il recupero della disponibilità: ${error.message}</p>
                            container.innerHTML = `
                                <div class="no-availability">
                                    <p>Questo insegnante non ha ancora impostato lezioni disponibili.</p>    });
                                </div>
                            `;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('availabilityContainer').innerHTML = `
                        <div class="no-availability">
                            <p>Si è verificato un errore durante il recupero della disponibilità: ${error.message}</p>/ Calculate the start of the current week (Sunday/Monday depending on locale)
                        </div>const firstDayOfWeek = today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1); // Adjust for Monday as first day
                    `;;
                });
        }
        
        function groupAvailabilityByWeek() {s no availability
            groupedByWeek = {};
            groupedByWeek[i] = [];
            // Get today's date
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            onst slotDate = new Date(slot.data);
            // Calculate the start of the current week (Sunday/Monday depending on locale)
            const firstDayOfWeek = today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1); // Adjust for Monday as first dayot belongs to (0 = current week, 1 = next week, etc.)
            const weekStart = new Date(today);
            weekStart.setDate(firstDayOfWeek); const weekOffset = Math.floor((slotDate - weekStart) / (7 * 24 * 60 * 60 * 1000));
                
            // Create default empty weeks for better pagination't exist
            // Show at least 4 weeks even if there's no availability       if (!groupedByWeek[weekOffset]) {
            for (let i = 0; i < 4; i++) {            groupedByWeek[weekOffset] = [];
                groupedByWeek[i] = [];
            }
                // Add slot to appropriate week
            allAvailability.forEach(slot => {slot);
                const slotDate = new Date(slot.data);
                
                // Calculate which week this slot belongs to (0 = current week, 1 = next week, etc.)upedByWeek);
                // How many weeks from current week
                const weekOffset = Math.floor((slotDate - weekStart) / (7 * 24 * 60 * 60 * 1000));
                fset) {
                // Initialize this week's array if it doesn't existtyContainer');
                if (!groupedByWeek[weekOffset]) {
                    groupedByWeek[weekOffset] = [];
                }t] || [];
                
                // Add slot to appropriate week
                groupedByWeek[weekOffset].push(slot);const today = new Date();
            });getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1);
            today);
            console.log("Grouped by week:", groupedByWeek);+ (weekOffset * 7));
        }
        
        function renderWeekAvailability(weekOffset) {e(weekStart.getDate() + 6);
            const container = document.getElementById('availabilityContainer');
            } - ${weekEnd.toLocaleDateString('it-IT')}`;
            // Get slots for the specified week
            const weekSlots = groupedByWeek[weekOffset] || [];
            ntainer.innerHTML = `
            // Get the date range for this weekv class="week-header">
            const today = new Date();           Settimana ${weekOffset === 0 ? 'corrente' : weekOffset === 1 ? 'prossima' : weekOffset + 1}
            const firstDayOfWeek = today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1);            <div class="week-dates">${weekDateRange}</div>
            const weekStart = new Date(today);
            weekStart.setDate(firstDayOfWeek + (weekOffset * 7));ailability">
            suna disponibilità per questa settimana.</p>
            const weekEnd = new Date(weekStart);
            weekEnd.setDate(weekStart.getDate() + 6);n(weekOffset)}
            
            const weekDateRange = `${weekStart.toLocaleDateString('it-IT')} - ${weekEnd.toLocaleDateString('it-IT')}`;
            
            if (weekSlots.length === 0) {
                container.innerHTML = `
                    <div class="week-header">nst groupedByDate = {};
                        Settimana ${weekOffset === 0 ? 'corrente' : weekOffset === 1 ? 'prossima' : weekOffset + 1}const dayNames = {
                        <div class="week-dates">${weekDateRange}</div>
                    </div>
                    <div class="no-availability">
                        <p>Nessuna disponibilità per questa settimana.</p>
                    </div>
                    ${renderPagination(weekOffset)}',
                `;ica': 'Domenica'
                return;
            }
            kSlots.forEach(slot => {
            // Group by date    if (!groupedByDate[slot.data]) {
            const groupedByDate = {};
            const dayNames = {day: dayNames[slot.giorno_settimana],
                'lunedi': 'Lunedì',ormattata,
                'martedi': 'Martedì',
                'mercoledi': 'Mercoledì',
                'giovedi': 'Giovedì',
                'venerdi': 'Venerdì',  groupedByDate[slot.data].slots.push(slot);
                'sabato': 'Sabato',});
                'domenica': 'Domenica'
            };
            let html = `
            weekSlots.forEach(slot => {
                if (!groupedByDate[slot.data]) {corrente' : weekOffset === 1 ? 'prossima' : weekOffset + 1}
                    groupedByDate[slot.data] = {class="week-dates">${weekDateRange}</div>
                        day: dayNames[slot.giorno_settimana],
                        date: slot.data_formattata,
                        slots: []
                    };ort dates chronologically
                }upedByDate).sort();
                groupedByDate[slot.data].slots.push(slot);
            });
            
            // Generate HTML for this week's dates
            let html = `
                <div class="week-header">    <h3 class="day-header">${dayData.day} ${dayData.date}</h3>
                    Settimana ${weekOffset === 0 ? 'corrente' : weekOffset === 1 ? 'prossima' : weekOffset + 1}
                    <div class="week-dates">${weekDateRange}</div>
                </div>
            `;andling of booking status and UI presentation
            
            // Sort dates chronologicallyedByMe = slot.stato_effettivo === 'prenotata_da_me';
            const sortedDates = Object.keys(groupedByDate).sort();t slotClass = isAvailable ? '' : 'booked-slot';
             
            sortedDates.forEach(date => {    let bookingBtn;
                const dayData = groupedByDate[date];le) {
                html += `         bookingBtn = `<button class="booking-btn" onclick="bookSlot('${slot.id || ''}', '${slot.data}', '${slot.ora_inizio}', '${slot.ora_fine}')">Prenota</button>`;
                    <div class="day-card">        } else if (isBookedByMe) {
                        <h3 class="day-header">${dayData.day} ${dayData.date}</h3> booked-by-me';
                `;="booking-status my-booking">Prenotato da te</span>`;
                        } else {
                dayData.slots.forEach(slot => {span class="booking-status">Prenotato</span>`;
                    const isAvailable = slot.stato !== 'prenotata' && slot.stato !== 'completata';           }
                    const slotClass = isAvailable ? '' : 'booked-slot';            
                    const bookingBtn = isAvailable ? 
                        `<button class="booking-btn" onclick="bookSlot('${slot.id || ''}', '${slot.data}', '${slot.ora_inizio}', '${slot.ora_fine}')">Prenota</button>` :
                        `<span class="booking-status">Prenotato</span>`;<span class="time-range">${slot.ora_inizio} - ${slot.ora_fine}</span>
                    
                    html += `                ${slot.from_google_calendar == 1 ? '<span class="google-badge">Google Calendar</span>' : ''}
                        <div class="time-slot ${slotClass}">
                            <span class="time-range">${slot.ora_inizio} - ${slot.ora_fine}</span>
                            ${bookingBtn}
                            ${slot.from_google_calendar == 1 ? '<span class="google-badge">Google Calendar</span>' : ''}
                        </div>
                    `;
                });
                
                html += `</div>`;ination(weekOffset);
            });
            TML = html;
            // Add pagination controls
            html += renderPagination(weekOffset);
            
            container.innerHTML = html;s include at least 4 weeks
        }
        k = Math.max(3, Object.keys(groupedByWeek).length - 1);
        function renderPagination(currentWeek) {
            // Find min and max week numbers - always include at least 4 weeks// Build pagination HTML
            let minWeek = 0;
            let maxWeek = Math.max(3, Object.keys(groupedByWeek).length - 1);       <div class="pagination">
                        <button 
            // Build pagination HTMLion-btn" 
            let paginationHtml = `
                <div class="pagination">
                    <button ana precedente
                        class="pagination-btn" 
                        onclick="changeWeek(${currentWeek - 1})"ana ${currentWeek === 0 ? 'corrente' : currentWeek === 1 ? 'prossima' : currentWeek + 1}</span>
                        ${currentWeek <= minWeek ? 'disabled' : ''}>       <button 
                        &laquo; Settimana precedente               class="pagination-btn" 
                    </button>                onclick="changeWeek(${currentWeek + 1})"
                    <span class="page-indicator">Settimana ${currentWeek === 0 ? 'corrente' : currentWeek === 1 ? 'prossima' : currentWeek + 1}</span> : ''}>
                    <button 
                        class="pagination-btn" utton>
                        onclick="changeWeek(${currentWeek + 1})"   </div>
                        ${currentWeek >= maxWeek ? 'disabled' : ''}>`;
                        Settimana successiva &raquo;
                    </button>ml;
                </div>
            `;
            hangeWeek(newWeek) {
            return paginationHtml;
        } (newWeek >= 0 && newWeek <= Math.max(3, Object.keys(groupedByWeek).length - 1)) {
        ewWeek;
        function changeWeek(newWeek) {ity(currentWeek);
            // Allow navigating to any week in the range, even if there's no availability
            if (newWeek >= 0 && newWeek <= Math.max(3, Object.keys(groupedByWeek).length - 1)) {
                currentWeek = newWeek;
                renderWeekAvailability(currentWeek);
                window.scrollTo(0, 0); // Scroll to toplotId, date, startTime, endTime) {
            }otare la lezione di ${date} dalle ${startTime} alle ${endTime}?`)) {
        }
        
        function bookSlot(slotId, date, startTime, endTime) {
            if (!confirm(`Vuoi prenotare la lezione di ${date} dalle ${startTime} alle ${endTime}?`)) {i/book_lesson.php', {
                return;
            }eaders: {
                  'Content-Type': 'application/x-www-form-urlencoded',
            fetch('../api/book_lesson.php', {
                method: 'POST',IComponent(currentTeacherEmail)}&date=${encodeURIComponent(date)}&start_time=${encodeURIComponent(startTime)}&end_time=${encodeURIComponent(endTime)}`
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',en(response => {
                },       if (!response.ok) {
                body: `teacher_email=${encodeURIComponent(currentTeacherEmail)}&date=${encodeURIComponent(date)}&start_time=${encodeURIComponent(startTime)}&end_time=${encodeURIComponent(endTime)}`       throw new Error('Errore nella risposta del server');
            })         }
            .then(response => {         return response.json();
                if (!response.ok) {            })






















</html></body>    </script>        }            });                alert('Si è verificato un errore durante la prenotazione: ' + error.message);                console.error('Error:', error);            .catch(error => {            })                }                    alert('Errore: ' + data.message);                } else {                    loadAvailability();                    // Refresh the availability to reflect the booking                    alert('Lezione prenotata con successo!');                if (data.success) {            .then(data => {            })                return response.json();                }                    throw new Error('Errore nella risposta del server');            .then(data => {
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
