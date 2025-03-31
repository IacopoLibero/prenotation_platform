<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore'){
    header('Location: ../index.php');
    exit;
}
$isTeacher = ($_SESSION['tipo'] === 'professore');
require_once '../connessione.php';

// Ottieni le lezioni del professore prenotate da studenti
$email = $_SESSION['email'];
$query = "SELECT l.id, l.titolo, l.descrizione, l.start_time, l.end_time, l.stato, s.username as student_name, s.email as student_email
          FROM Lezioni l
          LEFT JOIN Studenti s ON l.student_email = s.email
          WHERE l.teacher_email = ? AND l.student_email IS NOT NULL
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
    <link rel="stylesheet" href="../styles/gestione_lezioni.css?v=<?php echo time(); ?>">
    <title>Gestione Lezioni</title>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Gestione lezioni</div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>
                <?php if($isTeacher): ?>
                    <li><a href="disponibilita.php">Disponibilità</a></li>
                    <li><a href="prenotazioni.php">Prenotazioni</a></li>
                    <li><a href="gestione_studenti.php">Studenti</a></li>
                    <li><a href="report.php">Report</a></li>
                <?php endif; ?>

                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
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
    
    <script src="../js/gestione_lezioni.js?v=<?php echo time(); ?>"></script>
</body>
</html>
