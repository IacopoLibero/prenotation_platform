<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'studente'){
    header('Location: ../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/home.css">
    <link rel="stylesheet" href="../styles/cerca_insegnante.css">
    <title>Cerca Insegnante</title>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Programma Lezioni</div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>
                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <h1>Cerca Insegnante</h1>
            <p>Trova insegnanti per nome, email o materia</p>
            
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
