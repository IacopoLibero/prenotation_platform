<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'studente'){
    header('Location: ../index.php');
    exit;
}
$isTeacher = ($_SESSION['tipo'] === 'professore');
require_once '../connessione.php';

// Ottieni tutti gli insegnanti
$query = "SELECT username, email, google_calendar_link FROM Professori ORDER BY username";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/home.css">
    <link rel="stylesheet" href="../styles/orari_insegnanti.css?v=<?php echo time(); ?>">
    <title>Orari Insegnanti</title>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Orari insegnanti</div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>
                
                <?php if(!$isTeacher): ?>
                    <li><a href="prenota_lezioni.php">Prenota Lezioni</a></li>
                    <li><a href="storico_lezioni.php">Storico Lezioni</a></li>
                    <li><a href="cerca_insegnante.php">Cerca Insegnante</a></li>
                <?php endif; ?>

                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <p>Visualizza gli orari disponibili degli insegnanti</p>
            
            <div class="teachers-section">
                <div class="teacher-selector">
                    <select id="teacherSelect" class="teacher-select">
                        <option value="">Seleziona un insegnante</option>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['email']) ?>" 
                                    data-has-calendar="<?= !empty($row['google_calendar_link']) ? '1' : '0' ?>">
                                <?= htmlspecialchars($row['username']) ?> 
                                <?= !empty($row['google_calendar_link']) ? '(Google Calendar)' : '' ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div id="calendarInfoBox" class="google-calendar-info" style="display:none;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" class="google-calendar-icon" alt="Google Calendar">
                    Questo insegnante sincronizza la sua disponibilità con Google Calendar.
                </div>
                
                <div class="availability-container" id="availabilityContainer">
                    <div class="no-availability">
                        <p>Seleziona un insegnante per visualizzare la sua disponibilità.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>

    <!-- Include the extracted JavaScript file, with cache-busting parameter -->
    <script src="../js/orari_insegnanti.js?v=<?php echo time(); ?>"></script>
</body>
</html>
