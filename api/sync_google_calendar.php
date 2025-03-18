<?php
// Strict error handling approach
ini_set('display_errors', 0); // Don't display errors directly
error_reporting(E_ALL); // But still report all types of errors

// Start output buffering to prevent any output before headers
ob_start();

try {
    require_once '../connessione.php';
    session_start();
    
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
    
    // Carica le preferenze dell'utente per l'uso nelle funzioni
    global $preferences;
    $preferences = get_teacher_preferences($conn, $teacher_email);
    
    // Scarica e analizza il calendario
    $calendar_url = $data['google_calendar_link'];
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
    
    if (!$ical_content) {
        throw new Exception('Impossibile scaricare il calendario');
    }
    
    // Analizza gli eventi dal calendario iCal
    $events = parse_ical_events($ical_content);
    
    // Ottieni le date per cui generare disponibilità
    $now = new DateTime();
    $end_date = clone $now;
    $end_date->modify('+4 weeks');
    $dates = generate_dates($now, $end_date, isset($preferences['weekend']) ? $preferences['weekend'] : false);

    // Genera disponibilità in base agli eventi trovati
    $availability = generate_availability($dates, $events, $preferences);
    
    // Salva la disponibilità nel database
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
    
    // Match each VEVENT block
    preg_match_all('/BEGIN:VEVENT.*?END:VEVENT/s', $ical_content, $matches);
    
    foreach ($matches[0] as $index => $event_text) {
        $event = [];
        
        // Extract summary/title
        if (preg_match('/SUMMARY:(.*?)(?:\r\n|\n)/s', $event_text, $summary)) {
            $event['summary'] = trim($summary[1]);
        } else {
            $event['summary'] = 'Untitled Event';
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
            $events[] = $event;
        }
    }
    
    return $events;
}

// Funzione per analizzare date iCal
function parse_ical_date($date_str) {
    // Handle all-day events (8 digits only)
    if (strlen($date_str) == 8) {
        $year = substr($date_str, 0, 4);
        $month = substr($date_str, 4, 2);
        $day = substr($date_str, 6, 2);
        
        // All-day events: set to midnight (start) or 23:59:59 (end)
        return new DateTime("$year-$month-$day 00:00:00");
    }
    // Handle timed events
    else {
        $year = substr($date_str, 0, 4);
        $month = substr($date_str, 4, 2);
        $day = substr($date_str, 6, 2);
        
        // Check if time component exists
        if (strlen($date_str) >= 11) {
            $hour = substr($date_str, 9, 2);
            $minute = substr($date_str, 11, 2);
            $second = (strlen($date_str) >= 15) ? substr($date_str, 13, 2) : '00';
            
            // If ends with Z, it's UTC time, convert to local
            if (substr($date_str, -1) === 'Z') {
                $dt = new DateTime("$year-$month-$day $hour:$minute:$second", new DateTimeZone('UTC'));
                $dt->setTimezone(new DateTimeZone('Europe/Rome')); // Convert to local time
                return $dt;
            } else {
                return new DateTime("$year-$month-$day $hour:$minute:$second", new DateTimeZone('Europe/Rome'));
            }
        } else {
            return new DateTime("$year-$month-$day 00:00:00", new DateTimeZone('Europe/Rome'));
        }
    }
}

// Recupera le preferenze di disponibilità del professore
function get_teacher_preferences($conn, $teacher_email) {
    $query = "SELECT * FROM Preferenze_Disponibilita WHERE teacher_email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $teacher_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        // Default preferences
        return [
            'weekend' => false,
            'mattina' => true,
            'pomeriggio' => true,
            'ora_inizio_mattina' => '08:00:00',
            'ora_fine_mattina' => '13:00:00',
            'ora_inizio_pomeriggio' => '14:00:00',
            'ora_fine_pomeriggio' => '19:00:00',
            'ore_prima_evento' => 0,
            'ore_dopo_evento' => 0
        ];
    }
}

// Generate dates for which availability should be checked
function generate_dates($start_date, $end_date, $include_weekend = false) {
    $dates = [];
    $current = clone $start_date;
    
    while ($current <= $end_date) {
        $day_of_week = $current->format('N'); // 1 (Monday) to 7 (Sunday)
        
        // Skip weekends if not included
        if ($include_weekend || ($day_of_week <= 5)) {
            $dates[] = $current->format('Y-m-d');
        }
        
        $current->modify('+1 day');
    }
    
    return $dates;
}

