document.getElementById('searchButton').addEventListener('click', searchTeachers);
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchTeachers();
    }
});

function searchTeachers() {
    const searchTerm = document.getElementById('searchInput').value.trim();
    
    if (searchTerm.length < 2) {
        alert('Inserisci almeno 2 caratteri per la ricerca');
        return;
    }
    
    fetch(`../api/search_teacher.php?search=${encodeURIComponent(searchTerm)}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('resultsContainer');
            
            if (data.success && data.teachers.length > 0) {
                let html = '';
                
                data.teachers.forEach(teacher => {
                    const hasGoogleCalendar = teacher.google_calendar_link !== null;
                    
                    html += `
                        <div class="teacher-card">
                            <div class="teacher-info">
                                <h3 class="teacher-name">${teacher.username}</h3>
                                ${hasGoogleCalendar ? '<span class="google-calendar-badge">Google Calendar</span>' : ''}
                            </div>
                            <div class="teacher-email">${teacher.email}</div>
                            ${teacher.materie ? `<div class="teacher-subjects">Materie: ${teacher.materie}</div>` : ''}
                            ${teacher.bio ? `<div class="teacher-bio">${teacher.bio}</div>` : ''}
                            <div class="teacher-actions">
                                <a href="orari_insegnanti.php?email=${encodeURIComponent(teacher.email)}" class="view-availability">Visualizza disponibilità</a>
                            </div>
                        </div>
                    `;
                });
                
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="no-results">
                        <p>Nessun insegnante trovato per "${searchTerm}".</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('resultsContainer').innerHTML = `
                <div class="no-results">
                    <p>Si è verificato un errore durante la ricerca.</p>
                </div>
            `;
        });
}

function addFavorite(teacherEmail) {
    const formData = new FormData();
    formData.append('teacher_email', teacherEmail);
    
    fetch('../api/add_favorite.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Si è verificato un errore durante l\'aggiunta ai preferiti.');
    });
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
