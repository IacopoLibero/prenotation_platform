<?php
    session_start();
    if(!isset($_SESSION['user'])){
        header('Location: ../index.php');
    }
    $isTeacher = ($_SESSION['tipo'] === 'professore');
    
    require_once '../connessione.php';
    $email = $_SESSION['email'];
    
    if ($isTeacher) {
        // Ottieni statistiche per il professore
        $query = "SELECT 
                 COUNT(*) as total_lessons,
                 IFNULL(SUM(CASE WHEN stato = 'prenotata' THEN 1 ELSE 0 END), 0) as upcoming_lessons,
                 COUNT(DISTINCT student_email) as student_count,
                 IFNULL(SUM(CASE WHEN stato = 'completata' THEN TIMESTAMPDIFF(MINUTE, start_time, end_time) ELSE 0 END), 0) as completed_minutes
                 FROM Lezioni 
                 WHERE teacher_email = ?";
    } else {
        // Ottieni statistiche per lo studente
        $query = "SELECT 
                 COUNT(*) as total_lessons,
                 IFNULL(SUM(CASE WHEN stato = 'prenotata' THEN 1 ELSE 0 END), 0) as upcoming_lessons,
                 COUNT(DISTINCT teacher_email) as teacher_count,
                 IFNULL(SUM(CASE WHEN stato = 'completata' THEN TIMESTAMPDIFF(MINUTE, start_time, end_time) ELSE 0 END), 0) as completed_minutes
                 FROM Lezioni 
                 WHERE student_email = ?";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    
    // Calcola le ore di lezione completate
    $completed_hours = floor($stats['completed_minutes'] / 60);
    $completed_minutes = $stats['completed_minutes'] % 60;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Add cache-busting parameter to the CSS link -->
    <link rel="stylesheet" href="../styles/home.css?v=<?php echo time(); ?>">
    <!-- Add cache-control meta tags -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Home</title>
    <script type="text/javascript">
      var _iub = _iub || [];
      _iub.csConfiguration = {"askConsentAtCookiePolicyUpdate":true,"countryDetection":true,"enableFadp":true,"enableLgpd":true,"enableTcf":true,"enableUspr":true,"floatingPreferencesButtonDisplay":"anchored-bottom-right","googleAdditionalConsentMode":true,"lgpdAppliesGlobally":false,"perPurposeConsent":true,"preferenceCookie":{"expireAfter":180},"siteId":3962138,"storage":{"useSiteId":true},"tcfPurposes":{"2":"consent_only","7":"consent_only","8":"consent_only","9":"consent_only","10":"consent_only","11":"consent_only"},"usPreferencesWidgetDisplay":"bottom-left","whitelabel":false,"cookiePolicyId":56978183,"lang":"it","floatingPreferencesButtonCaption":true,"banner":{"acceptButtonCaptionColor":"#FFFFFF","acceptButtonColor":"#0073CE","acceptButtonDisplay":true,"backgroundColor":"#FFFFFF","closeButtonDisplay":false,"customizeButtonCaptionColor":"#4D4D4D","customizeButtonColor":"#DADADA","customizeButtonDisplay":true,"explicitWithdrawal":true,"listPurposes":true,"ownerName":"superipetizioni.altervista.org","position":"float-bottom-center","rejectButtonCaptionColor":"#FFFFFF","rejectButtonColor":"#0073CE","rejectButtonDisplay":true,"showPurposesToggles":true,"showTotalNumberOfProviders":true,"textColor":"#000000"}};
      </script>
      <script type="text/javascript" src="https://cs.iubenda.com/autoblocking/3962138.js"></script>
      <script type="text/javascript" src="//cdn.iubenda.com/cs/tcf/stub-v2.js"></script>
      <script type="text/javascript" src="//cdn.iubenda.com/cs/tcf/safe-tcf-v2.js"></script>
      <script type="text/javascript" src="//cdn.iubenda.com/cs/gpp/stub.js"></script>
      <script type="text/javascript" src="//cdn.iubenda.com/cs/iubenda_cs.js" charset="UTF-8" async>
    </script>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Home</div>
            <ul>
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
        <section class="welcome">
            <h1>Benvenuto, <?php echo $_SESSION['user']; ?>!</h1>
            <p>Gestisci le tue lezioni in modo semplice e veloce.</p>
            <p class="user-type">Sei connesso come: <span class="badge <?php echo $isTeacher ? 'teacher' : 'student'; ?>"><?php echo $isTeacher ? 'Professore' : 'Studente'; ?></span></p>
        </section>
        
        <!-- Ad container with proper styling -->
        <div class="ad-container" style="text-align: center; margin: 20px auto; max-width: 300px; min-height: 250px; overflow: hidden;">
            <script>
                !function(d,l,e,s,c){
                    e=d.createElement("script");
                    e.src="//ad.altervista.org/js.ad/size=300X250/?ref="+encodeURIComponent(l.hostname+l.pathname)+"&r="+Date.now();
                    s=d.scripts;
                    c=d.currentScript||s[s.length-1];
                    c.parentNode.insertBefore(e,c)
                }(document,location)
            </script>
        </div>
        
        <section class="dashboard">
            <h2>Dashboard</h2>
            
            <?php if($isTeacher): ?>
            <!-- Dashboard per professori -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Lezioni Programmate</h3>
                    <div class="count"><?= $stats['upcoming_lessons'] ?></div>
                    <a href="gestione_lezioni.php">Visualizza</a>
                </div>
                <div class="dashboard-card">
                    <h3>Ore Insegnate</h3>
                    <div class="count"><?= $completed_hours ?>h <?= $completed_minutes ?>m</div>
                    <a href="report.php">Dettagli</a>
                </div>
                <div class="dashboard-card">
                    <h3>Studenti Totali</h3>
                    <div class="count"><?= $stats['student_count'] ?></div>
                    <a href="gestione_studenti.php">Visualizza</a>
                </div>
            </div>
            <?php else: ?>
            <!-- Dashboard per studenti -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Lezioni Prenotate</h3>
                    <div class="count"><?= $stats['upcoming_lessons'] ?></div>
                    <a href="storico_lezioni.php">Visualizza</a>
                </div>
                <div class="dashboard-card">
                    <h3>Ore di Lezione</h3>
                    <div class="count"><?= $completed_hours ?>h <?= $completed_minutes ?>m</div>
                    <a href="storico_lezioni.php">Dettagli</a>
                </div>
                <div class="dashboard-card">
                    <h3>Insegnanti</h3>
                    <div class="count"><?= $stats['teacher_count'] ?></div>
                    <a href="cerca_insegnante.php">Cerca</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Sezione di aiuto rapido -->
            <div class="quick-help">
                <h3>Aiuto Rapido</h3>
                <div class="help-content">
                    <?php if($isTeacher): ?>
                        <p><strong>Come iniziare:</strong></p>
                        <ol>
                            <li>Imposta i tuoi orari di disponibilità nella sezione <a href="disponibilita.php">Disponibilità</a></li>
                            <li>Crea nuove lezioni nella sezione <a href="gestione_lezioni.php">Gestisci Lezioni</a></li>
                            <li>Monitora le prenotazioni ricevute nella sezione <a href="prenotazioni.php">Prenotazioni</a></li>
                            <li>Visualizza le statistiche complete nella sezione <a href="report.php">Report</a></li>
                        </ol>
                    <?php else: ?>
                        <p><strong>Come iniziare:</strong></p>
                        <ol>
                            <li>Cerca un insegnante nella sezione <a href="cerca_insegnante.php">Cerca Insegnante</a></li>
                            <li>Visualizza gli orari di disponibilità degli insegnanti nella sezione <a href="orari_insegnanti.php">Orari</a></li>
                            <li>Consulta lo storico delle tue lezioni nella sezione <a href="storico_lezioni.php">Storico</a></li>
                        </ol>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>
    
    <!-- Add JavaScript fix -->
    <script src="../js/fix-quick-help.js"></script>
    
    <!-- Ad container with proper styling -->
    <div class="ad-container" style="text-align: center; margin: 20px auto; max-width: 300px; min-height: 250px; overflow: hidden;">
        <script>
            !function(d,l,e,s,c){
                e=d.createElement("script");
                e.src="//ad.altervista.org/js.ad/size=300X250/?ref="+encodeURIComponent(l.hostname+l.pathname)+"&r="+Date.now();
                s=d.scripts;
                c=d.currentScript||s[s.length-1];
                c.parentNode.insertBefore(e,c)
            }(document,location)
        </script>
    </div>
</body>
</html>