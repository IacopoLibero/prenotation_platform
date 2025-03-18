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
