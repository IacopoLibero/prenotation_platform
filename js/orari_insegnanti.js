// Global variables for pagination
let currentWeek = 0; // Week offset (0 = current week, 1 = next week, etc.)
let allAvailability = [];
let currentTeacherEmail = '';
let groupedByWeek = {}; // Store availability grouped by week

document.addEventListener('DOMContentLoaded', function() {
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
            // IMPROVED: Better handling of booking status and UI presentation
            let isAvailable = slot.stato === 'disponibile';
            let isBookedByMe = slot.stato_effettivo === 'prenotata_da_me';
            let slotClass = isAvailable ? '' : 'booked-slot';
            
            let bookingBtn;
            if (isAvailable) {
                bookingBtn = `<button class="booking-btn" onclick="bookSlot('${slot.id || ''}', '${slot.data}', '${slot.ora_inizio}', '${slot.ora_fine}')">Prenota</button>`;
            } else if (isBookedByMe) {
                slotClass += ' booked-by-me';
                bookingBtn = `<span class="booking-status my-booking">Prenotato da te</span>`;
            } else {
                bookingBtn = `<span class="booking-status">Prenotato</span>`;
            }
            
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
