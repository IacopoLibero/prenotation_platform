function cancelBooking(lessonId) {
    if (confirm('Sei sicuro di voler cancellare questa prenotazione?')) {
        const formData = new FormData();
        formData.append('lesson_id', lessonId);
        
        fetch('../api/cancel_booking.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Si è verificato un errore durante la cancellazione della prenotazione.');
        });
    }
}

// Filtro per stato lezione
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Rimuovi classe active da tutti i bottoni
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Aggiungi classe active al bottone cliccato
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            
            document.querySelectorAll('.lesson-card').forEach(card => {
                if (filter === 'all' || card.dataset.status === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});
