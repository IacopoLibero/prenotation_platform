// Funzionalità di ricerca
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const studentCards = document.querySelectorAll('.student-card');
        
        studentCards.forEach(card => {
            const name = card.querySelector('.student-name').textContent.toLowerCase();
            const email = card.querySelector('.student-email').textContent.toLowerCase();
            
            if (name.includes(searchTerm) || email.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
});
