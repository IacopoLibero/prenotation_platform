<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore'){
    header('Location: ../index.php');
    exit;
}
$isTeacher = ($_SESSION['tipo'] === 'professore');
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
    <link rel="stylesheet" href="../styles/prenotazioni.css?v=<?php echo time(); ?>">
    <title>Prenotazioni Ricevute</title>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Prenotazioni ricevute</div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>
                <?php if($isTeacher): ?>
                    <li><a href="gestione_lezioni.php">Gestisci Lezioni</a></li>
                    <li><a href="disponibilita.php">Disponibilità</a></li>
                    <li><a href="gestione_studenti.php">Studenti</a></li>
                    <li><a href="report.php">Report</a></li>
                <?php endif; ?>

                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <p>Gestisci le prenotazioni effettuate dagli studenti</p>
            
            <!-- Ad container with proper styling -->
            <div class="ad-container" style="text-align: center; margin: 20px auto; max-width: 300px; min-height: 250px; overflow: hidden;">
                <script>
                    !function(d,l,e,s,c){
                        e=d.createElement("script");
                        e.src="//ad.altervista.org/js.ad/size=300X250/?ref="+encodeURIComponent(l.hostname+l.pathname)+"&r="+Date.now();
                        s=d.scripts;
                        c=d.currentScript||s[s.length-1];
                        c.parentNode.insertBefore(e,c)
                    }(document,location)
                </script>
            </div>
            
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

    <script src="../js/prenotazioni.js?v=<?php echo time(); ?>"></script>
</body>
</html>
