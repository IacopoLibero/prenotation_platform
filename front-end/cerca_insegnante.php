<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'studente'){
    header('Location: ../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/home.css">
    <title>Cerca Insegnante</title>
    <style>
        .search-container {
            margin: 30px 0;
            text-align: center;
        }
        .search-box {
            display: flex;
            max-width: 600px;
            margin: 0 auto;
        }
        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
            font-size: 1rem;
        }
        .search-button {
            background-color: #2da0a8;
            color: white;
            border: none;
            padding: 0 20px;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            font-weight: 500;
        }
        .search-button:hover {
            background-color: #238e95;
        }
        .teachers-container {
            margin-top: 30px;
        }
        .teacher-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        .teacher-name {
            font-size: 1.2rem;
            color: #2da0a8;
            margin-bottom: 5px;
        }
        .teacher-email {
            color: #666;
            margin-bottom: 10px;
        }
        .teacher-subjects {
            margin: 10px 0;
            font-style: italic;
        }
        .teacher-bio {
            margin: 15px 0;
        }
        .btn-favorite {
            background-color: #ff7043;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-favorite:hover {
            background-color: #f4511e;
        }
        .no-results {
            text-align: center;
            padding: 50px;
            color: #666;
        }
        .google-calendar-badge {
            display: inline-block;
            background-color: #4285F4;
            color: white;
            font-size: 0.75rem;
            padding: 3px 6px;
            border-radius: 3px;
            margin-left: 6px;
        }
        .teacher-info {
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Programma Lezioni</div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>
                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <h1>Cerca Insegnante</h1>
            <p>Trova insegnanti per nome, email o materia</p>
            
            <div class="search-container">
                <div class="search-box">
                    <input type="text" id="searchInput" class="search-input" placeholder="Cerca per nome, email o materia...">
                    <button id="searchButton" class="search-button">Cerca</button>
                </div>
            </div>
            
            <div class="teachers-container" id="resultsContainer"></div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>

    <script>
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
    </script>
</body>
</html>
