<?php
// Questo script può essere eseguito come cron job per sincronizzare tutti i calendari
require_once '../connessione.php';

// Recupera tutti i professori che hanno configurato un calendario Google
$query = "SELECT email FROM Professori WHERE google_calendar_link IS NOT NULL";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

$updated = 0;
$failed = 0;

while ($row = $result->fetch_assoc()) {
    $teacher_email = $row['email'];
    
    // Simula una sessione per il professore
    session_start();
    $_SESSION['user'] = true;
    $_SESSION['tipo'] = 'professore';
    $_SESSION['email'] = $teacher_email;
    
    // Chiama l'API di sincronizzazione
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/sync_google_calendar.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $data = json_decode($response, true);
        if ($data['success']) {
            $updated++;
        } else {
            $failed++;
            error_log("Errore aggiornamento calendario per $teacher_email: " . $data['message']);
        }
    } else {
        $failed++;
        error_log("Errore HTTP durante la sincronizzazione del calendario per $teacher_email");
    }
    
    session_destroy();
}

echo "Calendari aggiornati: $updated, Falliti: $failed" . PHP_EOL;
?>
