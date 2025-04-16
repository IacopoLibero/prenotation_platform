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
    
    // Crea un array con tutti i giorni della settimana corrente
    const daysInWeek = [];
    const dayMapping = {
        1: 'lunedi',
        2: 'martedi',
        3: 'mercoledi',
        4: 'giovedi',
        5: 'venerdi',
        6: 'sabato',
        0: 'domenica'
    };
    
    // Genera tutti i giorni della settimana corrente
    for (let i = 0; i < 7; i++) {
        const day = new Date(weekStart);
        day.setDate(weekStart.getDate() + i);
        
        const dayNum = day.getDay(); // 0-6
        const dayName = dayMapping[dayNum];
        const formattedDate = day.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
        
        // Ottieni la data in formato ISO per confronto
        const isoDate = day.toISOString().split('T')[0]; // YYYY-MM-DD
        
        daysInWeek.push({
            date: day,
            dayName: dayName,
            formattedDate: formattedDate,
            isoDate: isoDate
        });
    }
    
    // Ordina i giorni in modo che inizino da lunedì
    daysInWeek.sort((a, b) => {
        const order = { 'lunedi': 1, 'martedi': 2, 'mercoledi': 3, 'giovedi': 4, 'venerdi': 5, 'sabato': 6, 'domenica': 7 };
        return order[a.dayName] - order[b.dayName];
    });
    
    // Get today's date without time for comparison
    const currentDate = new Date();
    currentDate.setHours(0, 0, 0, 0);
    
    // Visualizza ogni giorno della settimana
    for (const dayInfo of daysInWeek) {
        // Ottieni gli slot per questo giorno della settimana
        const allSlots = weekData[dayInfo.dayName] || [];
        
        if (allSlots.length === 0) continue; // Salta se non ci sono slot per questo giorno della settimana
        
        // Per la settimana corrente, mostra solo i giorni da oggi in poi
        const dayDate = new Date(dayInfo.date);
        dayDate.setHours(0, 0, 0, 0);
        
        // Se è la settimana corrente e questo giorno è prima di oggi, salta
        if (weekNumber === 0 && dayDate < currentDate) {
            continue;
        }
        
        // Per le settimane future, dobbiamo mostrare tutti i giorni (inclusi lunedì e martedì)
        // Filtriamo gli slot in base alla data solo per la settimana corrente
        const isCurrentWeek = weekNumber === 0;
        let slotsForThisDay = [];
        
        if (isCurrentWeek) {
            // Per la settimana corrente, filtra per data esatta
            slotsForThisDay = allSlots.filter(slot => {
                return (slot.data_completa === dayInfo.isoDate || slot.data === dayInfo.isoDate);
            });
        } else {
            // Per le settimane future, raggruppa per data
            // Prima organizziamo gli slot per data
            const slotsByDate = {};
            
            allSlots.forEach(slot => {
                const dateKey = slot.data || slot.data_completa || 'generic';
                if (!slotsByDate[dateKey]) {
                    slotsByDate[dateKey] = [];
                }
                slotsByDate[dateKey].push(slot);
            });
            
            // Per questa specifica data, prendiamo gli slot corrispondenti
            if (slotsByDate[dayInfo.isoDate]) {
                slotsForThisDay = slotsByDate[dayInfo.isoDate];
            } else if (slotsByDate['generic']) {
                // Se non ci sono slot per questa data specifica ma ci sono slot generici, usiamo quelli
                slotsForThisDay = slotsByDate['generic'];
            } else {
                // Altrimenti prendiamo il primo gruppo di slot disponibili per questo giorno della settimana
                const firstDate = Object.keys(slotsByDate)[0];
                if (firstDate) {
                    slotsForThisDay = slotsByDate[firstDate];
                }
            }
        }
        
        // Se non ci sono slot disponibili dopo il filtraggio, salta questo giorno
        if (slotsForThisDay.length === 0) continue;
        
        // Crea una card per il giorno
        html += `
            <div class="day-card">
                <h3 class="day-header">
                    ${dayNames[dayInfo.dayName]}
                    <span class="date-badge">${dayInfo.formattedDate}</span>
                </h3>
        `;
        
        // Sort slots by time
        slotsForThisDay.sort((a, b) => a.ora_inizio.localeCompare(b.ora_inizio));
        
        // Usa un Set per evitare duplicazioni di orari
        const processedTimes = new Set();
        
        slotsForThisDay.forEach(slot => {
            // Crea una chiave unica per ogni slot (ora_inizio - ora_fine)
            const timeKey = `${slot.ora_inizio}-${slot.ora_fine}`;
            
            // Salta se questo orario è già stato processato
            if (processedTimes.has(timeKey)) return;
            
            // Segna questo orario come processato
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
