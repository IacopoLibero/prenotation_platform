<?php
// Script per testare l'elaborazione degli eventi di Google Calendar
session_start();
require_once '../connessione.php';

// Controllo se l'utente è loggato ed è un professore
if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$teacher_email = $_SESSION['email'];

// Recupero informazioni del calendario
$query = "SELECT google_calendar_link FROM Professori WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $teacher_email);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row || empty($row['google_calendar_link'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Nessun calendario Google configurato']);
    exit;
}

// Scarica il contenuto del calendario
$ical_content = @file_get_contents($row['google_calendar_link']);
if ($ical_content === false) {
    // Prova con cURL se file_get_contents fallisce
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $row['google_calendar_link']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $ical_content = curl_exec($ch);
    curl_close($ch);
}

if (empty($ical_content)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Impossibile scaricare il calendario']);
    exit;
}

// Analisi eventi
$events = [];
preg_match_all('/BEGIN:VEVENT.*?END:VEVENT/s', $ical_content, $matches);

// Informazioni sugli eventi trovati
$processed_events = [];
foreach ($matches[0] as $event_text) {
    $event = [];
    
    // Estrai summary (titolo)
    if (preg_match('/SUMMARY:(.*?)(?:\r\n|\r|\n)/s', $event_text, $summary)) {
        $event['summary'] = trim($summary[1]);
    }
    
    // Estrai data di inizio
    if (preg_match('/DTSTART.*?:(.*?)(?:\r\n|\r|\n)/s', $event_text, $dtstart)) {
        $date_str = $dtstart[1];
        $event['start_raw'] = $date_str;
        
        if (strpos($date_str, 'T') !== false) {
            // Format: 20230405T100000Z
            if (preg_match('/(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})Z?/', $date_str, $matches)) {
                $datetime = $matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' ' . 
                           $matches[4] . ':' . $matches[5] . ':' . $matches[6];
                if (substr($date_str, -1) === 'Z') {
                    // Convert UTC to local time
                    $dt = new DateTime($datetime, new DateTimeZone('UTC'));
                    $dt->setTimezone(new DateTimeZone('Europe/Rome'));
                    $event['start'] = $dt->format('Y-m-d H:i:s');
                } else {
                    $event['start'] = $datetime;
                }
            }
        } else {
            // Format: 20230405 (all-day event)
            if (preg_match('/(\d{4})(\d{2})(\d{2})/', $date_str, $matches)) {
                $event['start'] = $matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' 00:00:00';
            }
        }
    }
    
    // Estrai data di fine
    if (preg_match('/DTEND.*?:(.*?)(?:\r\n|\r|\n)/s', $event_text, $dtend)) {
        $date_str = $dtend[1];
        $event['end_raw'] = $date_str;
        
        if (strpos($date_str, 'T') !== false) {
            // Format: 20230405T110000Z
            if (preg_match('/(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})Z?/', $date_str, $matches)) {
                $datetime = $matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' ' . 
                           $matches[4] . ':' . $matches[5] . ':' . $matches[6];
                if (substr($date_str, -1) === 'Z') {
                    // Convert UTC to local time
                    $dt = new DateTime($datetime, new DateTimeZone('UTC'));
                    $dt->setTimezone(new DateTimeZone('Europe/Rome'));
                    $event['end'] = $dt->format('Y-m-d H:i:s');
                } else {
                    $event['end'] = $datetime;
                }
            }
        } else {
            // Format: 20230405 (all-day event)
            if (preg_match('/(\d{4})(\d{2})(\d{2})/', $date_str, $matches)) {
                $event['end'] = $matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' 23:59:59';
            }
        }
    }
    
    $processed_events[] = $event;
}

// Restituisci gli eventi analizzati
header('Content-Type: application/json');
echo json_encode([
    'success' => true, 
    'calendar_link' => $row['google_calendar_link'],
    'events_count' => count($processed_events),
    'events' => $processed_events,
    'today' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get()
]);
?>
