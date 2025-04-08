<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'studente'){
    header('Location: ../index.php');
    exit;
}
$isTeacher = ($_SESSION['tipo'] === 'professore');
require_once '../connessione.php';

// Ottieni tutti i professori
$query = "SELECT DISTINCT p.email, p.username 
          FROM Professori p 
          JOIN Lezioni l ON p.email = l.teacher_email 
          WHERE l.stato = 'disponibile' AND l.start_time > NOW()
          ORDER BY p.username";
$stmt = $conn->prepare($query);
$stmt->execute();
$result_teachers = $stmt->get_result();

// Ottieni le materie disponibili
$query = "SELECT DISTINCT materie 
          FROM Professori 
          WHERE materie IS NOT NULL AND materie != ''";
$stmt = $conn->prepare($query);
$stmt->execute();
$result_subjects = $stmt->get_result();

// Array per memorizzare le materie
$all_subjects = [];
while ($row = $result_subjects->fetch_assoc()) {
    // Split materie separate da virgole
    $subject_array = explode(',', $row['materie']);
    foreach ($subject_array as $subject) {
        $subject = trim($subject);
        if (!empty($subject) && !in_array($subject, $all_subjects)) {
            $all_subjects[] = $subject;
        }
    }
}
sort($all_subjects);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/home.css">
    <link rel="stylesheet" href="../styles/prenota_lezioni.css?v=<?php echo time(); ?>">
    <title>Prenota Lezioni</title>
    <!-- Include ad handler script -->
    <script src="../js/ad-handler.js?v=<?php echo time(); ?>"></script>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Prenota lezioni</div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>
                
                <?php if(!$isTeacher): ?>
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
            <p>Visualizza e prenota le lezioni disponibili</p>
            
            <!-- Include standardized ad container -->
            <?php include_once('../includes/ad-container.php'); ?>
            
            <div class="filter-section">
                <div class="filter-controls">
                    <div class="filter-group">
                        <label for="teacherSelect">Insegnante:</label>
                        <select id="teacherSelect" class="filter-select">
                            <option value="">Tutti gli insegnanti</option>
                            <?php while($row = $result_teachers->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($row['email']) ?>"><?= htmlspecialchars($row['username']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="subjectSelect">Materia:</label>
                        <select id="subjectSelect" class="filter-select">
                            <option value="">Tutte le materie</option>
                            <?php foreach($all_subjects as $subject): ?>
                                <option value="<?= htmlspecialchars($subject) ?>"><?= htmlspecialchars($subject) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="dateFilter">Data:</label>
                        <input type="date" id="dateFilter" class="date-filter" min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <button id="resetFilters" class="reset-btn">Reimposta filtri</button>
                </div>
            </div>
            
            <div id="lessonsContainer" class="lessons-container">
                <div class="loading-indicator">
                    <div class="spinner"></div>
                    <p>Caricamento lezioni disponibili...</p>
                </div>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>

    <script src="../js/prenota_lezioni.js?v=<?php echo time(); ?>"></script>
</body>
</html>