/**
 * Google Calendar Setup page functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // Controlla lo stato della connessione quando la pagina si carica
    checkConnectionStatus();
    
    // Test di sincronizzazione
    const testSyncBtn = document.getElementById('btn-test-sync');
    if (testSyncBtn) {
        testSyncBtn.addEventListener('click', function(e) {
            e.preventDefault();
            checkConnectionStatus();
        });
    }
    
    // Form del calendario (solo per i professori)
    const calendarForm = document.getElementById('calendar-form');
    if (calendarForm) {
        calendarForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveCalendarSettings();
        });
        
        // Aggiungi evento per il bottone "Aggiungi un altro calendario"
        const addCalendarBtn = document.getElementById('btn-add-calendar');
        if (addCalendarBtn) {
            addCalendarBtn.addEventListener('click', addNewCalendar);
        }
        
        // Inizializza gli eventi per i calendari esistenti
        initializeCalendarItems();
        
        // Gestione visibilità fasce orarie basata su checkbox
        const mattinaCheckbox = document.getElementById('mattina');
        const pomeriggioCheckbox = document.getElementById('pomeriggio');
        
        if (mattinaCheckbox) {
            mattinaCheckbox.addEventListener('change', function() {
                document.getElementById('mattina-times').style.display = this.checked ? 'flex' : 'none';
            });
        }
        
        if (pomeriggioCheckbox) {
            pomeriggioCheckbox.addEventListener('change', function() {
                document.getElementById('pomeriggio-times').style.display = this.checked ? 'flex' : 'none';
            });
        }
    }
});

/**
 * Verifica lo stato della connessione con Google Calendar
 */
function checkConnectionStatus() {
    const connectionStatus = document.getElementById('connection-status');
    
    // Mostra stato di caricamento
    connectionStatus.className = 'status-loading';
    connectionStatus.innerHTML = `
        <div class="status-icon">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
        <div class="status-text">
            Verifica della connessione in corso...
        </div>
    `;
    
    // Chiamata API per verificare la connessione
    fetch('../api/sync_google_calendar.php?action=test')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Connessione riuscita
                connectionStatus.className = 'status-success';
                connectionStatus.innerHTML = `
                    <div class="status-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="status-message">
                        <div class="status-text">
                            Connessione a Google Calendar stabilita con successo!
                        </div>
                        <div class="status-details">
                            Hai accesso a ${data.data.calendar_count} calendari.
                        </div>
                    </div>
                `;
            } else {
                // Errore di connessione
                connectionStatus.className = 'status-error';
                connectionStatus.innerHTML = `
                    <div class="status-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="status-message">
                        <div class="status-text">
                            Si è verificato un errore nella connessione.
                        </div>
                        <div class="status-details">
                            ${data.message}
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            connectionStatus.className = 'status-error';
            connectionStatus.innerHTML = `
                <div class="status-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="status-message">
                    <div class="status-text">
                        Si è verificato un errore durante la verifica.
                    </div>
                    <div class="status-details">
                        Problema di comunicazione con il server.
                    </div>
                </div>
            `;
        });
}

/**
 * Inizializza gli eventi per i calendari esistenti
 */
function initializeCalendarItems() {
    const removeButtons = document.querySelectorAll('.btn-remove-calendar');
    
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const calendarId = this.getAttribute('data-calendar-id');
            const calendarItem = this.closest('.calendar-item');
            
            if (calendarId && confirm('Sei sicuro di voler rimuovere questo calendario?')) {
                // Se ha un ID database, lo marchiamo per eliminazione
                removeCalendar(calendarId, calendarItem);
            } else if (!calendarId) {
                // Se è un nuovo calendario che non ha ancora ID nel database
                calendarItem.remove();
            }
        });
    });
}

/**
 * Rimuove un calendario già salvato nel database
 */
function removeCalendar(calendarId, calendarItem) {
    fetch(`../api/save_google_calendar.php?action=remove&id=${calendarId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            calendarItem.remove();
            alert('Calendario rimosso con successo');
        } else {
            alert('Errore nel rimuovere il calendario: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        alert('Si è verificato un errore durante la comunicazione con il server');
    });
}

/**
 * Aggiunge un nuovo blocco calendario all'interfaccia
 */
function addNewCalendar() {
    // Ottieni l'ultimo indice utilizzato
    const calendarItems = document.querySelectorAll('.calendar-item');
    const newIndex = calendarItems.length;
    
    // Clona il primo calendario come template
    const template = document.querySelector('.calendar-item');
    const newCalendar = template.cloneNode(true);
    
    // Aggiorna gli ID e i name degli elementi
    newCalendar.setAttribute('data-index', newIndex);
    
    // Aggiorna tutti gli input e select all'interno
    const inputs = newCalendar.querySelectorAll('input, select');
    inputs.forEach(input => {
        const nameAttr = input.getAttribute('name');
        if (nameAttr) {
            input.setAttribute('name', nameAttr.replace(/\[\d+\]/, `[${newIndex}]`));
        }
        
        const idAttr = input.getAttribute('id');
        if (idAttr) {
            input.setAttribute('id', idAttr.replace(/-\d+$/, `-${newIndex}`));
        }
        
        // Reset dei valori (tranne per checkbox)
        if (input.type === 'checkbox') {
            input.checked = true;
        } else {
            input.value = input.type === 'number' ? '0' : '';
            if (input.classList.contains('calendar-name')) {
                input.value = 'Calendario Lezioni';
            }
        }
    });
    
    // Aggiorna le label
    const labels = newCalendar.querySelectorAll('label');
    labels.forEach(label => {
        const forAttr = label.getAttribute('for');
        if (forAttr) {
            label.setAttribute('for', forAttr.replace(/-\d+$/, `-${newIndex}`));
        }
    });
    
    // Reset del bottone rimuovi
    const removeBtn = newCalendar.querySelector('.btn-remove-calendar');
    if (removeBtn) {
        removeBtn.removeAttribute('data-calendar-id');
        removeBtn.addEventListener('click', function() {
            newCalendar.remove();
        });
    }
    
    // Aggiungi il nuovo calendario al container
    document.getElementById('calendarsContainer').appendChild(newCalendar);
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
