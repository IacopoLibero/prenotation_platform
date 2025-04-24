/**
 * User Account page JavaScript functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const accountForm = document.querySelector('.account-info form');
    
    if (accountForm) {
        accountForm.addEventListener('submit', function(e) {
            // Basic form validation
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            
            // Email validation
            if (emailField && !validateEmail(emailField.value)) {
                e.preventDefault();
                alert('Per favore, inserisci un indirizzo email valido.');
                return;
            }
            
            // Password validation (only if a new password is being set)
            if (passwordField && passwordField.value.length > 0 && passwordField.value.length < 6) {
                e.preventDefault();
                alert('La password deve contenere almeno 6 caratteri.');
                return;
            }
        });
    }

    // Google Calendar "Revoca Accesso" button
    const revokeBtn = document.getElementById('btn-revoke-access');
    if (revokeBtn) {
        revokeBtn.addEventListener('click', function() {
            if (confirm('Sei sicuro di voler revocare l\'accesso a Google Calendar? Questa operazione rimuoverà la connessione tra il tuo account e Google Calendar.')) {
                revokeGoogleCalendarAccess();
            }
        });
    }
});

/**
 * Validate email format
 * @param {string} email The email to validate
 * @return {boolean} True if valid, false otherwise
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Revoca l'accesso a Google Calendar
 */
function revokeGoogleCalendarAccess() {
    fetch('../api/sync_google_calendar.php?action=revoke', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Accesso a Google Calendar revocato con successo');
            // Ricarica la pagina per aggiornare lo stato
            window.location.reload();
        } else {
            alert('Errore durante la revoca dell\'accesso: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        alert('Si è verificato un errore durante la comunicazione con il server');
    });
}
