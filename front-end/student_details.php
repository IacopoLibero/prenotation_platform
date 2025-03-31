<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore'){
    header('Location: ../index.php');
    exit;
}

require_once '../connessione.php';

// Verifica che sia stato passato un parametro email
if (!isset($_GET['email']) || empty($_GET['email'])) {
    header('Location: gestione_studenti.php');
    exit;
}

$student_email = $_GET['email'];
$teacher_email = $_SESSION['email'];

// Recupera le informazioni dello studente
$query_student = "SELECT username, email FROM Studenti WHERE email = ?";
$stmt = $conn->prepare($query_student);
$stmt->bind_param("s", $student_email);
$stmt->execute();
$result_student = $stmt->get_result();

if ($result_student->num_rows === 0) {
    // Studente non trovato
    header('Location: gestione_studenti.php');
    exit;
}

$student = $result_student->fetch_assoc();

// Recupera le lezioni dello studente con questo professore
$query_lessons = "SELECT l.id, l.titolo, l.descrizione, l.start_time, l.end_time, l.stato
                 FROM Lezioni l
                 WHERE l.teacher_email = ? AND l.student_email = ?
                 ORDER BY l.start_time DESC";
                 
$stmt = $conn->prepare($query_lessons);
$stmt->bind_param("ss", $teacher_email, $student_email);
$stmt->execute();
$result_lessons = $stmt->get_result();

// Calcola statistiche
$query_stats = "SELECT 
                COUNT(*) as total_lessons,
                SUM(CASE WHEN stato = 'completata' THEN 1 ELSE 0 END) as completed_lessons,
                SUM(CASE WHEN stato = 'prenotata' THEN 1 ELSE 0 END) as upcoming_lessons,
                SUM(CASE WHEN stato = 'cancellata' THEN 1 ELSE 0 END) as cancelled_lessons,
                SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_minutes,
                SUM(CASE WHEN stato = 'completata' THEN TIMESTAMPDIFF(MINUTE, start_time, end_time) ELSE 0 END) as completed_minutes
               FROM Lezioni 
               WHERE teacher_email = ? AND student_email = ?";
               
$stmt = $conn->prepare($query_stats);
$stmt->bind_param("ss", $teacher_email, $student_email);
$stmt->execute();
$result_stats = $stmt->get_result();
$stats = $result_stats->fetch_assoc();

// Converti minuti in ore e minuti
$total_hours = floor($stats['total_minutes'] / 60);
$total_minutes = $stats['total_minutes'] % 60;
$completed_hours = floor($stats['completed_minutes'] / 60);
$completed_minutes = $stats['completed_minutes'] % 60;
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/home.css">
    <link rel="stylesheet" href="../styles/student_details.css?v=<?php echo time(); ?>">
    <title>Dettagli Studente</title>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Dettagli studenti</div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>
                <?php if($isTeacher): ?>
                    <li><a href="gestione_lezioni.php">Gestisci Lezioni</a></li>
                    <li><a href="disponibilita.php">Disponibilità</a></li>
                    <li><a href="prenotazioni.php">Prenotazioni</a></li>
                    <li><a href="gestione_studenti.php">Studenti</a></li>
                    <li><a href="report.php">Report</a></li>
                <?php endif; ?>
                
                <?php if(!$isTeacher): ?>
                    <li><a href="prenota_lezioni.php">Prenota Lezioni</a></li>
                    <li><a href="orari_insegnanti.php">Orari Insegnanti</a></li>
                    <li><a href="storico_lezioni.php">Storico Lezioni</a></li>
                    <li><a href="cerca_insegnante.php">Cerca Insegnante</a></li>
                <?php endif; ?>

                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <a href="gestione_studenti.php" class="back-btn">← Torna alla lista studenti</a>
            
            <div class="student-info">
                <h1 class="student-name"><?= htmlspecialchars($student['username']) ?></h1>
                <div class="student-email"><?= htmlspecialchars($student['email']) ?></div>
                
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['total_lessons'] ?></div>
                        <div class="stat-label">Lezioni Totali</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['completed_lessons'] ?></div>
                        <div class="stat-label">Completate</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['upcoming_lessons'] ?></div>
                        <div class="stat-label">In Programma</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $total_hours ?>h <?= $total_minutes ?>m</div>
                        <div class="stat-label">Ore Totali</div>
                    </div>
                </div>
            </div>
            
            <h2 class="lessons-title">Storico Lezioni</h2>
            
            <div class="lessons-container">
                <?php if ($result_lessons->num_rows > 0): ?>
                    <?php while($row = $result_lessons->fetch_assoc()): ?>
                        <div class="lesson-card">
                            <div class="lesson-header">
                                <h3 class="lesson-title"><?= htmlspecialchars($row['titolo']) ?></h3>
                                <span class="status <?= $row['stato'] ?>"><?= ucfirst($row['stato']) ?></span>
                            </div>
                            <div class="lesson-time">
                                Data: <?= date('d/m/Y H:i', strtotime($row['start_time'])) ?> - 
                                <?= date('H:i', strtotime($row['end_time'])) ?>
                            </div>
                            <div class="lesson-description">
                                <?= nl2br(htmlspecialchars($row['descrizione'])) ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-lessons">
                        <p>Non ci sono lezioni registrate per questo studente.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>

    <script src="../js/student_details.js?v=<?php echo time(); ?>"></script>
</body>
</html>
