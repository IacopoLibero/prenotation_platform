/**
 * Google Calendar Setup page functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // Test the connection status
    checkConnectionStatus();
    
    // Initialize calendar items if teacher
    if (document.getElementById('calendarsContainer')) {
        initializeCalendarItems();
    }
    
    // Add calendar button event
    const addCalendarBtn = document.getElementById('btn-add-calendar');
    if (addCalendarBtn) {
        addCalendarBtn.addEventListener('click', addNewCalendar);
    }
    
    // Test sync button event
    const testSyncBtn = document.getElementById('btn-test-sync');
    if (testSyncBtn) {
        testSyncBtn.addEventListener('click', function(e) {
            e.preventDefault();
            testSyncConnection();
        });
    }
    
    // Form submit handler
    const calendarForm = document.getElementById('calendar-form');
    if (calendarForm) {
        calendarForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveCalendarSettings();
        });
    }
    
    // Toggle visibility of time ranges based on checkbox state
    const mattinaCheck = document.getElementById('mattina');
    const pomeriggioCheck = document.getElementById('pomeriggio');
    
    if (mattinaCheck) {
        mattinaCheck.addEventListener('change', function() {
            document.getElementById('mattina-times').style.display = this.checked ? 'flex' : 'none';
        });
    }
    
    if (pomeriggioCheck) {
        pomeriggioCheck.addEventListener('change', function() {
            document.getElementById('pomeriggio-times').style.display = this.checked ? 'flex' : 'none';
        });
    }
});

/**
 * Verifica lo stato della connessione con Google Calendar
 */
function checkConnectionStatus() {
    const statusContainer = document.getElementById('connection-status');
    if (!statusContainer) return;
    
    fetch('../api/sync_google_calendar.php?action=test')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusContainer.className = 'status-success';
                statusContainer.innerHTML = `
                    <div class="status-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="status-text">
                        <strong>Connessione attiva</strong>
                        <div class="status-details">
                            Il tuo account è correttamente collegato con Google Calendar.
                        </div>
                    </div>
                `;
            } else {
                statusContainer.className = 'status-error';
                statusContainer.innerHTML = `
                    <div class="status-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="status-text">
                        <strong>Problema di connessione</strong>
                        <div class="status-details">
                            ${data.message}
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            statusContainer.className = 'status-error';
            statusContainer.innerHTML = `
                <div class="status-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="status-text">
                    <strong>Errore di comunicazione</strong>
                    <div class="status-details">
                        Si è verificato un errore durante la comunicazione con il server. Riprova più tardi.
                    </div>
                </div>
            `;
        });
}

/**
 * Inizializza gli eventi per i calendari esistenti
 */
function initializeCalendarItems() {
    // Add event listeners to existing remove buttons
    document.querySelectorAll('.btn-remove-calendar').forEach(button => {
        button.addEventListener('click', function() {
            const calendarId = this.getAttribute('data-calendar-id');
            const calendarItem = this.closest('.calendar-item');
            if (calendarId && confirm('Sei sicuro di voler rimuovere questo calendario?')) {
                removeCalendar(calendarId, calendarItem);
            }
        });
    });
}

/**
 * Rimuove un calendario già salvato nel database
 */
function removeCalendar(calendarId, calendarItem) {
    fetch(`../api/save_google_calendar.php?action=remove&id=${calendarId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                calendarItem.remove();
                alert('Calendario rimosso con successo');
                
                // Se era l'ultimo, aggiungi un nuovo calendario vuoto
                if (document.querySelectorAll('.calendar-item').length === 0) {
                    addNewCalendar();
                }
            } else {
                alert('Errore nella rimozione del calendario: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Si è verificato un errore durante la comunicazione con il server');
        });
}

/**
 * Aggiunge un nuovo blocco calendario all'interfaccia
 */
function addNewCalendar() {
    const calendarsContainer = document.getElementById('calendarsContainer');
    const existingCalendars = document.querySelectorAll('.calendar-item');
    const newIndex = existingCalendars.length;
    
    // Get existing calendars from selects
    const calendarOptions = [];
    document.querySelectorAll('.calendar-item select').forEach(select => {
        if (select.options.length > 1) {
            Array.from(select.options).slice(1).forEach(option => {
                calendarOptions.push(`<option value="${option.value}">${option.text}</option>`);
            });
        }
    });
    
    const newCalendarItem = document.createElement('div');
    newCalendarItem.className = 'calendar-item';
    newCalendarItem.setAttribute('data-index', newIndex);
    
    newCalendarItem.innerHTML = `
        <div class="form-group">
            <label for="calendar-select-${newIndex}">Seleziona il calendario:</label>
            <select id="calendar-select-${newIndex}" name="calendars[${newIndex}][calendar_id]" required>
                <option value="">-- Seleziona un calendario --</option>
                ${calendarOptions.join('')}
            </select>
        </div>
        
        <div class="form-group">
            <label for="calendar-name-${newIndex}" class="calendar-name-label">Nome del calendario:</label>
            <input type="text" id="calendar-name-${newIndex}" name="calendars[${newIndex}][calendar_name]" 
                   value="Calendario Lezioni ${newIndex + 1}" required>
        </div>
        
        <div class="form-row">
            <div class="form-group half">
                <label for="hours-before-${newIndex}">Ore prima dell'evento:</label>
                <input type="number" id="hours-before-${newIndex}" name="calendars[${newIndex}][hours_before]" 
                       value="0" min="0" step="0.5">
                <small>Tempo di preparazione prima della lezione</small>
            </div>
            
            <div class="form-group half">
                <label for="hours-after-${newIndex}">Ore dopo l'evento:</label>
                <input type="number" id="hours-after-${newIndex}" name="calendars[${newIndex}][hours_after]" 
                       value="0" min="0" step="0.5">
                <small>Tempo di riposo dopo la lezione</small>
            </div>
        </div>
        
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="calendars[${newIndex}][is_active]" checked value="1">
                Calendario attivo
            </label>
            <small>Se deselezionato, questo calendario verrà ignorato durante la sincronizzazione</small>
        </div>
        
        <div class="form-group">
            <label class="checkbox-label">
                <input type="radio" name="calendario_selezionato" value="new_${newIndex}">
                Utilizza questo calendario per le nuove lezioni
            </label>
            <small>Le nuove lezioni create verranno inserite in questo calendario</small>
        </div>
    `;
    
    calendarsContainer.appendChild(newCalendarItem);
}

