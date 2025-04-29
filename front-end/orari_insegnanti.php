<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'studente') {
    header('Location: ../index.php');
    exit;
}

require_once '../connessione.php';

// Recupera la lista di tutti i professori disponibili
$query = "SELECT 
          p.username, 
          p.email, 
          (SELECT COUNT(*) FROM Calendari_Professori cp WHERE cp.teacher_email = p.email) > 0 AS has_calendar
          FROM Professori p
          ORDER BY p.username";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orari Insegnanti</title>
    <link rel="stylesheet" href="../styles/home.css">
    <link rel="stylesheet" href="../styles/orari_insegnanti.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Include ad handler script -->
    <script src="../js/ad-handler.js?v=<?php echo time(); ?>"></script>
    <style>
        .realtime-indicator {
            background-color: #4285F4;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .realtime-indicator img {
            height: 18px;
            margin-right: 8px;
        }
        
        .google-badge {
            background-color: transparent;
            display: inline-flex;
            align-items: center;
            margin-left: 5px;
        }
        
        .google-badge img {
            margin-right: 3px;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Orari Insegnanti</div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>
                <li><a href="orari_insegnanti.php">Orari Insegnanti</a></li>
                <li><a href="cerca_insegnante.php">Cerca Insegnante</a></li>
                <li><a href="storico_lezioni.php">Le Mie Lezioni</a></li>
                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <!-- Include standardized ad container -->
        <?php include_once('../includes/ad-container.php'); ?>
        
        <section>
            <div class="teacher-availability">
                <h1>Disponibilità degli Insegnanti</h1>
                
                <div class="realtime-indicator" id="realtimeIndicator">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Calendar_icon_%282020%29.svg" alt="Google Calendar">
                    Disponibilità in tempo reale sincronizzata con Google Calendar
                </div>
                
                <div class="teacher-select-container">
                    <label for="teacherSelect">Seleziona un insegnante:</label>
                    <select id="teacherSelect">
                        <option value="">-- Seleziona --</option>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['email']) ?>" 
                                    data-has-calendar="<?= $row['has_calendar'] ? '1' : '0' ?>">
                                <?= htmlspecialchars($row['username']) ?> 
                                <?= $row['has_calendar'] ? '(Google Calendar)' : '' ?>
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
    
    <script src="../js/orari_insegnanti.js?v=<?php echo time(); ?>"></script>
</body>
</html>
