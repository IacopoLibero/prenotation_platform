<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'studente'){
    header('Location: ../index.php');
    exit;
}
$isTeacher = ($_SESSION['tipo'] === 'professore');
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
    <link rel="stylesheet" href="../styles/storico_lezioni.css?v=<?php echo time(); ?>">
    <title>Storico Lezioni</title>
    <!-- Include ad handler script -->
    <script src="../js/ad-handler.js?v=<?php echo time(); ?>"></script>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Storico lezioni</div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>
                
                <?php if(!$isTeacher): ?>
                    <li><a href="orari_insegnanti.php">Orari Insegnanti</a></li>
                    <li><a href="cerca_insegnante.php">Cerca Insegnante</a></li>
                <?php endif; ?>

                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            
            <!-- Include standardized ad container -->
            <?php include_once('../includes/ad-container.php'); ?>
            
            <!-- Add filter buttons -->
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

    <script src="../js/storico_lezioni.js?v=<?php echo time(); ?>"></script>
</body>
</html>