/**
 * Salva le impostazioni del calendario per i professori
 */
function saveCalendarSettings() {
    const calendarItems = document.querySelectorAll('.calendar-item');
    const calendars = [];
    
    // Raccogli i dati di tutti i calendari
    calendarItems.forEach(item => {
        const index = item.getAttribute('data-index');
        const calendarSelect = document.getElementById(`calendar-select-${index}`);
        const calendarName = document.getElementById(`calendar-name-${index}`);
        const hoursBefore = document.getElementById(`hours-before-${index}`);
        const hoursAfter = document.getElementById(`hours-after-${index}`);
        const isActiveCheckbox = item.querySelector(`input[name="calendars[${index}][is_active]"]`);
        const idInput = item.querySelector(`input[name="calendars[${index}][id]"]`);
        
        // Validazione di base
        if (!calendarSelect.value) {
            alert(`Seleziona un calendario per il blocco #${parseInt(index) + 1}`);
            return;
        }
        
        if (!calendarName.value) {
            alert(`Inserisci un nome per il calendario nel blocco #${parseInt(index) + 1}`);
            return;
        }
        
        // Aggiungi i dati del calendario
        calendars.push({
            id: idInput ? idInput.value : null,
            calendar_id: calendarSelect.value,
            calendar_name: calendarName.value,
            hours_before: parseFloat(hoursBefore.value) || 0,
            hours_after: parseFloat(hoursAfter.value) || 0,
            is_active: isActiveCheckbox && isActiveCheckbox.checked ? 1 : 0
        });
    });
    
    // Raccogli i dati delle preferenze di disponibilità
    const preferences = {
        weekend: document.getElementById('weekend').checked ? 1 : 0,
        mattina: document.getElementById('mattina').checked ? 1 : 0,
        pomeriggio: document.getElementById('pomeriggio').checked ? 1 : 0,
        ora_inizio_mattina: document.getElementById('ora-inizio-mattina').value,
        ora_fine_mattina: document.getElementById('ora-fine-mattina').value,
        ora_inizio_pomeriggio: document.getElementById('ora-inizio-pomeriggio').value,
        ora_fine_pomeriggio: document.getElementById('ora-fine-pomeriggio').value
    };
    
    // Determina quale calendario è selezionato per l'inserimento delle lezioni
    const calendarioSelezionatoRadio = document.querySelector('input[name="calendario_selezionato"]:checked');
    let calendarioSelezionatoId = null;
    
    if (calendarioSelezionatoRadio) {
        const selectedValue = calendarioSelezionatoRadio.value;
        
        // Se è un valore numerico, è un calendario esistente
        if (!isNaN(selectedValue)) {
            calendarioSelezionatoId = parseInt(selectedValue);
        } else {
            // Se è un nuovo calendario (es. "new_0"), ottieni l'indice
            const newCalendarIndex = selectedValue.split('_')[1];
            // Il calendario selezionato sarà impostato dopo il salvataggio
            preferences.new_selected_calendar_index = parseInt(newCalendarIndex);
        }
    }
    
    // Se c'è un calendario selezionato, aggiungi l'ID alle preferenze
    if (calendarioSelezionatoId !== null) {
        preferences.calendario_selezionato_id = calendarioSelezionatoId;
    }
    
    // Prepara i dati da inviare
    const data = {
        calendars: calendars,
        preferences: preferences
    };
    
    // Invia i dati al server
    fetch('../api/save_google_calendar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        return response.text().then(text => {
            try {
                // Prova a parsare come JSON
                return JSON.parse(text);
            } catch (e) {
                // Se non è un JSON valido, mostra il testo completo
                console.error('Risposta server non valida (non JSON):', text);
                throw new Error('Risposta non JSON dal server. Controlla la console.');
            }
        });
    })
    .then(data => {
        if (data.success) {
            alert('Configurazione salvata con successo');
            // Ricarica la pagina per mostrare le modifiche
            window.location.reload();
        } else {
            alert('Errore nel salvare la configurazione: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        alert('Si è verificato un errore durante la comunicazione con il server: ' + error.message);
    });
}

/**
 * Testa la sincronizzazione con Google Calendar
 */
function testSyncConnection() {
    const testSyncBtn = document.getElementById('btn-test-sync');
    const originalText = testSyncBtn.innerHTML;
    
    testSyncBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Test in corso...';
    testSyncBtn.disabled = true;
    
    fetch('../api/sync_google_calendar.php?action=test')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Test completato con successo! La connessione con Google Calendar funziona correttamente.');
            } else {
                alert('Errore nella connessione: ' + data.message);
            }
            testSyncBtn.innerHTML = originalText;
            testSyncBtn.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Si è verificato un errore durante la comunicazione con il server');
            testSyncBtn.innerHTML = originalText;
            testSyncBtn.disabled = false;
        });
}
