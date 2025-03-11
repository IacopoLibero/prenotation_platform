<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore'){
    header('Location: ../index.php');
    exit;
}

require_once '../connessione.php';

// Ottieni le lezioni del professore
$email = $_SESSION['email'];
$query = "SELECT l.id, l.titolo, l.descrizione, l.start_time, l.end_time, l.stato, s.username as student_name, s.email as student_email
          FROM Lezioni l
          LEFT JOIN Studenti s ON l.student_email = s.email
          WHERE l.teacher_email = ?
          ORDER BY l.start_time DESC";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/home.css">
    <title>Gestione Lezioni</title>
    <style>
        .action-buttons {
            margin-bottom: 20px;
        }
        .btn-create {
            background-color: #2da0a8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-create:hover {
            background-color: #238e95;
        }
        .lessons-container {
            margin-top: 30px;
        }
        .lesson-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        .lesson-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .lesson-title {
            font-size: 1.2rem;
            color: #2da0a8;
            margin: 0;
        }
        .lesson-time {
            color: #666;
        }
        .lesson-student {
            margin: 10px 0;
            font-weight: 500;
        }
        .lesson-description {
            margin-bottom: 15px;
            color: #333;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
        }
        .status.disponibile {
            background-color: #66bb6a;
        }
        .status.prenotata {
            background-color: #42a5f5;
        }
        .status.completata {
            background-color: #9575cd;
        }
        .status.cancellata {
            background-color: #ef5350;
        }
        .lesson-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .btn-edit, .btn-delete, .btn-complete {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            color: white;
        }
        .btn-edit {
            background-color: #42a5f5;
        }
        .btn-edit:hover {
            background-color: #2196f3;
        }
        .btn-delete {
            background-color: #ef5350;
        }
        .btn-delete:hover {
            background-color: #e53935;
        }
        .btn-complete {
            background-color: #66bb6a;
        }
        .btn-complete:hover {
            background-color: #4caf50;
        }
        .no-lessons {
            text-align: center;
            padding: 50px;
            color: #666;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            width: 70%;
            max-width: 600px;
            border-radius: 8px;
        }
        .close-modal {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .modal-title {
            margin-top: 0;
            color: #2da0a8;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group textarea {
            height: 100px;
        }
        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
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
                <li><a href="gestione_lezioni.php">Gestisci Lezioni</a></li>
                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <h1>Gestione Lezioni</h1>
            <p>Crea, modifica e gestisci le tue lezioni</p>
            
            <div class="action-buttons">
                <button id="createLessonBtn" class="btn-create">Crea Nuova Lezione</button>
            </div>
            
            <div class="lessons-container">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="lesson-card">
                            <div class="lesson-header">
                                <h3 class="lesson-title"><?= htmlspecialchars($row['titolo']) ?></h3>
                                <span class="status <?= $row['stato'] ?>"><?= ucfirst($row['stato']) ?></span>
                            </div>
                            <div class="lesson-time">
                                Data: <?= date('d/m/Y H:i', strtotime($row['start_time'])) ?> - 
                                <?= date('H:i', strtotime($row['end_time'])) ?>
                            </div>
                            <?php if($row['student_name']): ?>
                            <div class="lesson-student">
                                Studente: <?= htmlspecialchars($row['student_name']) ?> (<?= htmlspecialchars($row['student_email']) ?>)
                            </div>
                            <?php else: ?>
                            <div class="lesson-student">
                                Nessuno studente prenotato
                            </div>
                            <?php endif; ?>
                            <div class="lesson-description">
                                <?= nl2br(htmlspecialchars($row['descrizione'])) ?>
                            </div>
                            <div class="lesson-actions">
                                <?php if($row['stato'] == 'disponibile'): ?>
                                    <button class="btn-edit" onclick="editLesson(<?= $row['id'] ?>, '<?= addslashes(htmlspecialchars($row['titolo'])) ?>', '<?= addslashes(htmlspecialchars($row['descrizione'])) ?>', '<?= date('Y-m-d', strtotime($row['start_time'])) ?>', '<?= date('H:i', strtotime($row['start_time'])) ?>', '<?= date('Y-m-d', strtotime($row['end_time'])) ?>', '<?= date('H:i', strtotime($row['end_time'])) ?>')">Modifica</button>
                                    <button class="btn-delete" onclick="deleteLesson(<?= $row['id'] ?>)">Elimina</button>
                                <?php elseif($row['stato'] == 'prenotata'): ?>
                                    <button class="btn-complete" onclick="completeLesson(<?= $row['id'] ?>)">Segna come Completata</button>
                                    <button class="btn-delete" onclick="cancelLesson(<?= $row['id'] ?>)">Cancella Lezione</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-lessons">
                        <p>Non hai ancora creato nessuna lezione.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <!-- Modal per creare/modificare lezioni -->
    <div id="lessonModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 class="modal-title" id="modalTitle">Crea Nuova Lezione</h2>
            <form id="lessonForm">
                <input type="hidden" id="lessonId" name="lesson_id" value="">
                
                <div class="form-group">
                    <label for="titolo">Titolo:</label>
                    <input type="text" id="titolo" name="titolo" required>
                </div>
                
                <div class="form-group">
                    <label for="descrizione">Descrizione:</label>
                    <textarea id="descrizione" name="descrizione"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="start_date">Data inizio:</label>
                    <input type="date" id="start_date" name="start_date" required>
                </div>
                
                <div class="form-group">
                    <label for="start_time">Ora inizio:</label>
                    <input type="time" id="start_time" name="start_time" required>
                </div>
                
                <div class="form-group">
                    <label for="end_date">Data fine:</label>
                    <input type="date" id="end_date" name="end_date" required>
                </div>
                
                <div class="form-group">
                    <label for="end_time">Ora fine:</label>
                    <input type="time" id="end_time" name="end_time" required>
                </div>
                
                <div class="form-buttons">
                    <button type="button" onclick="closeModal()">Annulla</button>
                    <button type="submit" id="submitButton">Salva</button>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>
    
    <script>
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
    </script>
</body>
</html>
