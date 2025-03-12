// Simple mobile toggle script - separate from the main script
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on mobile
    if (window.innerWidth <= 768) {
        console.log('Mobile device detected, initializing mobile login');
        
        const container = document.getElementById('container');
        const toSigninButtons = document.querySelectorAll('.to-signin');
        const toSignupButtons = document.querySelectorAll('.to-signup');
        
        // Mobile buttons functionality
        toSigninButtons.forEach(button => {
            button.addEventListener('click', function() {
                console.log('Mobile: Sign in button clicked');
                container.classList.remove('mobile-active');
                updateMobileButtons();
            });
        });
        
        toSignupButtons.forEach(button => {
            button.addEventListener('click', function() {
                console.log('Mobile: Sign up button clicked');
                container.classList.add('mobile-active');
                updateMobileButtons();
            });
        });
        
        // Update mobile button states
        function updateMobileButtons() {
            const isActive = container.classList.contains('mobile-active');
            console.log('Mobile: Updating button states, container active:', isActive);
            
            toSigninButtons.forEach(button => {
                button.classList.toggle('active', !isActive);
            });
            
            toSignupButtons.forEach(button => {
                button.classList.toggle('active', isActive);
            });
        }
    }
});
