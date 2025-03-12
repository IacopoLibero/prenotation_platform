<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore'){
    header('Location: ../index.php');
    exit;
}

require_once '../connessione.php';

// Ottieni la disponibilità attuale del professore
$email = $_SESSION['email'];
$query = "SELECT id, giorno_settimana, ora_inizio, ora_fine FROM Disponibilita 
          WHERE teacher_email = ? 
          ORDER BY FIELD(giorno_settimana, 'lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica'), 
          ora_inizio";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Traduzione giorni della settimana
$giorni = [
    'lunedi' => 'Lunedì',
    'martedi' => 'Martedì',
    'mercoledi' => 'Mercoledì',
    'giovedi' => 'Giovedì',
    'venerdi' => 'Venerdì',
    'sabato' => 'Sabato',
    'domenica' => 'Domenica'
];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/home.css">
    <title>Imposta Disponibilità</title>
    <style>
        .availability-section {
            margin: 30px 0;
        }
        .form-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        .form-title {
            color: #2da0a8;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group select, .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        .btn-add {
            background-color: #2da0a8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            margin-top: 10px;
        }
        .btn-add:hover {
            background-color: #238e95;
        }
        .availability-list {
            margin-top: 30px;
        }
        .availability-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .availability-info {
            flex-grow: 1;
        }
        .day-name {
            font-weight: 600;
            color: #2da0a8;
        }
        .time-range {
            margin-left: 10px;
            color: #666;
        }
        .btn-delete-slot {
            background-color: #ef5350;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .btn-delete-slot:hover {
            background-color: #e53935;
        }
        .no-availability {
            text-align: center;
            padding: 20px;
            color: #666;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .google-calendar-btn {
            display: block;
            margin: 20px 0;
            padding: 10px 15px;
            background-color: #4285F4;
            color: white;
            text-align: center;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
        }
        .google-calendar-btn:hover {
            background-color: #3367D6;
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
                <li><a href="google_calendar_setup.php">Google Calendar</a></li>
                <li><a href="../login/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <h1>Imposta Disponibilità</h1>
            <p>Configura gli orari in cui sei disponibile per tenere lezioni</p>
            
            <a href="google_calendar_setup.php" class="google-calendar-btn">
                Sincronizza con Google Calendar
            </a>
            
            <div class="availability-section">
                <div class="form-container">
                    <h2 class="form-title">Aggiungi Nuova Disponibilità</h2>
                    <form id="availabilityForm">
                        <div class="form-group">
                            <label for="giorno">Giorno della settimana:</label>
                            <select id="giorno" name="giorno" required>
                                <option value="lunedi">Lunedì</option>
                                <option value="martedi">Martedì</option>
                                <option value="mercoledi">Mercoledì</option>
                                <option value="giovedi">Giovedì</option>
                                <option value="venerdi">Venerdì</option>
                                <option value="sabato">Sabato</option>
                                <option value="domenica">Domenica</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="ora_inizio">Ora inizio:</label>
                            <input type="time" id="ora_inizio" name="ora_inizio" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="ora_fine">Ora fine:</label>
                            <input type="time" id="ora_fine" name="ora_fine" required>
                        </div>
                        
                        <button type="submit" class="btn-add">Aggiungi Disponibilità</button>
                    </form>
                </div>
                
                <div class="availability-list">
                    <h2 class="form-title">Disponibilità Attuali</h2>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <div class="availability-card">
                                <div class="availability-info">
                                    <span class="day-name"><?= $giorni[$row['giorno_settimana']] ?></span>
                                    <span class="time-range"><?= $row['ora_inizio'] ?> - <?= $row['ora_fine'] ?></span>
                                </div>
                                <button class="btn-delete-slot" onclick="deleteAvailability(<?= $row['id'] ?>)">Elimina</button>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-availability">
                            <p>Non hai ancora impostato nessuna disponibilità.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2023 Programma Lezioni. Tutti i diritti riservati.</p>
    </footer>
    
    <script>
        document.getElementById('availabilityForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const giorno = document.getElementById('giorno').value;
            const oraInizio = document.getElementById('ora_inizio').value;
            const oraFine = document.getElementById('ora_fine').value;
            
            if (!giorno || !oraInizio || !oraFine) {
                alert('Tutti i campi sono obbligatori');
                return;
            }
            
            if (oraFine <= oraInizio) {
                alert('L\'ora di fine deve essere successiva all\'ora di inizio');
                return;
            }
            
            const formData = new FormData();
            formData.append('giorno', giorno);
            formData.append('ora_inizio', oraInizio);
            formData.append('ora_fine', oraFine);
            
            fetch('../api/set_availability.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Si è verificato un errore durante l\'impostazione della disponibilità.');
            });
        });
        
        function deleteAvailability(id) {
            if (confirm('Sei sicuro di voler eliminare questa disponibilità?')) {
                const formData = new FormData();
                formData.append('availability_id', id);
                
                fetch('../api/delete_availability.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Si è verificato un errore durante l\'eliminazione della disponibilità.');
                });
            }
        }
    </script>
</body>
</html>