function generate_availability($dates, $events, $preferences) {
    $availability = [];
    
    // Durata di ogni slot in minuti (default: 60 minuti)
    $slot_duration = 60;
    
    // Track slots generated by day
    $slots_by_day = [
        'lunedi' => 0,
        'martedi' => 0,
        'mercoledi' => 0,
        'giovedi' => 0,
        'venerdi' => 0,
        'sabato' => 0,
        'domenica' => 0
    ];
    
    // Process each date
    foreach ($dates as $date_str) {
        $date = new DateTime($date_str);
        $day_num = $date->format('N'); // 1 (Monday) to 7 (Sunday)
        $day_names = ['', 'lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica'];
        $day_name = $day_names[$day_num];
        
        // Counter for slots generated on this day
        $day_slots = 0;
        
        // MATTINA: Genera slot per orari mattutini
        if ($preferences['mattina'] && !empty($preferences['ora_inizio_mattina']) && !empty($preferences['ora_fine_mattina'])) {
            $morning_start_time = $preferences['ora_inizio_mattina'];
            $morning_end_time = $preferences['ora_fine_mattina'];
            
            // Converti stringhe orario in oggetti DateTime
            $morning_start = new DateTime($date_str . ' ' . $morning_start_time);
            $morning_end = new DateTime($date_str . ' ' . $morning_end_time);
            
            // Genera slot orari da inizio a fine mattina
            $current = clone $morning_start;
            while ($current < $morning_end) {
                $slot_end = clone $current;
                $slot_end->modify("+$slot_duration minutes");
                
                // Make sure we don't go past the end time
                if ($slot_end > $morning_end) {
                    $slot_end = clone $morning_end;
                }
                
                // Only add if we have a meaningful slot (not zero duration)
                if ($current < $slot_end) {
                    $slot = [
                        'data' => $date_str,
                        'giorno_settimana' => $day_name,
                        'ora_inizio' => $current->format('H:i'),
                        'ora_fine' => $slot_end->format('H:i'),
                        'from_google_calendar' => true
                    ];
                    
                    // Only add if slot is not occupied
                    if (!is_slot_occupied($date_str, $slot, $events)) {
                        $availability[] = $slot;
                        $day_slots++;
                        $slots_by_day[$day_name]++;
                    }
                }
                
                $current = $slot_end;
            }
        }
        
        // POMERIGGIO: Genera slot per orari pomeridiani
        if ($preferences['pomeriggio'] && !empty($preferences['ora_inizio_pomeriggio']) && !empty($preferences['ora_fine_pomeriggio'])) {
            $afternoon_start_time = $preferences['ora_inizio_pomeriggio'];
            $afternoon_end_time = $preferences['ora_fine_pomeriggio'];
            
            // Converti stringhe orario in oggetti DateTime
            $afternoon_start = new DateTime($date_str . ' ' . $afternoon_start_time);
            $afternoon_end = new DateTime($date_str . ' ' . $afternoon_end_time);
            
            // Genera slot orari da inizio a fine pomeriggio
            $current = clone $afternoon_start;
            while ($current < $afternoon_end) {
                $slot_end = clone $current;
                $slot_end->modify("+$slot_duration minutes");
                
                // Make sure we don't go past the end time
                if ($slot_end > $afternoon_end) {
                    $slot_end = clone $afternoon_end;
                }
                
                // Only add if we have a meaningful slot (not zero duration)
                if ($current < $slot_end) {
                    $slot = [
                        'data' => $date_str,
                        'giorno_settimana' => $day_name,
                        'ora_inizio' => $current->format('H:i'),
                        'ora_fine' => $slot_end->format('H:i'),
                        'from_google_calendar' => true
                    ];
                    
                    // Only add if slot is not occupied
                    if (!is_slot_occupied($date_str, $slot, $events)) {
                        $availability[] = $slot;
                        $day_slots++;
                        $slots_by_day[$day_name]++;
                    }
                }
                
                $current = $slot_end;
            }
        }
    }
    
    return $availability;
}

