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
