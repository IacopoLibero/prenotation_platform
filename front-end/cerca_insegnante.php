<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'studente'){
    header('Location: ../index.php');
    exit;
}
$isTeacher = ($_SESSION['tipo'] === 'professore');
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/home.css">
    <link rel="stylesheet" href="../styles/cerca_insegnante.css">
    <title>Cerca Insegnante</title>
    <!-- Include ad handler script -->
    <script src="../js/ad-handler.js?v=<?php echo time(); ?>"></script>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Cerca insegnante</div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>
                
                <?php if(!$isTeacher): ?>
                    <li><a href="prenota_lezioni.php">Prenota Lezioni</a></li>
                    <li><a href="orari_insegnanti.php">Orari Insegnanti</a></li>
                    <li><a href="storico_lezioni.php">Storico Lezioni</a></li>
                <?php endif; ?>

                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <p>Trova insegnanti per nome, email o materia</p>
            
            <!-- Include standardized ad container -->
            <?php include_once('../includes/ad-container.php'); ?>
            
            <div class="search-container">
                <div class="search-box">
                    <input type="text" id="searchInput" class="search-input" placeholder="Cerca per nome, email o materia...">
                    <button id="searchButton" class="search-button">Cerca</button>
                </div>
            </div>
            
            <div class="teachers-container" id="resultsContainer"></div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>
    
    <script src="../js/cerca_insegnante.js"></script>
</body>
</html>
