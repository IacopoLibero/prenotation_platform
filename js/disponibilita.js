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
    
    // Get the date range for this week
    let weekStart, weekEnd;
    let foundDates = false;
    
    // Find first and last date in this week's data
    for (const day in weekData) {
        if (weekData[day].length > 0) {
            for (const slot of weekData[day]) {
                const date = new Date(slot.data);
                if (!weekStart || date < weekStart) {
                    weekStart = date;
                }
                if (!weekEnd || date > weekEnd) {
                    weekEnd = date;
                }
                foundDates = true;
            }
        }
    }
    
    // Default dates if no slots found
    if (!foundDates) {
        const today = new Date();
        weekStart = new Date();
        weekStart.setDate(today.getDate() + (weekNumber * 7));
        weekEnd = new Date(weekStart);
        weekEnd.setDate(weekStart.getDate() + 6);
    }
    
    const weekDateRange = `${weekStart.toLocaleDateString('it-IT')} - ${weekEnd.toLocaleDateString('it-IT')}`;
    
    // Check if there are any slots for this week
    let hasSlots = false;
    for (const day in weekData) {
        if (weekData[day].length > 0) {
            hasSlots = true;
            break;
        }
    }
    
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
    
    // Order days correctly (Monday to Sunday)
    const orderedDays = ['lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica'];
    
    for (const day of orderedDays) {
        const slots = weekData[day] || [];
        if (slots.length === 0) continue;
        
        // Sort slots by time
        slots.sort((a, b) => a.ora_inizio.localeCompare(b.ora_inizio));
        
        html += `
            <div class="day-card">
                <h3 class="day-header">
                    ${dayNames[day]}
                    <span class="date-badge">${slots[0].data_formattata}</span>
                </h3>
        `;
        
        slots.forEach(slot => {
            html += `
                <div class="time-slot">
                    <span class="time-range">${slot.ora_inizio} - ${slot.ora_fine}</span>
                </div>
            `;
        });
        
        html += `</div>`;
    }
    
    html += `</div>`;
    container.innerHTML = html;
}

// Register event listeners when DOM is loaded
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
