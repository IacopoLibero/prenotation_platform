<?php
session_start();
require_once '../connessione.php';

// Controllo se l'utente è loggato ed è un professore
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Recupero dei dati
$teacher_email = $_SESSION['email'];
$calendar_link = $_POST['calendar_link'];
$weekend = isset($_POST['weekend']) ? intval($_POST['weekend']) : 0;
$mattina = isset($_POST['mattina']) ? intval($_POST['mattina']) : 1;
$pomeriggio = isset($_POST['pomeriggio']) ? intval($_POST['pomeriggio']) : 1;
$ora_inizio_mattina = $_POST['ora_inizio_mattina'] ?? '08:00';
$ora_fine_mattina = $_POST['ora_fine_mattina'] ?? '13:00';
$ora_inizio_pomeriggio = $_POST['ora_inizio_pomeriggio'] ?? '14:00';
$ora_fine_pomeriggio = $_POST['ora_fine_pomeriggio'] ?? '19:00';

// Aggiungiamo i nuovi parametri
$ore_prima_evento = isset($_POST['ore_prima_evento']) ? floatval($_POST['ore_prima_evento']) : 0;
$ore_dopo_evento = isset($_POST['ore_dopo_evento']) ? floatval($_POST['ore_dopo_evento']) : 0;

// Validazione
if (empty($calendar_link)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Il link del calendario è obbligatorio']);
    exit;
}

// Verifica che il link sia un URL valido
if (!filter_var($calendar_link, FILTER_VALIDATE_URL)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Il link del calendario non è valido']);
    exit;
}

// Verifica che il link sia accessibile prima di salvarlo
$accessible = false;

// Prova con file_get_contents
$ctx = stream_context_create(['http' => ['timeout' => 5]]);
$test_content = @file_get_contents($calendar_link, false, $ctx);

// Se fallisce, prova con cURL
if ($test_content === false && function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $calendar_link);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $test_content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($test_content !== false && $http_code >= 200 && $http_code < 300) {
        $accessible = true;
    }
} else if ($test_content !== false) {
    $accessible = true;
}

// Verifica che il contenuto sembri essere un calendario iCal
if ($accessible && strpos($test_content, 'BEGIN:VCALENDAR') === false) {
    $accessible = false;
}

if (!$accessible) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Il calendario non è accessibile o non è nel formato iCal corretto. Verifica che il link sia pubblico e corretto.'
    ]);
    exit;
}

// Estrazione dell'ID del calendario dal link
$calendar_id = null;
if (preg_match('/\/([a-zA-Z0-9%@._-]+)\/public\/basic\.ics$/', $calendar_link, $matches)) {
    $calendar_id = urldecode($matches[1]);
} else {
    // Formato più permissivo per supportare più varianti di URL iCal
    $parts = parse_url($calendar_link);
    $path_parts = explode('/', $parts['path']);
    foreach ($path_parts as $part) {
        if (strpos($part, '@') !== false || (strlen($part) > 10 && preg_match('/[a-z0-9]/i', $part))) {
            $calendar_id = $part;
            break;
        }
    }
    
    if (!$calendar_id) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Formato del link calendario non valido. Assicurati di utilizzare il link iCal dal tuo Google Calendar.'
        ]);
        exit;
    }
}

// Salvataggio del link del calendario
$update_query = "UPDATE Professori SET google_calendar_link = ?, google_calendar_id = ? WHERE email = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("sss", $calendar_link, $calendar_id, $teacher_email);
$result = $stmt->execute();

if (!$result) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore nel salvare il link del calendario: ' . $stmt->error]);
    exit;
}

// Salvataggio delle preferenze di disponibilità
$check_query = "SELECT id FROM Preferenze_Disponibilita WHERE teacher_email = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("s", $teacher_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Aggiorna le preferenze esistenti
    $update_query = "UPDATE Preferenze_Disponibilita 
                     SET weekend = ?, mattina = ?, pomeriggio = ?, 
                         ora_inizio_mattina = ?, ora_fine_mattina = ?,
                         ora_inizio_pomeriggio = ?, ora_fine_pomeriggio = ?,
                         ore_prima_evento = ?, ore_dopo_evento = ?
                     WHERE teacher_email = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("iiissssdds", $weekend, $mattina, $pomeriggio, 
                     $ora_inizio_mattina, $ora_fine_mattina, 
                     $ora_inizio_pomeriggio, $ora_fine_pomeriggio,
                     $ore_prima_evento, $ore_dopo_evento,
                     $teacher_email);
} else {
    // Inserisce nuove preferenze
    $insert_query = "INSERT INTO Preferenze_Disponibilita 
                    (teacher_email, weekend, mattina, pomeriggio, 
                     ora_inizio_mattina, ora_fine_mattina, 
                     ora_inizio_pomeriggio, ora_fine_pomeriggio,
                     ore_prima_evento, ore_dopo_evento)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("siisssssdd", $teacher_email, $weekend, $mattina, $pomeriggio, 
                     $ora_inizio_mattina, $ora_fine_mattina, 
                     $ora_inizio_pomeriggio, $ora_fine_pomeriggio,
                     $ore_prima_evento, $ore_dopo_evento);
}

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Preferenze salvate con successo']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore nel salvare le preferenze: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
