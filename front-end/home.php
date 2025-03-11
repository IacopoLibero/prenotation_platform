<?php
    session_start();
    if(!isset($_SESSION['user'])){
        header('Location: ../index.php');
    }
    $isTeacher = ($_SESSION['tipo'] === 'professore');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/home.css">
    <title>Home</title>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Programma Lezioni</div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="user_account.php">Account</a></li>
                <?php if($isTeacher): ?>
                <li><a href="gestione_lezioni.php">Gestisci Lezioni</a></li>
                <?php else: ?>
                <li><a href="prenota_lezioni.php">Prenota Lezioni</a></li>
                <?php endif; ?>
                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <section class="welcome">
            <h1>Benvenuto, <?php echo $_SESSION['user']; ?>!</h1>
            <p>Gestisci le tue lezioni in modo semplice e veloce.</p>
            <p class="user-type">Sei connesso come: <span class="badge <?php echo $isTeacher ? 'teacher' : 'student'; ?>"><?php echo $isTeacher ? 'Professore' : 'Studente'; ?></span></p>
        </section>
        
        <section class="dashboard">
            <h2>Dashboard</h2>
            
            <?php if($isTeacher): ?>
            <!-- Dashboard per professori -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Lezioni Programmate</h3>
                    <div class="count">0</div>
                    <a href="gestione_lezioni.php">Visualizza</a>
                </div>
                <div class="dashboard-card">
                    <h3>Ore Insegnate</h3>
                    <div class="count">0</div>
                    <a href="report.php">Dettagli</a>
                </div>
                <div class="dashboard-card">
                    <h3>Studenti Totali</h3>
                    <div class="count">0</div>
                    <a href="gestione_studenti.php">Visualizza</a>
                </div>
            </div>
            <?php else: ?>
            <!-- Dashboard per studenti -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Lezioni Prenotate</h3>
                    <div class="count">0</div>
                    <a href="prenota_lezioni.php">Visualizza</a>
                </div>
                <div class="dashboard-card">
                    <h3>Ore di Lezione</h3>
                    <div class="count">0</div>
                    <a href="storico_lezioni.php">Dettagli</a>
                </div>
                <div class="dashboard-card">
                    <h3>Insegnanti</h3>
                    <div class="count">0</div>
                    <a href="cerca_insegnante.php">Cerca</a>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </main>
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>
</body>
</html>