// Fix is_slot_occupied function to handle date_str consistently
function is_slot_occupied($date_str, $slot, $events) {
    global $preferences; // Accede alle preferenze dell'utente
    
    // Get day name for logging
    $day_name = $slot['giorno_settimana'];
    
    // Create precise time objects for slot
    $slot_start = new DateTime($date_str . ' ' . $slot['ora_inizio'] . ':00');
    $slot_end = new DateTime($date_str . ' ' . $slot['ora_fine'] . ':00');
    
    // Debug variables to track processing
    $events_on_this_day = 0;
    
    // Recupera i buffer di tempo dalle preferenze
    $buffer_before = isset($preferences['ore_prima_evento']) ? floatval($preferences['ore_prima_evento']) : 0;
    $buffer_after = isset($preferences['ore_dopo_evento']) ? floatval($preferences['ore_dopo_evento']) : 0;
    
    // Controlla tutti gli eventi
    foreach ($events as $event) {
        // Skip events without start or end times
        if (empty($event['start']) || empty($event['end'])) {
            continue;
        }
        
        $event_summary = $event['summary'] ?? 'Untitled event';
        $event_start = $event['start'];
        $event_end = $event['end'];
        
        // Check if event date matches the slot date
        $event_date = $event_start->format('Y-m-d');
        $event_start_date = $event_start->format('Y-m-d');
        $event_end_date = $event_end->format('Y-m-d');
        
        // If the event spans this date or is on this date
        if ($event_start_date <= $date_str && $event_end_date >= $date_str) {
            $events_on_this_day++;
            
            // Create datetime objects with boundaries for the current day
            $check_start = clone $event_start;
            $check_end = clone $event_end;
            
            // If the event starts before this day, adjust start time to midnight of this day
            if ($event_start_date < $date_str) {
                $check_start = new DateTime($date_str . ' 00:00:00');
            }
            
            // If the event ends after this day, adjust end time to 23:59:59 of this day
            if ($event_end_date > $date_str) {
                $check_end = new DateTime($date_str . ' 23:59:59');
            }
            
            // Applica i buffer di tempo prima e dopo l'evento
            $event_start_with_buffer = clone $check_start;
            $event_end_with_buffer = clone $check_end;
            
            if ($buffer_before > 0) {
                $minutes_before = $buffer_before * 60;
                $event_start_with_buffer->modify("-$minutes_before minutes");
            }
            
            if ($buffer_after > 0) {
                $minutes_after = $buffer_after * 60;
                $event_end_with_buffer->modify("+$minutes_after minutes");
            }
            
            // Controllo sovrapposizione con l'evento considerando i buffer
            if (($event_start_with_buffer < $slot_end) && ($event_end_with_buffer > $slot_start)) {
                return true; // Slot is occupied
            }
        }
    }
    
    return false; // Slot is free
}

// Enhanced save_availability function to track and verify saved slots by date AND day
function save_availability($conn, $teacher_email, $availability) {
    if (empty($availability)) {
        return true;
    }
    
    // Track slots saved by day of week AND date to detect anomalies
    $saved_by_day = [
        'lunedi' => 0,
        'martedi' => 0,
        'mercoledi' => 0,
        'giovedi' => 0,
        'venerdi' => 0,
        'sabato' => 0,
        'domenica' => 0
    ];
    $saved_by_date = [];
    
    // 1. Elimina le lezioni disponibili esistenti basate su Google Calendar
    $delete_query = "DELETE FROM Lezioni 
                    WHERE teacher_email = ? 
                    AND stato = 'disponibile'
                    AND start_time > NOW()";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("s", $teacher_email);
    $stmt->execute();
    
    // 2. Inserisci le nuove disponibilità nella tabella Lezioni
    $success = true;
    
    $lesson_query = "INSERT INTO Lezioni (teacher_email, titolo, descrizione, start_time, end_time, stato) 
                    VALUES (?, 'Disponibile', 'Slot generato automaticamente', ?, ?, 'disponibile')";
    $lesson_stmt = $conn->prepare($lesson_query);
    
    foreach ($availability as $slot) {
        $date_str = $slot['data'];
        $day_name = $slot['giorno_settimana'];
        
        // Tieni traccia delle date e giorni
        if (!isset($saved_by_date[$date_str])) {
            $saved_by_date[$date_str] = 0;
        }
        
        // Converti in datetime per DB
        $start_time_str = $date_str . ' ' . $slot['ora_inizio'] . ':00';
        $end_time_str = $date_str . ' ' . $slot['ora_fine'] . ':00';
        
        // Inserisci lezione
        $lesson_stmt->bind_param("sss", $teacher_email, $start_time_str, $end_time_str);
        
        if (!$lesson_stmt->execute()) {
            $success = false;
        } else {
            $saved_by_day[$day_name]++;
            $saved_by_date[$date_str]++;
        }
    }
    
    return $success;
}
?>