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
 * Salva le impostazioni del calendario per i professori
 */
function saveCalendarSettings() {
    const calendarSelect = document.getElementById('calendar-select');
    const calendarName = document.getElementById('calendar-name');
    const hoursBefore = document.getElementById('hours-before');
    const hoursAfter = document.getElementById('hours-after');
    
    // Validazione di base
    if (!calendarSelect.value) {
        alert('Seleziona un calendario');
        return;
    }
    
    if (!calendarName.value) {
        alert('Inserisci un nome per il calendario');
        return;
    }
    
    // Prepara i dati da inviare
    const calendarData = {
        calendar_id: calendarSelect.value,
        calendar_name: calendarName.value,
        hours_before: parseFloat(hoursBefore.value) || 0,
        hours_after: parseFloat(hoursAfter.value) || 0
    };
    
    // Invia i dati al server
    fetch('../api/save_google_calendar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(calendarData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Configurazione del calendario salvata con successo');
        } else {
            alert('Errore nel salvare la configurazione: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        alert('Si è verificato un errore durante la comunicazione con il server');
    });
}
