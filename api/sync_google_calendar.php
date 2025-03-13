<?php
// Strict error handling approach
ini_set('display_errors', 0); // Don't display errors directly
error_reporting(E_ALL); // But still report all types of errors

// Start output buffering to prevent any output before headers
ob_start();

// For catching fatal errors that would otherwise produce blank page
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Clean any output that might have been sent
        ob_end_clean();
        
        // Send proper JSON error response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Si è verificato un errore durante la sincronizzazione',
            'debug_info' => $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ]);
    }
});

try {
    session_start();
    require_once '../connessione.php';
    
    // Controllo se l'utente è loggato ed è un professore
    if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore') {
        throw new Exception('Non autorizzato');
    }
    
    $teacher_email = $_SESSION['email'];
    
    // Recupero informazioni del calendario e preferenze
    $query = "SELECT p.google_calendar_link, pd.* 
              FROM Professori p
              LEFT JOIN Preferenze_Disponibilita pd ON p.email = pd.teacher_email
              WHERE p.email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $teacher_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if (!$data || empty($data['google_calendar_link'])) {
        throw new Exception('Nessun calendario Google configurato');
    }
    
    // Verifica l'URL del calendario prima di procedere
    $calendar_url = $data['google_calendar_link'];
    if (!filter_var($calendar_url, FILTER_VALIDATE_URL)) {
        throw new Exception('URL del calendario non valido');
    }
    
    // Scarica il contenuto del calendario
    $ical_content = @file_get_contents($calendar_url);
    
    // Se file_get_contents fallisce, prova con cURL
    if ($ical_content === false) {
        if (!function_exists('curl_init')) {
            throw new Exception('Impossibile accedere al calendario. La funzione cURL non è disponibile sul server.');
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $calendar_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $ical_content = curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($ical_content === false || empty($ical_content)) {
            throw new Exception('Impossibile accedere al calendario. Verifica che il link sia corretto e pubblico. Errore: ' . $curl_error);
        }
    }
    
    // Verifica che il contenuto sia effettivamente un file iCal
    if (empty($ical_content) || strpos($ical_content, 'BEGIN:VCALENDAR') === false) {
        throw new Exception('Il contenuto scaricato non sembra essere un calendario valido.');
    }
    
    // Analizza gli eventi iCal
    $events = parse_ical_events($ical_content);
    
    // Ottieni le date delle prossime 2 settimane
    $dates = get_next_weeks_dates(2);
    
    // Genera disponibilità in base alle date e agli eventi
    $availability = generate_availability($dates, $events, $data);
    
    // Prima elimina le vecchie disponibilità non ancora prenotate
    // Modifico la query per evitare l'errore di colonna sconosciuta
    $delete_query = "DELETE FROM Disponibilita WHERE teacher_email = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("s", $teacher_email);
    $stmt->execute();
    
    // Elimina anche le vecchie lezioni che sono ancora disponibili
    $delete_lessons_query = "DELETE FROM Lezioni WHERE teacher_email = ? AND stato = 'disponibile'";
    $stmt = $conn->prepare($delete_lessons_query);
    $stmt->bind_param("s", $teacher_email);
    $stmt->execute();
    
    // Salva le nuove disponibilità nel database
    $result = save_availability($conn, $teacher_email, $availability);
    
    if (!$result) {
        throw new Exception('Errore durante il salvataggio delle disponibilità');
    }
    
    // Success response - ensure output buffer is clean before sending
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Disponibilità generate con successo']);

} catch (Exception $e) {
    // Error response - ensure output buffer is clean before sending
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close database connection if it exists
if (isset($conn) && $conn) {
    $conn->close();
}

// Funzione per analizzare gli eventi iCal
function parse_ical_events($ical_content) {
    $events = [];
    
    // Dividiamo il file iCal in eventi
    preg_match_all('/BEGIN:VEVENT.*?END:VEVENT/s', $ical_content, $matches);
    
    foreach ($matches[0] as $index => $event_text) {
        $event = [];
        
        // Estrai sommario/titolo dell'evento 
        if (preg_match('/SUMMARY:(.*?)(?:\r\n|\n)/s', $event_text, $summary)) {
            $event['summary'] = trim($summary[1]);
        }
        
        // Estrai data di inizio
        if (preg_match('/DTSTART(?:;VALUE=DATE)?.*?:(.*?)(?:\r\n|\n)/s', $event_text, $dtstart)) {
            $event['start'] = parse_ical_date($dtstart[1]);
            $event['start_raw'] = $dtstart[1]; // Salva anche il valore originale per debug
        }
        
        // Estrai data di fine
        if (preg_match('/DTEND(?:;VALUE=DATE)?.*?:(.*?)(?:\r\n|\n)/s', $event_text, $dtend)) {
            $event['end'] = parse_ical_date($dtend[1]);
            $event['end_raw'] = $dtend[1]; // Salva anche il valore originale per debug
        }
        
        // Solo se abbiamo sia inizio che fine validi
        if (!empty($event['start']) && !empty($event['end'])) {
            // Debug log per verificare la corretta interpretazione degli eventi
            error_log("Evento #$index importato: " . 
                      ($event['summary'] ?? 'Senza titolo') . " - " . 
                      $event['start']->format('Y-m-d H:i:s') . " - " . 
                      $event['end']->format('Y-m-d H:i:s'));
            
            $events[] = $event;
        }
    }
    
    error_log("Totale eventi importati: " . count($events));
    return $events;
}

// Funzione per convertire il formato data iCal in DateTime
function parse_ical_date($date_string) {
    // Gestione eventi per tutto il giorno (formato solo data senza T)
    if (strlen($date_string) == 8 && strpos($date_string, 'T') === false) {
        // Formato: 20231215 (all-day event)
        if (preg_match('/(\d{4})(\d{2})(\d{2})/', $date_string, $matches)) {
            $date = new DateTime('now', new DateTimeZone('Europe/Rome'));
            $date->setDate($matches[1], $matches[2], $matches[3]);
            
            // Per gli eventi di tutto il giorno, imposta ore appropriate
            if (substr($date_string, -1) !== 'Z') {
                // Data di inizio → mezzanotte
                $date->setTime(0, 0, 0);
            }
            
            return $date;
        }
    } 
    // Gestione eventi con orario specifico
    else if (strpos($date_string, 'T') !== false) {
        // Formato: 20231215T130000Z
        $pattern = '/(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})Z?/';
        if (preg_match($pattern, $date_string, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
            $hour = $matches[4];
            $minute = $matches[5];
            $second = $matches[6];
            
            // Se ha Z alla fine, è in UTC e dobbiamo convertirlo
            if (substr($date_string, -1) === 'Z') {
                $date = new DateTime('now', new DateTimeZone('UTC'));
                $date->setDate($year, $month, $day);
                $date->setTime($hour, $minute, $second);
                $date->setTimezone(new DateTimeZone('Europe/Rome'));
                
                // Debug della conversione di timezone
                error_log("Conversione UTC→Europe/Rome: $date_string → " . $date->format('Y-m-d H:i:s'));
            } 
            // Altrimenti è già nell'ora locale
            else {
                $date = new DateTime('now', new DateTimeZone('Europe/Rome'));
                $date->setDate($year, $month, $day);
                $date->setTime($hour, $minute, $second);
            }
            
            return $date;
        }
    }
    
    error_log("Formato data non riconosciuto: $date_string");
    return null;
}

// Funzione per generare le date delle prossime settimane
function get_next_weeks_dates($weeks) {
    $dates = [];
    $now = new DateTime();
    
    for ($i = 0; $i < $weeks * 7; $i++) {
        $date = clone $now;
        $date->modify("+$i days");
        $dates[] = $date;
    }
    
    return $dates;
}

// Funzione per generare disponibilità in base alle date e agli eventi
function generate_availability($dates, $events, $preferences) {
    $availability = [];
    
    foreach ($dates as $date) {
        // Salta weekend se non abilitato
        $day_of_week = $date->format('N');
        if (($day_of_week >= 6) && !$preferences['weekend']) {
            continue;
        }
        
        // Converti il numero del giorno nella stringa corrispondente
        $day_name = '';
        switch ($date->format('N')) {
            case 1: $day_name = 'lunedi'; break;
            case 2: $day_name = 'martedi'; break;
            case 3: $day_name = 'mercoledi'; break;
            case 4: $day_name = 'giovedi'; break;
            case 5: $day_name = 'venerdi'; break;
            case 6: $day_name = 'sabato'; break;
            case 7: $day_name = 'domenica'; break;
        }
        
        // Usa l'oggetto DateTime per la data corrente
        $today = new DateTime('today');
        
        // Salta giorni passati (date nel passato)
        if ($date < $today) {
            continue;
        }
        
        // Genera slot per mattina e pomeriggio
        $slots = [];
        
        // Mattina
        if ($preferences['mattina']) {
            $start_time = $preferences['ora_inizio_mattina'];
            $end_time = $preferences['ora_fine_mattina'];
            
            $slots[] = [
                'data' => $date->format('Y-m-d'),
                'giorno_settimana' => $day_name,
                'ora_inizio' => $start_time,
                'ora_fine' => $end_time
            ];
        }
        
        // Pomeriggio
        if ($preferences['pomeriggio']) {
            $start_time = $preferences['ora_inizio_pomeriggio'];
            $end_time = $preferences['ora_fine_pomeriggio'];
            
            $slots[] = [
                'data' => $date->format('Y-m-d'),
                'giorno_settimana' => $day_name,
                'ora_inizio' => $start_time,
                'ora_fine' => $end_time
            ];
        }
        
        // Filtra gli slot in base agli eventi del calendario
        foreach ($slots as $slot) {
            if (!is_slot_occupied($date, $slot, $events)) {
                $availability[] = $slot;
            }
        }
    }
    
    return $availability;
}

// Funzione migliorata per controllare se uno slot è occupato da eventi
function is_slot_occupied($date, $slot, $events) {
    $slot_start = clone $date;
    $slot_end = clone $date;
    
    list($start_hour, $start_minute) = explode(':', $slot['ora_inizio']);
    list($end_hour, $end_minute) = explode(':', $slot['ora_fine']);
    
    $slot_start->setTime((int)$start_hour, (int)$start_minute);
    $slot_end->setTime((int)$end_hour, (int)$end_minute);
    
    $slot_date = $slot_start->format('Y-m-d');
    error_log("Verifica slot: " . $slot_start->format('Y-m-d H:i:s') . " - " . $slot_end->format('Y-m-d H:i:s'));
    
    foreach ($events as $index => $event) {
        $event_start_date = $event['start']->format('Y-m-d');
        $event_end_date = $event['end']->format('Y-m-d');
        
        // Processa solo eventi relativi al giorno corrente o eventi multi-giorno
        if ($event_start_date == $slot_date || $event_end_date == $slot_date || 
            ($event_start_date < $slot_date && $event_end_date > $slot_date)) {
            
            // Controlla se c'è sovrapposizione
            if (($event['start'] < $slot_end) && ($event['end'] > $slot_start)) {
                error_log("Sovrapposizione trovata con evento: " . 
                          ($event['summary'] ?? 'Senza titolo') . " - " .
                          $event['start']->format('Y-m-d H:i:s') . " - " . 
                          $event['end']->format('Y-m-d H:i:s'));
                return true;
            }
            
            // Gestisci eventi di tutto il giorno
            if ($event['start']->format('H:i:s') == '00:00:00' && 
                $event['end']->format('H:i:s') == '00:00:00' && 
                $event_start_date != $event_end_date) {
                error_log("Evento giornaliero trovato: " . ($event['summary'] ?? 'Senza titolo'));
                return true;
            }
        }
    }
    
    return false;
}

// Funzione per salvare le disponibilità nel database
function save_availability($conn, $teacher_email, $availability) {
    if (empty($availability)) {
        error_log("Nessuna disponibilità da salvare per $teacher_email");
        return true; // Nessuna disponibilità da salvare
    }
    
    $success = true;
    error_log("Salvataggio di " . count($availability) . " disponibilità per $teacher_email");
    
    // Modifica la query per usare REPLACE INTO invece di INSERT
    // REPLACE INTO cancellerà automaticamente eventuali righe duplicate prima di inserire le nuove
    $query = "REPLACE INTO Disponibilita (teacher_email, giorno_settimana, ora_inizio, ora_fine) 
              VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    foreach ($availability as $slot) {
        $stmt->bind_param(
            "ssss", 
            $teacher_email, 
            $slot['giorno_settimana'], 
            $slot['ora_inizio'], 
            $slot['ora_fine']
        );
        
        if (!$stmt->execute()) {
            error_log("Errore nell'inserimento della disponibilità: " . $stmt->error);
            $success = false;
        } else {
            // Prima controlla se esiste già una lezione per questa disponibilità
            $check_query = "SELECT id FROM Lezioni 
                            WHERE teacher_email = ? 
                            AND start_time = ? 
                            AND stato = 'disponibile'";
            $check_stmt = $conn->prepare($check_query);
            $start_time_str = $slot['data'] . ' ' . $slot['ora_inizio'];
            $check_stmt->bind_param("ss", $teacher_email, $start_time_str);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            // Se non esiste già, crea la lezione
            if ($check_result->num_rows == 0) {
                // Crea anche la lezione corrispondente
                $date_str = $slot['data'];
                
                // Formatta correttamente le date per MySQL
                $titolo = "Disponibilità " . ucfirst($slot['giorno_settimana']);
                
                // Correggi il formato della data/ora per evitare doppi secondi
                $formatted_date = date('Y-m-d', strtotime($date_str));
                
                // Controlla se l'ora già include i secondi
                $start_time = $slot['ora_inizio'];
                $end_time = $slot['ora_fine'];
                
                // Se l'ora non include già i secondi, aggiungerli
                if (substr_count($start_time, ':') === 1) {
                    $start_time_str = $formatted_date . ' ' . $start_time . ':00';
                } else {
                    $start_time_str = $formatted_date . ' ' . $start_time;
                }
                
                if (substr_count($end_time, ':') === 1) {
                    $end_time_str = $formatted_date . ' ' . $end_time . ':00';
                } else {
                    $end_time_str = $formatted_date . ' ' . $end_time;
                }
                
                error_log("Inserendo lezione: $titolo - $start_time_str - $end_time_str");
                
                $lesson_query = "INSERT INTO Lezioni 
                                (teacher_email, titolo, start_time, end_time, stato) 
                                VALUES (?, ?, ?, ?, 'disponibile')";
                $lesson_stmt = $conn->prepare($lesson_query);
                $lesson_stmt->bind_param("ssss", 
                    $teacher_email, 
                    $titolo,
                    $start_time_str,
                    $end_time_str
                );
                
                if (!$lesson_stmt->execute()) {
                    error_log("Errore nell'inserimento della lezione: " . $lesson_stmt->error);
                    $success = false;
                }
            }
        }
    }
    
    return $success;
}

// If the script reaches here normally, make sure any content is sent
if (ob_get_length() > 0) {
    ob_end_flush();
}
?>
