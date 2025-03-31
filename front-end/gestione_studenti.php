<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore'){
    header('Location: ../index.php');
    exit;
}
$isTeacher = ($_SESSION['tipo'] === 'professore');
require_once '../connessione.php';

// Ottieni gli studenti che hanno prenotato lezioni con questo professore
$email = $_SESSION['email'];
$query = "SELECT DISTINCT s.username, s.email, COUNT(l.id) as total_lessons,
          SUM(CASE WHEN l.stato = 'completata' THEN 1 ELSE 0 END) as completed_lessons,
          SUM(CASE WHEN l.stato = 'prenotata' THEN 1 ELSE 0 END) as upcoming_lessons
          FROM Studenti s
          JOIN Lezioni l ON s.email = l.student_email
          WHERE l.teacher_email = ? AND l.stato IN ('prenotata', 'completata')
          GROUP BY s.email
          ORDER BY s.username";
          
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
    <link rel="stylesheet" href="../styles/gestione_studenti.css">
    <title>Gestione Studenti</title>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Gestione studenti</div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>
                <?php if($isTeacher): ?>
                    <li><a href="gestione_lezioni.php">Gestisci Lezioni</a></li>
                    <li><a href="disponibilita.php">Disponibilità</a></li>
                    <li><a href="prenotazioni.php">Prenotazioni</a></li>
                    <li><a href="report.php">Report</a></li>
                <?php endif; ?>

                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <p>Visualizza gli studenti che seguono le tue lezioni</p>
            
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Cerca studente per nome o email...">
            </div>
            
            <div class="students-container">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="student-card">
                            <h3 class="student-name"><?= htmlspecialchars($row['username']) ?></h3>
                            <div class="student-email"><?= htmlspecialchars($row['email']) ?></div>
                            <div class="stats">
                                <div class="stat-item">
                                    <span class="stat-value"><?= $row['total_lessons'] ?></span>
                                    <span class="stat-label">Lezioni Totali</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?= $row['completed_lessons'] ?></span>
                                    <span class="stat-label">Completate</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?= $row['upcoming_lessons'] ?></span>
                                    <span class="stat-label">In Programma</span>
                                </div>
                            </div>
                            <a href="student_details.php?email=<?= urlencode($row['email']) ?>" class="student-details-btn">
                                Dettagli Lezioni
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-students">
                        <p>Non hai ancora studenti che hanno prenotato le tue lezioni.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>

    <script src="../js/gestione_studenti.js"></script>
</body>
</html>
