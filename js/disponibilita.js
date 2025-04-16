// Variabili globali
let currentWeek = 0;
let availabilityData = {};
let dayNames = {};
let maxWeeks = 0;

// Inizializza la pagina
document.addEventListener('DOMContentLoaded', function() {
    // These values will be set from PHP via the initAvailability function
    renderWeek(currentWeek);
    renderPaginationControls();
});

// Initialize data from PHP
function initAvailability(data, days, weeks) {
    availabilityData = data;
    dayNames = days;
    maxWeeks = weeks;
    
    // Stampa i dati disponibilità sulla console per debug
    console.log('Availability data:', data);
    
    // Renderizza la prima settimana
    renderWeek(0);
    renderPaginationControls();
}

// Sincronizzazione Google Calendar
function syncGoogleCalendar() {
    const syncBtn = document.getElementById('syncBtn');
    const syncBtnEmpty = document.getElementById('syncBtnEmpty');
    const syncStatus = document.getElementById('syncStatus');
    
    // Disabilita il pulsante e mostra loading
    const buttons = [syncBtn, syncBtnEmpty].filter(button => button !== null);
    
    buttons.forEach(button => {
        if (button) {
            button.disabled = true;
            button.innerHTML = `
                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" alt="Google Calendar">
                Sincronizzazione...
            `;
        }
    });
    
    if (syncStatus) {
        syncStatus.style.display = 'inline';
        syncStatus.textContent = 'Sincronizzazione in corso...';
    }
    
    // Use direct path to API
    fetch('../api/sync_google_calendar.php?nocache=' + new Date().getTime())
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (syncStatus) {
                    syncStatus.textContent = 'Sincronizzazione completata! Aggiornamento pagina...';
                }
                // Force a hard refresh after successful sync
                setTimeout(() => {
                    window.location.href = window.location.pathname + '?refresh=' + new Date().getTime();
                }, 1500);
            } else {
                if (syncStatus) {
                    syncStatus.textContent = 'Errore: ' + data.message;
                }
                buttons.forEach(button => {
                    if (button) {
                        button.innerHTML = `
                            <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" alt="Google Calendar">
                            Riprova
                        `;
                        button.disabled = false;
                    }
                });
            }
        })
        .catch(error => {
            if (syncStatus) {
                syncStatus.textContent = 'Errore: ' + error.message;
            }
            buttons.forEach(button => {
                if (button) {
                    button.innerHTML = `
                        <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" alt="Google Calendar">
                        Riprova
                    `;
                    button.disabled = false;
                }
            });
        });
}

// Funzione per renderizzare i controlli di paginazione
function renderPaginationControls() {
    const paginationContainer = document.getElementById('paginationControls');
    if (!paginationContainer) return;
    
    const weekNames = {
        0: 'Questa settimana',
        1: 'Prossima settimana',
        2: 'Tra due settimane',
        3: 'Tra tre settimane'
    };
    
    paginationContainer.innerHTML = `
        <button 
            class="pagination-btn" 
            onclick="changeWeek(${currentWeek - 1})"
            ${currentWeek <= 0 ? 'disabled' : ''}>
            &laquo; Settimana precedente
        </button>
        <span class="page-indicator">${weekNames[currentWeek] || `Settimana ${currentWeek + 1}`}</span>
        <button 
            class="pagination-btn" 
            onclick="changeWeek(${currentWeek + 1})"
            ${currentWeek >= maxWeeks - 1 ? 'disabled' : ''}>
            Settimana successiva &raquo;
        </button>
    `;
}

// Funzione per cambiare settimana
function changeWeek(newWeek) {
    if (newWeek >= 0 && newWeek < maxWeeks) {
        currentWeek = newWeek;
        renderWeek(currentWeek);
        renderPaginationControls();
        window.scrollTo(0, 0); // Scroll to top
    }
}

