document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('mattina').addEventListener('change', function() {
        document.getElementById('mattina_orari').style.display = this.checked ? 'flex' : 'none';
    });
    
    document.getElementById('pomeriggio').addEventListener('change', function() {
        document.getElementById('pomeriggio_orari').style.display = this.checked ? 'flex' : 'none';
    });
    
    document.getElementById('calendarForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const calendarLink = document.getElementById('calendar_link').value;
        const weekend = document.getElementById('weekend').checked;
        const mattina = document.getElementById('mattina').checked;
        const pomeriggio = document.getElementById('pomeriggio').checked;
        const oraInizioMattina = document.getElementById('ora_inizio_mattina').value;
        const oraFineMattina = document.getElementById('ora_fine_mattina').value;
        const oraInizioPomeriggio = document.getElementById('ora_inizio_pomeriggio').value;
        const oraFinePomeriggio = document.getElementById('ora_fine_pomeriggio').value;
        const orePrimaEvento = document.getElementById('ore_prima_evento').value;
        const oreDopoEvento = document.getElementById('ore_dopo_evento').value;
        
        // Validazione base
        if (!calendarLink) {
            alert('Inserisci il link del tuo Google Calendar');
            return;
        }
        
        if (!mattina && !pomeriggio) {
            alert('Seleziona almeno una fascia oraria (mattina o pomeriggio)');
            return;
        }
        
        if (mattina && oraFineMattina <= oraInizioMattina) {
            alert('L\'ora di fine mattina deve essere successiva all\'ora di inizio');
            return;
        }
        
        if (pomeriggio && oraFinePomeriggio <= oraInizioPomeriggio) {
            alert('L\'ora di fine pomeriggio deve essere successiva all\'ora di inizio');
            return;
        }
        
        const formData = new FormData();
        formData.append('calendar_link', calendarLink);
        formData.append('weekend', weekend ? 1 : 0);
        formData.append('mattina', mattina ? 1 : 0);
        formData.append('pomeriggio', pomeriggio ? 1 : 0);
        formData.append('ora_inizio_mattina', oraInizioMattina);
        formData.append('ora_fine_mattina', oraFineMattina);
        formData.append('ora_inizio_pomeriggio', oraInizioPomeriggio);
        formData.append('ora_fine_pomeriggio', oraFinePomeriggio);
        formData.append('ore_prima_evento', orePrimaEvento);
        formData.append('ore_dopo_evento', oreDopoEvento);
        
        // Mostriamo un messaggio di attesa
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Sincronizzazione in corso...';
        submitBtn.disabled = true;
        
        fetch('../api/save_google_calendar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Errore nella risposta del server: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Dopo il salvataggio, sincronizziamo il calendario
                return fetch('../api/sync_google_calendar.php');
            } else {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                alert(data.message);
                throw new Error(data.message);
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Errore nella sincronizzazione del calendario: ' + response.status);
            }
            // First get the raw text response
            return response.text();
        })
        .then(responseText => {
            // Check if the response is actually empty
            if (!responseText || responseText.trim() === '') {
                throw new Error('Il server ha restituito una risposta vuota. Controlla i log del server.');
            }
            
            // Try to parse as JSON only if it looks like JSON
            let data;
            try {
                if (responseText.trim().startsWith('{') || responseText.trim().startsWith('[')) {
                    data = JSON.parse(responseText);
                } else {
                    throw new Error('Risposta non valida JSON: ' + responseText.substring(0, 100) + '...');
                }
            } catch (e) {
                throw new Error('Errore nel parsing JSON: ' + e.message + '\nRisposta: ' + responseText.substring(0, 100));
            }
            
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            
            if (data && data.success) {
                alert('Calendario sincronizzato con successo. Nuove disponibilità generate.');
                // Reindirizzamento opzionale
                window.location.href = 'disponibilita.php';
            } else if (data) {
                // Mostra il messaggio di errore
                alert('Errore durante la sincronizzazione: ' + data.message);
            } else {
                throw new Error('Risposta non valida dal server');
            }
        })
        .catch(error => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            alert('Si è verificato un errore: ' + error.message);
        });
    });
});
