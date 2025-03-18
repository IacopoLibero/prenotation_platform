<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore'){
    header('Location: ../index.php');
    exit;
}

require_once '../connessione.php';

// Ottieni le statistiche delle lezioni
$email = $_SESSION['email'];

// Totale lezioni per stato
$query_count = "SELECT 
                  COUNT(*) as total_lessons,
                  SUM(CASE WHEN stato = 'disponibile' THEN 1 ELSE 0 END) as available_count,
                  SUM(CASE WHEN stato = 'prenotata' THEN 1 ELSE 0 END) as booked_count,
                  SUM(CASE WHEN stato = 'completata' THEN 1 ELSE 0 END) as completed_count,
                  SUM(CASE WHEN stato = 'cancellata' THEN 1 ELSE 0 END) as cancelled_count
                FROM Lezioni 
                WHERE teacher_email = ?";
$stmt = $conn->prepare($query_count);
$stmt->bind_param("s", $email);
$stmt->execute();
$result_count = $stmt->get_result();
$counts = $result_count->fetch_assoc();

// Calcola le ore di lezione
$query_hours = "SELECT 
                  SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_minutes,
                  SUM(CASE WHEN stato = 'completata' THEN TIMESTAMPDIFF(MINUTE, start_time, end_time) ELSE 0 END) as completed_minutes
                FROM Lezioni 
                WHERE teacher_email = ?";
$stmt = $conn->prepare($query_hours);
$stmt->bind_param("s", $email);
$stmt->execute();
$result_hours = $stmt->get_result();
$hours = $result_hours->fetch_assoc();

// Converti minuti in ore e minuti
$total_hours = floor($hours['total_minutes'] / 60);
$total_minutes = $hours['total_minutes'] % 60;
$completed_hours = floor($hours['completed_minutes'] / 60);
$completed_minutes = $hours['completed_minutes'] % 60;

// Conta studenti unici
$query_students = "SELECT COUNT(DISTINCT student_email) as student_count
                  FROM Lezioni 
                  WHERE teacher_email = ? AND student_email IS NOT NULL";
$stmt = $conn->prepare($query_students);
$stmt->bind_param("s", $email);
$stmt->execute();
$result_students = $stmt->get_result();
$students = $result_students->fetch_assoc();

// Lezioni per mese (ultimi 6 mesi)
$query_monthly = "SELECT 
                    YEAR(start_time) as year,
                    MONTH(start_time) as month, 
                    COUNT(*) as lesson_count,
                    SUM(CASE WHEN stato = 'completata' THEN 1 ELSE 0 END) as completed_count
                  FROM Lezioni 
                  WHERE teacher_email = ? 
                    AND start_time >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                  GROUP BY YEAR(start_time), MONTH(start_time)
                  ORDER BY YEAR(start_time) DESC, MONTH(start_time) DESC";
$stmt = $conn->prepare($query_monthly);
$stmt->bind_param("s", $email);
$stmt->execute();
$result_monthly = $stmt->get_result();

// Nomi dei mesi in Italiano
$month_names = [
    1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile', 5 => 'Maggio', 6 => 'Giugno',
    7 => 'Luglio', 8 => 'Agosto', 9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/home.css">
    <link rel="stylesheet" href="../styles/report.css?v=<?php echo time(); ?>">
    <title>Report Lezioni</title>
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
                <li><a href="gestione_studenti.php">Studenti</a></li>
                <li><a href="report.php">Report</a></li>
                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <h1>Report Lezioni</h1>
            <p>Panoramica delle tue attività di insegnamento</p>
            
            <div class="report-container">
                <div class="stat-cards">
                    <div class="stat-card">
                        <div class="stat-value"><?= $counts['total_lessons'] ?: 0 ?></div>
                        <div class="stat-label">Lezioni Totali</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $counts['completed_count'] ?: 0 ?></div>
                        <div class="stat-label">Lezioni Completate</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $counts['booked_count'] ?: 0 ?></div>
                        <div class="stat-label">Lezioni Prenotate</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $students['student_count'] ?: 0 ?></div>
                        <div class="stat-label">Studenti Totali</div>
                    </div>
                </div>
                
                <div class="chart-container">
                    <h2 class="chart-title">Ore di Lezione</h2>
                    <div class="stat-cards">
                        <div class="stat-card">
                            <div class="stat-value"><?= $total_hours ?>h <?= $total_minutes ?>m</div>
                            <div class="stat-label">Ore Totali</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?= $completed_hours ?>h <?= $completed_minutes ?>m</div>
                            <div class="stat-label">Ore Completate</div>
                        </div>
                    </div>
                </div>
                
                <div class="chart-container">
                    <h2 class="chart-title">Lezioni negli Ultimi 6 Mesi</h2>
                    
                    <?php if ($result_monthly->num_rows > 0): ?>
                        <table class="monthly-table">
                            <thead>
                                <tr>
                                    <th>Mese</th>
                                    <th>Anno</th>
                                    <th>Lezioni Totali</th>
                                    <th>Lezioni Completate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result_monthly->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $month_names[$row['month']] ?></td>
                                    <td><?= $row['year'] ?></td>
                                    <td><?= $row['lesson_count'] ?></td>
                                    <td><?= $row['completed_count'] ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-data">
                            <p>Non ci sono dati disponibili per gli ultimi 6 mesi.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>

    <script src="../js/report.js?v=<?php echo time(); ?>"></script>
</body>
</html>
