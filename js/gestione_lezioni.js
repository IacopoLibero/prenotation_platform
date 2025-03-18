// Modal handling
const modal = document.getElementById('lessonModal');
const form = document.getElementById('lessonForm');
let isEdit = false;

document.getElementById('createLessonBtn').addEventListener('click', () => {
    document.getElementById('modalTitle').textContent = 'Crea Nuova Lezione';
    document.getElementById('lessonId').value = '';
    document.getElementById('titolo').value = '';
    document.getElementById('descrizione').value = '';
    
    // Set default dates to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('start_date').value = today;
    document.getElementById('end_date').value = today;
    
    // Set default times
    document.getElementById('start_time').value = '10:00';
    document.getElementById('end_time').value = '11:00';
    
    document.getElementById('submitButton').textContent = 'Crea';
    isEdit = false;
    modal.style.display = 'block';
});

function editLesson(id, titolo, descrizione, startDate, startTime, endDate, endTime) {
    document.getElementById('modalTitle').textContent = 'Modifica Lezione';
    document.getElementById('lessonId').value = id;
    document.getElementById('titolo').value = titolo;
    document.getElementById('descrizione').value = descrizione;
    document.getElementById('start_date').value = startDate;
    document.getElementById('start_time').value = startTime;
    document.getElementById('end_date').value = endDate;
    document.getElementById('end_time').value = endTime;
    
    document.getElementById('submitButton').textContent = 'Aggiorna';
    isEdit = true;
    modal.style.display = 'block';
}

function closeModal() {
    modal.style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target == modal) {
        closeModal();
    }
}

// Form submission
form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(form);
    const url = isEdit ? '../api/update_lesson.php' : '../api/create_lesson.php';
    
    fetch(url, {
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
        alert('Si è verificato un errore durante l\'operazione.');
    });
});

// Delete lesson
function deleteLesson(lessonId) {
    if (confirm('Sei sicuro di voler eliminare questa lezione?')) {
        const formData = new FormData();
        formData.append('lesson_id', lessonId);
        
        fetch('../api/delete_lesson.php', {
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
            alert('Si è verificato un errore durante l\'eliminazione.');
        });
    }
}

// Cancel lesson
function cancelLesson(lessonId) {
    if (confirm('Sei sicuro di voler cancellare questa lezione? Lo studente riceverà una notifica.')) {
        const formData = new FormData();
        formData.append('lesson_id', lessonId);
        
        fetch('../api/delete_lesson.php', {
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
            alert('Si è verificato un errore durante la cancellazione.');
        });
    }
}

// Complete lesson
function completeLesson(lessonId) {
    if (confirm('Sei sicuro di voler segnare questa lezione come completata?')) {
        const formData = new FormData();
        formData.append('lesson_id', lessonId);
        
        fetch('../api/complete_lesson.php', {
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
            alert('Si è verificato un errore durante l\'operazione.');
        });
    }
}
