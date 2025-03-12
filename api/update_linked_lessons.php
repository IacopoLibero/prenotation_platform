<?php
session_start();
require_once '../connessione.php';

// Questa API può essere chiamata da un cron job o da un webhook quando un evento viene aggiornato
// per aggiornare le lezioni nel sistema

// Controllo se l'utente è loggato ed è un professore
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$teacher_email = $_SESSION['email'];

// Recupera il link al calendario
$query = "SELECT google_calendar_link FROM Professori WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $teacher_email);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row || empty($row['google_calendar_link'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Nessun calendario Google collegato']);
    exit;
}

// Aggiorna le lezioni chiamando l'endpoint di sincronizzazione
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . '/api/sync_google_calendar.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    $data = json_decode($response, true);
    if ($data['success']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Lezioni aggiornate con successo']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento: ' . $data['message']]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore durante la richiesta di sincronizzazione']);
}
?>
