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
    <link rel="stylesheet" href="../styles/profile.css">
    <title>Account Utente</title>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Il tuo account</div>
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
        <section class="profile-header">
            <h1>Il Mio Profilo</h1>
            <div class="user-type-badge <?php echo $isTeacher ? 'teacher' : 'student'; ?>">
                <?php echo $isTeacher ? 'Professore' : 'Studente'; ?>
            </div>
        </section>
        
        <section class="account-info">
            <h2>Informazioni Personali</h2>
            <form action="../api/update_account.php" method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo $_SESSION['user']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo $_SESSION['email']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password (lascia vuoto per mantenere la password attuale):</label>
                    <input type="password" id="password" name="password">
                </div>
                
                <?php if($isTeacher): ?>
                <div class="form-group">
                    <label for="materie">Materie insegnate:</label>
                    <input type="text" id="materie" name="materie" value="<?php echo isset($_SESSION['materie']) ? $_SESSION['materie'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="bio">Biografia:</label>
                    <textarea id="bio" name="bio"><?php echo isset($_SESSION['bio']) ? $_SESSION['bio'] : ''; ?></textarea>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn-update">Aggiorna Profilo</button>
            </form>
        </section>
        
        <?php if($isTeacher): ?>
        <!-- Sezione per i professori -->
        <section class="teacher-features">
            <h2>Strumenti Insegnante</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <h3>Gestione Lezioni</h3>
                    <p>Crea, modifica e cancella le tue lezioni</p>
                    <a href="gestione_lezioni.php" class="btn-feature">Gestisci</a>
                </div>
                <div class="feature-card">
                    <h3>Imposta Disponibilità</h3>
                    <p>Configura i tuoi orari disponibili per le lezioni</p>
                    <a href="disponibilita.php" class="btn-feature">Imposta</a>
                </div>
                <div class="feature-card">
                    <h3>Prenotazioni Ricevute</h3>
                    <p>Visualizza tutte le prenotazioni degli studenti</p>
                    <a href="prenotazioni.php" class="btn-feature">Visualizza</a>
                </div>
                <div class="feature-card">
                    <h3>Gestione Studenti</h3>
                    <p>Visualizza e gestisci gli studenti che seguono le tue lezioni</p>
                    <a href="gestione_studenti.php" class="btn-feature">Gestisci</a>
                </div>
                <div class="feature-card">
                    <h3>Reportistica</h3>
                    <p>Genera report sulle lezioni effettuate</p>
                    <a href="report.php" class="btn-feature">Report</a>
                </div>
            </div>
        </section>
        <?php else: ?>
        <!-- Sezione per gli studenti -->
        <section class="student-features">
            <h2>Strumenti Studente</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <h3>Prenota Lezioni</h3>
                    <p>Visualizza e prenota le lezioni disponibili</p>
                    <a href="prenota_lezioni.php" class="btn-feature">Prenota</a>
                </div>
                <div class="feature-card">
                    <h3>Orari Insegnanti</h3>
                    <p>Visualizza gli orari disponibili degli insegnanti</p>
                    <a href="orari_insegnanti.php" class="btn-feature">Visualizza</a>
                </div>
                <div class="feature-card">
                    <h3>Storico Lezioni</h3>
                    <p>Visualizza lo storico delle lezioni prenotate e completate</p>
                    <a href="storico_lezioni.php" class="btn-feature">Storico</a>
                </div>
                <div class="feature-card">
                    <h3>Cerca Insegnante</h3>
                    <p>Trova un insegnante tramite email o link invito</p>
                    <a href="cerca_insegnante.php" class="btn-feature">Cerca</a>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>
    
    <!-- Add reference to the JavaScript file with cache busting -->
    <script src="../js/user_account.js?v=<?php echo time(); ?>"></script>
</body>
</html>
