// FUNZIONA NON TOCCARE STO CAZZO DI FILE - Aggiornato per supporto in tempo reale

// // Variabili globali
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

// Carica dinamicamente la disponibilità da Google Calendar
function loadRealTimeAvailability() {
    const loadingContainer = document.getElementById('weekContainer');
    if (!loadingContainer) return;
    
    // Mostra un messaggio di caricamento
    loadingContainer.innerHTML = `
        <div class="loading-container">
            <div class="spinner"></div>
            <p>Caricamento disponibilità in tempo reale da Google Calendar...</p>
        </div>
    `;
    
    // Ottieni i dati dalle impostazioni
    fetch('../api/get_calendar_availability.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Errore nella risposta del server');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Organize data by weeks
                const availability = data.availability;
                const now = new Date();
                
                // Calculate the start of the current week (Monday)
                const currentDate = new Date(now);
                const dayOfWeek = currentDate.getDay() || 7; // Convert 0 (Sunday) to 7
                const diff = currentDate.getDate() - dayOfWeek + 1; // Adjust to start from Monday
                const startOfWeek = new Date(now);
                startOfWeek.setDate(diff);
                startOfWeek.setHours(0, 0, 0, 0); // Reset time to start of day
                
                // Organize data by weeks and days
                const groupedData = {};
                
                availability.forEach(slot => {
                    const slotDate = new Date(slot.data);
                    // Calculate how many weeks from the current week
                    const timeDiff = slotDate.getTime() - startOfWeek.getTime();
                    const daysDiff = Math.floor(timeDiff / (1000 * 3600 * 24));
                    const weekNum = Math.floor(daysDiff / 7);
                    
                    if (weekNum >= 0 && weekNum < 4) { // Limit to 4 weeks
                        if (!groupedData[weekNum]) {
                            groupedData[weekNum] = {};
                        }
                        
                        const dayName = slot.giorno_settimana;
                        
                        if (!groupedData[weekNum][dayName]) {
                            groupedData[weekNum][dayName] = [];
                        }
                        
                        groupedData[weekNum][dayName].push(slot);
                    }
                });
                
                // Update global variables
                availabilityData = groupedData;
                maxWeeks = Object.keys(groupedData).length || 1;
                
                // Render the first week
                renderWeek(0);
                renderPaginationControls();
                
                // Aggiorna lo stato per indicare che è in tempo reale
                const statusBoxes = document.querySelectorAll('.status-box');
                statusBoxes.forEach(box => {
                    if (box.querySelector('strong')) {
                        box.querySelector('strong').textContent = "Google Calendar sincronizzato in tempo reale ✓";
                    }
                });
                
            } else {
                loadingContainer.innerHTML = `
                    <div class="error-container">
                        <p class="error-message">Errore nel caricamento: ${data.message}</p>
                        <button onclick="window.location.reload()" class="btn-primary">Riprova</button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            loadingContainer.innerHTML = `
                <div class="error-container">
                    <p class="error-message">Errore nella comunicazione con il server: ${error.message}</p>
                    <button onclick="window.location.reload()" class="btn-primary">Riprova</button>
                </div>
            `;
        });
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
    
    // Ora carica direttamente in tempo reale invece di fare la sincronizzazione
    loadRealTimeAvailability();
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
    console.log("INIZIO RENDERIZZAZIONE SETTIMANA:", weekNumber);
    
    const container = document.getElementById('weekContainer');
    if (!container) return;
    
    const weekData = availabilityData[weekNumber] || {};
    console.log('Rendering week:', weekNumber, 'Data:', weekData);
    
    // Calcola l'intervallo di date per questa settimana
    const today = new Date();
    
    // Ottieni il primo giorno della settimana corrente (lunedì)
    const currentDate = new Date(today);
    const dayOfWeek = currentDate.getDay() || 7; // Converti 0 (domenica) in 7
    const diff = currentDate.getDate() - dayOfWeek + 1; // Aggiusta per iniziare da lunedì
    
    // Calcola la data di inizio per la settimana richiesta
    const weekStart = new Date(today);
    weekStart.setDate(diff + (weekNumber * 7));
    
    // Calcola la data di fine settimana (domenica)
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekStart.getDate() + 6);
    
    // Formato data per visualizzazione
    const formatDate = (date) => {
        return `${date.getDate()}/${date.getMonth() + 1}/${date.getFullYear()}`;
    };
    
    const weekDateRange = `${formatDate(weekStart)} - ${formatDate(weekEnd)}`;
    
    // Creiamo un array con tutte le date della settimana per un riferimento preciso
    const datesOfWeek = [];
    for (let i = 0; i < 7; i++) {
        const date = new Date(weekStart);
        date.setDate(weekStart.getDate() + i);
        datesOfWeek.push(date);
    }
    
    // Verifica se ci sono slot per questa settimana
    let hasSlots = false;
    for (const day in weekData) {
        if (weekData[day] && weekData[day].length > 0) {
            hasSlots = true;
            break;
        }
    }
    
    console.log('Has slots:', hasSlots);
    
    // Ottieni i nomi delle settimane
    const weekNames = {
        0: 'Questa settimana',
        1: 'Prossima settimana',
        2: 'Tra due settimane',
        3: 'Settimana 4'
    };
    
    // Titolo della settimana appropriato
    const weekTitle = weekNames[weekNumber] !== undefined ? weekNames[weekNumber] : `Settimana ${weekNumber + 1}`;
    
    // Se non ci sono slot disponibili, mostra un messaggio
    if (!hasSlots) {
        container.innerHTML = `
            <div class="week-header">
                ${weekTitle}
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
            ${weekTitle}
            <div class="week-dates">${weekDateRange}</div>
        </div>
        <div class="availability-container">
    `;
    
    // Ordine dei giorni della settimana per visualizzazione
    const daysOrder = ['lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica'];
    
    // Visualizza ogni giorno della settimana nell'ordine corretto
    for (const dayName of daysOrder) {
        // Salta se non ci sono slot per questo giorno
        if (!weekData[dayName] || weekData[dayName].length === 0) {
            console.log(`Nessuno slot per ${dayName}`);
            continue;
        }
        
        console.log(`Elaborazione giorno ${dayName}, trovati ${weekData[dayName].length} slot`);
        
        // Calcola la data per questo giorno della settimana
        const dayIndex = daysOrder.indexOf(dayName);
        const dayDate = new Date(weekStart);
        dayDate.setDate(weekStart.getDate() + dayIndex);
        
        // Per la settimana corrente, salta i giorni già passati
        if (weekNumber === 0 && dayDate < currentDate) {
            console.log(`${dayName} è nel passato, salto`);
            continue;
        }
        
        // Formatta la data per la visualizzazione
        const formattedDate = dayDate.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
        
        // Crea una card per il giorno
        html += `
            <div class="day-card">
                <h3 class="day-header">
                    ${dayNames[dayName]}
                    <span class="date-badge">${formattedDate}</span>
                </h3>
        `;
        
        // Ordina gli slot per orario
        const slotsForDay = [...weekData[dayName]]; // Crea una copia per non modificare l'originale
        slotsForDay.sort((a, b) => a.ora_inizio.localeCompare(b.ora_inizio));
        
        // Usa un Set per evitare duplicazioni di orari
        const processedTimes = new Set();
        
        // Mostra tutti gli slot disponibili
        slotsForDay.forEach(slot => {
            const timeKey = `${slot.ora_inizio}-${slot.ora_fine}`;
            
            // Salta se questo orario è già stato processato
            if (processedTimes.has(timeKey)) return;
            
            // Segna questo orario come processato
            processedTimes.add(timeKey);
            
            const statusClass = slot.stato === 'disponibile' ? 'status-available' : 
                              slot.stato === 'prenotata' ? 'status-booked' : 
                              slot.stato === 'completata' ? 'status-completed' : '';
            
            const realTimeIcon = slot.from_google_calendar ? 
                '<span class="realtime-badge" title="Sincronizzato in tempo reale con Google Calendar"><i class="fas fa-sync"></i></span>' : '';
            
            html += `
                <div class="time-slot">
                    <span class="time-range">${slot.ora_inizio} - ${slot.ora_fine}</span>
                    <span class="slot-status ${statusClass}">${slot.stato} ${realTimeIcon}</span>
                </div>
            `;
        });
        
        html += `</div>`;
    }
    
    html += `</div>`;
    container.innerHTML = html;
    console.log("FINE RENDERIZZAZIONE SETTIMANA:", weekNumber);
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
    
    // Aggiungi un tasto per caricare in tempo reale
    const rtBtn = document.getElementById('realtimeBtn');
    if (rtBtn) {
        rtBtn.addEventListener('click', loadRealTimeAvailability);
    }
    
    // Verifica la presenza del flag di caricamento in tempo reale
    const useRealTimeData = document.body.getAttribute('data-realtime') === 'true';
    if (useRealTimeData) {
        loadRealTimeAvailability();
    }
});
