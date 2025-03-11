<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore'){
    header('Location: ../index.php');
    exit;
}

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
    <title>Gestione Studenti</title>
    <style>
        .students-container {
            margin-top: 30px;
        }
        .student-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        .student-name {
            font-size: 1.2rem;
            color: #2da0a8;
            margin-bottom: 5px;
        }
        .student-email {
            color: #666;
            margin-bottom: 15px;
        }
        .stats {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            background-color: #f5f7fa;
            padding: 10px;
            border-radius: 5px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2da0a8;
            display: block;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        .no-students {
            text-align: center;
            padding: 50px;
            color: #666;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .search-container {
            margin-bottom: 30px;
            max-width: 600px;
            margin: 0 auto 30px;
        }
        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        .student-details-btn {
            background-color: #2da0a8;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            margin-top: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .student-details-btn:hover {
            background-color: #238e95;
        }
    </style>
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
                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <h1>Gestione Studenti</h1>
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

    <script>
        // Funzionalità di ricerca
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const studentCards = document.querySelectorAll('.student-card');
            
            studentCards.forEach(card => {
                const name = card.querySelector('.student-name').textContent.toLowerCase();
                const email = card.querySelector('.student-email').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
