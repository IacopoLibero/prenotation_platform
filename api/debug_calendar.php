<?php
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

// Ottieni timeframe di debug (default: prossimi 28 giorni - 4 settimane)
$days = isset($_GET['days']) ? (int)$_GET['days'] : 28;
$today = new DateTime('now', new DateTimeZone('Europe/Rome'));
$end_date = clone $today;
$end_date->modify("+$days days");

// Scarica e analizza il calendario
$calendar_url = $row['google_calendar_link'];
$ical_content = @file_get_contents($calendar_url);
if ($ical_content === false) {
    // Prova con cURL se file_get_contents fallisce
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $calendar_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $ical_content = curl_exec($ch);
    curl_close($ch);
}

// Analizza gli eventi
$events = [];
$all_events = [];
preg_match_all('/BEGIN:VEVENT.*?END:VEVENT/s', $ical_content, $matches);

// Per ciascun evento
foreach ($matches[0] as $event_text) {
    $event = [];
    
    // Estrai il titolo
    if (preg_match('/SUMMARY:(.*?)(?:\r\n|\n)/s', $event_text, $summary)) {
        $event['titolo'] = trim($summary[1]);
    } else {
        $event['titolo'] = 'Evento senza titolo';
    }
    
    // Estrai data inizio
    if (preg_match('/DTSTART(?:;VALUE=DATE)?.*?:(.*?)(?:\r\n|\n)/s', $event_text, $dtstart)) {
        $start_raw = $dtstart[1];
        $event['inizio_raw'] = $start_raw;
        
        // Format date based on whether it's all-day or timed
        if (strlen($start_raw) == 8) {
            // All day
            $event['inizio'] = substr($start_raw, 0, 4) . '-' . 
                             substr($start_raw, 4, 2) . '-' . 
                             substr($start_raw, 6, 2) . ' 00:00:00';
            $event['tutto_giorno'] = true;
        } else {
            // Event with time
            $year = substr($start_raw, 0, 4);
            $month = substr($start_raw, 4, 2);
            $day = substr($start_raw, 6, 2);
            $hour = substr($start_raw, 9, 2);
            $minute = substr($start_raw, 11, 2);
            
            // Only take seconds if they exist (sometimes ical entries don't have seconds)
            $second = (strlen($start_raw) >= 15) ? substr($start_raw, 13, 2) : '00';
            
            if (substr($start_raw, -1) === 'Z') {
                // Convert from UTC to local
                $dt = new DateTime("{$year}-{$month}-{$day} {$hour}:{$minute}:{$second}", new DateTimeZone('UTC'));
                $dt->setTimezone(new DateTimeZone('Europe/Rome'));
                $event['inizio'] = $dt->format('Y-m-d H:i:s');
            } else {
                $event['inizio'] = "{$year}-{$month}-{$day} {$hour}:{$minute}:{$second}";
            }
            $event['tutto_giorno'] = false;
        }
    }
    
    // Estrai data fine
    if (preg_match('/DTEND(?:;VALUE=DATE)?.*?:(.*?)(?:\r\n|\n)/s', $event_text, $dtend)) {
        $end_raw = $dtend[1];
        $event['fine_raw'] = $end_raw;
        
        // Format date based on whether it's all-day or timed
        if (strlen($end_raw) == 8) {
            // All day
            $event['fine'] = substr($end_raw, 0, 4) . '-' . 
                          substr($end_raw, 4, 2) . '-' . 
                          substr($end_raw, 6, 2) . ' 23:59:59';
        } else {
            // Event with time
            $year = substr($end_raw, 0, 4);
            $month = substr($end_raw, 4, 2);
            $day = substr($end_raw, 6, 2);
            $hour = substr($end_raw, 9, 2);
            $minute = substr($end_raw, 11, 2);
            $second = substr($end_raw, 13, 2);
            
            if (substr($end_raw, -1) === 'Z') {
                // Convert from UTC to local
                $dt = new DateTime("{$year}-{$month}-{$day} {$hour}:{$minute}:{$second}", new DateTimeZone('UTC'));
                $dt->setTimezone(new DateTimeZone('Europe/Rome'));
                $event['fine'] = $dt->format('Y-m-d H:i:s');
            } else {
                $event['fine'] = "{$year}-{$month}-{$day} {$hour}:{$minute}:{$second}";
            }
        }
    }
    
    // Aggiungi all'elenco completo
    $all_events[] = $event;
    
    // Controlla se l'evento è nel periodo di interesse
    if (isset($event['inizio']) && isset($event['fine'])) {
        $event_start = new DateTime($event['inizio']);
        $event_end = new DateTime($event['fine']);
        
        // Includi eventi che iniziano o terminano nel periodo di interesse
        if (($event_start >= $today && $event_start <= $end_date) || 
            ($event_end >= $today && $event_end <= $end_date) ||
            ($event_start <= $today && $event_end >= $end_date)) {
            $events[] = $event;
        }
    }
}

// Ordina gli eventi per data di inizio
usort($events, function($a, $b) {
    $date_a = new DateTime($a['inizio']);
    $date_b = new DateTime($b['inizio']);
    return $date_a <=> $date_b;
});

// Ordina anche l'elenco completo
usort($all_events, function($a, $b) {
    if (!isset($a['inizio']) || !isset($b['inizio'])) {
        return 0;
    }
    $date_a = new DateTime($a['inizio']);
    $date_b = new DateTime($b['inizio']);
    return $date_a <=> $date_b;
});

// Recupera preferenze disponibilità
$pref_query = "SELECT * FROM Preferenze_Disponibilita WHERE teacher_email = ?";
$stmt = $conn->prepare($pref_query);
$stmt->bind_param("s", $teacher_email);
$stmt->execute();
$preferences = $stmt->get_result()->fetch_assoc() ?: [
    'weekend' => false,
    'mattina' => true,
    'pomeriggio' => true,
    'ora_inizio_mattina' => '08:00:00',
    'ora_fine_mattina' => '13:00:00',
    'ora_inizio_pomeriggio' => '14:00:00',
    'ora_fine_pomeriggio' => '19:00:00'
];

// Restituisci i risultati
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'calendar_url' => $calendar_url,
    'today' => $today->format('Y-m-d H:i:s'),
    'end_date' => $end_date->format('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get(),
    'preferences' => $preferences,
    'events_count' => [
        'total' => count($all_events),
        'in_range' => count($events)
    ],
    'events' => $events
]);
?>
