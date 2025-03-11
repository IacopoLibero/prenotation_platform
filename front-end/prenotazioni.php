<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore'){
    header('Location: ../index.php');
    exit;
}

require_once '../connessione.php';

// Ottieni le lezioni prenotate
$email = $_SESSION['email'];
$query = "SELECT l.id, l.titolo, l.descrizione, l.start_time, l.end_time, l.stato,
          s.username as student_name, s.email as student_email 
          FROM Lezioni l 
          JOIN Studenti s ON l.student_email = s.email 
          WHERE l.teacher_email = ? AND l.stato IN ('prenotata', 'completata')
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
    <title>Prenotazioni Ricevute</title>
    <style>
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
            margin: 10px 0;
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
        .status.prenotata {
            background-color: #42a5f5;
        }
        .status.completata {
            background-color: #66bb6a;
        }
        .lesson-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .btn-complete, .btn-cancel {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            color: white;
        }
        .btn-complete {
            background-color: #66bb6a;
        }
        .btn-complete:hover {
            background-color: #4caf50;
        }
        .btn-cancel {
            background-color: #ef5350;
        }
        .btn-cancel:hover {
            background-color: #e53935;
        }
        .no-bookings {
            text-align: center;
            padding: 50px;
            color: #666;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .filter-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .filter-btn {
            margin: 0 10px;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            background-color: #f0f0f0;
        }
        .filter-btn.active {
            background-color: #2da0a8;
            color: white;
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
                <li><a href="disponibilita.php">Disponibilità</a></li>
                <li><a href="prenotazioni.php">Prenotazioni</a></li>
                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <h1>Prenotazioni Ricevute</h1>
            <p>Gestisci le prenotazioni effettuate dagli studenti</p>
            
            <div class="filter-container">
                <button class="filter-btn active" data-filter="all">Tutte</button>
                <button class="filter-btn" data-filter="prenotata">Prenotate</button>
                <button class="filter-btn" data-filter="completata">Completate</button>
            </div>
            
            <div class="lessons-container">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="lesson-card" data-status="<?= $row['stato'] ?>">
                            <div class="lesson-header">
                                <h3 class="lesson-title"><?= htmlspecialchars($row['titolo']) ?></h3>
                                <span class="status <?= $row['stato'] ?>"><?= ucfirst($row['stato']) ?></span>
                            </div>
                            <div class="lesson-time">
                                Data: <?= date('d/m/Y H:i', strtotime($row['start_time'])) ?> - 
                                <?= date('H:i', strtotime($row['end_time'])) ?>
                            </div>
                            <div class="lesson-student">
                                Studente: <?= htmlspecialchars($row['student_name']) ?> (<?= htmlspecialchars($row['student_email']) ?>)
                            </div>
                            <div class="lesson-description">
                                <?= nl2br(htmlspecialchars($row['descrizione'])) ?>
                            </div>
                            <?php if($row['stato'] === 'prenotata'): ?>
                                <div class="lesson-actions">
                                    <button class="btn-complete" onclick="completeLesson(<?= $row['id'] ?>)">Segna come Completata</button>
                                    <button class="btn-cancel" onclick="cancelLesson(<?= $row['id'] ?>)">Cancella Lezione</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-bookings">
                        <p>Non hai ancora ricevuto nessuna prenotazione.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>

    <script>
        // Filtro per stato lezione
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
        
        // Completa lezione
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
        
        // Cancella lezione
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
    </script>
</body>
</html>
