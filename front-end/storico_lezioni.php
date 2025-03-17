<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'studente'){
    header('Location: ../index.php');
    exit;
}

require_once '../connessione.php';

// Ottieni le lezioni prenotate dallo studente
$email = $_SESSION['email'];
$query = "SELECT l.id, l.titolo, l.descrizione, l.start_time, l.end_time, l.stato,
          p.username as teacher_name, p.email as teacher_email 
          FROM Lezioni l 
          JOIN Professori p ON l.teacher_email = p.email 
          WHERE l.student_email = ? 
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
    <title>Storico Lezioni</title>
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
        }
        .lesson-teacher {
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
        .status.cancellata {
            background-color: #ef5350;
        }
        .btn-cancel {
            background-color: #ef5350;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            margin-top: 10px;
        }
        .btn-cancel:hover {
            background-color: #d32f2f;
        }
        .no-lessons {
            text-align: center;
            padding: 50px;
            color: #666;
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
            <h1>Storico Lezioni</h1>
            <p>Visualizza la cronologia delle tue lezioni</p>
            
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
                            <div class="lesson-teacher">
                                Insegnante: <?= htmlspecialchars($row['teacher_name']) ?> (<?= htmlspecialchars($row['teacher_email']) ?>)
                            </div>
                            <div class="lesson-description">
                                <?= nl2br(htmlspecialchars($row['descrizione'])) ?>
                            </div>
                            <?php if($row['stato'] === 'prenotata' && strtotime($row['start_time']) > time()): ?>
                                <button class="btn-cancel" onclick="cancelBooking(<?= $row['id'] ?>)">Cancella prenotazione</button>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-lessons">
                        <p>Non hai ancora prenotato nessuna lezione.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>

    <script>
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
    </script>
</body>
</html>