// Funzione per renderizzare la settimana corrente
function renderWeek(weekNumber) {
    const container = document.getElementById('weekContainer');
    if (!container) return;
    
    const weekData = availabilityData[weekNumber] || {};
    console.log('Rendering week:', weekNumber, 'Data:', weekData);
    
    // Get the date range for this week
    const today = new Date();
    const weekStart = new Date(today);
    
    // Calcola il primo giorno della settimana (lunedì)
    const dayOfWeek = today.getDay(); // 0 = domenica, 1 = lunedì, ..., 6 = sabato
    const diff = today.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1); // Aggiusta per iniziare da lunedì
    
    weekStart.setDate(diff + (weekNumber * 7));
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekStart.getDate() + 6);
    
    const weekDateRange = `${weekStart.toLocaleDateString('it-IT')} - ${weekEnd.toLocaleDateString('it-IT')}`;
    
    // Check if there are any slots for this week
    let hasSlots = false;
    let totalSlots = 0;
    
    for (const day in weekData) {
        if (weekData[day] && weekData[day].length > 0) {
            hasSlots = true;
            totalSlots += weekData[day].length;
        }
    }
    
    console.log('Has slots:', hasSlots, 'Total slots:', totalSlots);
    
    if (!hasSlots) {
        container.innerHTML = `
            <div class="week-header">
                ${weekNumber === 0 ? 'Settimana corrente' : weekNumber === 1 ? 'Prossima settimana' : 'Settimana ' + (weekNumber + 1)}
                <div class="week-dates">${weekDateRange}</div>
            </div>
            <div class="no-availability">
                <p>Nessuna disponibilità per questa settimana.</p>
            </div>
        `;
        return;
    }
    
    // Build HTML for availability
    let html = `
        <div class="week-header">
            ${weekNumber === 0 ? 'Settimana corrente' : weekNumber === 1 ? 'Prossima settimana' : 'Settimana ' + (weekNumber + 1)}
            <div class="week-dates">${weekDateRange}</div>
        </div>
        <div class="availability-container">
    `;
    
    // Get current date and set time to start of day for comparison
    const currentDate = new Date();
    // Avanzare di un giorno (per mostrare solo dal giorno dopo)
    currentDate.setDate(currentDate.getDate() + 1);
    currentDate.setHours(0, 0, 0, 0);
    
    // Loop through days of the week (only weekdays)
    for (let i = 1; i <= 5; i++) {  // 1=lunedi, 2=martedi, 3=mercoledi, 4=giovedi, 5=venerdi
        const dayName = ['', 'lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi'][i];
        const slots = weekData[dayName];
        
        // If no slots for this day, skip
        if (!slots || slots.length === 0) continue;
        
        // Calculate the date for this day in the current week
        const dayDate = new Date(weekStart);
        dayDate.setDate(weekStart.getDate() + (i - weekStart.getDay() + 7) % 7);
        
        // Format the date for display and comparison
        const formattedDate = dayDate.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
        const isoDate = dayDate.toISOString().split('T')[0]; // YYYY-MM-DD
        
        // IMPORTANTE: per la settimana corrente, mostra solo dal giorno dopo in poi
        if (weekNumber === 0 && dayDate < currentDate) {
            continue;  // Skip days before tomorrow
        }
        
        // Filter slots that match this exact date
        const slotsForThisDate = slots.filter(slot => {
            // Se lo slot ha una data specifica, controlla che sia quella corretta
            if (slot.data_completa && slot.data_completa !== isoDate) return false;
            if (slot.data && slot.data !== isoDate) return false;
            
            // Se non ha una data specifica, accettalo per settimane future
            return true;
        });
        
        // If no slots available after filtering, skip this day
        if (slotsForThisDate.length === 0) continue;
        
        // Add card for this day
        html += `
            <div class="day-card">
                <h3 class="day-header">
                    ${dayNames[dayName]}
                    <span class="date-badge">${formattedDate}</span>
                </h3>
        `;
        
        // Sort slots by time
        slotsForThisDate.sort((a, b) => a.ora_inizio.localeCompare(b.ora_inizio));
        
        // Use a Set to avoid duplicating times
        const processedTimes = new Set();
        
        slotsForThisDate.forEach(slot => {
            // Create a unique key for this slot (start_time - end_time)
            const timeKey = `${slot.ora_inizio}-${slot.ora_fine}`;
            
            // Skip if already processed
            if (processedTimes.has(timeKey)) return;
            
            // Mark as processed
            processedTimes.add(timeKey);
            
            const statusClass = slot.stato === 'disponibile' ? 'status-available' : 
                              slot.stato === 'prenotata' ? 'status-booked' : 
                              slot.stato === 'completata' ? 'status-completed' : '';
            
            html += `
                <div class="time-slot">
                    <span class="time-range">${slot.ora_inizio} - ${slot.ora_fine}</span>
                    <span class="slot-status ${statusClass}">${slot.stato}</span>
                </div>
            `;
        });
        
        html += `</div>`;
    }
    
    html += `</div>`;
    container.innerHTML = html;
}

// Register event listeners when DOM is loaded - solo un event listener
document.addEventListener('DOMContentLoaded', function() {
    // Listener per i pulsanti di sincronizzazione
    const syncBtn = document.getElementById('syncBtn');
    if (syncBtn) {
        syncBtn.addEventListener('click', syncGoogleCalendar);
    }
    
    const syncBtnEmpty = document.getElementById('syncBtnEmpty');
    if (syncBtnEmpty) {
        syncBtnEmpty.addEventListener('click', syncGoogleCalendar);
    }
});
