<?php
// Strict error handling approach
ini_set('display_errors', 0); // Don't display errors directly
error_reporting(E_ALL); // But still report all types of errors

// Start output buffering to prevent any output before headers
ob_start();

// Define log file path in website root directory
define('CALENDAR_LOG_FILE', dirname(dirname(__FILE__)) . '/calendar_sync_log.txt');

// Create or clear the log file at the start of each sync
file_put_contents(CALENDAR_LOG_FILE, "=== Calendar Sync Log - " . date('Y-m-d H:i:s') . " ===\n\n");

// Custom log function that writes to our file
function calendar_log($message) {
    $timestamp = date('H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents(CALENDAR_LOG_FILE, $log_entry, FILE_APPEND);
}

// For catching fatal errors that would otherwise produce blank page
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Clean any output that might have been sent
        ob_end_clean();
        
        // Log the error
        calendar_log("FATAL ERROR: {$error['message']} in {$error['file']} on line {$error['line']}");
        
        // Send proper JSON error response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Si è verificato un errore durante la sincronizzazione',
            'debug_info' => $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ]);
    }
});

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
        // Valori predefiniti se non ci sono preferenze
        return [
            'weekend' => 0,
            'mattina' => 1,
            'pomeriggio' => 1,
            'ora_inizio_mattina' => '08:00:00',
            'ora_fine_mattina' => '13:00:00',
            'ora_inizio_pomeriggio' => '14:00:00',
            'ora_fine_pomeriggio' => '19:00:00',
            'ore_prima_evento' => 0,
            'ore_dopo_evento' => 0
        ];
    }
}

try {
    calendar_log("Inizializzazione sincronizzazione calendario");
    session_start();
    require_once '../connessione.php';
    
    // Controllo se l'utente è loggato ed è un professore
    if (!isset($_SESSION['user']) || $_SESSION['tipo'] !== 'professore') {
        throw new Exception('Non autorizzato');
    }
    
    $teacher_email = $_SESSION['email'];
    calendar_log("Utente: $teacher_email");
    
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
    calendar_log("Preferenze caricate: buffer prima=" . $preferences['ore_prima_evento'] . 
                 "h, buffer dopo=" . $preferences['ore_dopo_evento'] . "h");
    
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
    
    // Ottieni le date delle prossime 3 settimane
    $dates = get_next_weeks_dates(3);
    
    // Genera disponibilità in base alle date e agli eventi
    $availability = generate_availability($dates, $events, $data);
    
    // Prima elimina le vecchie disponibilità non ancora prenotate
    calendar_log("Eliminazione vecchie disponibilità");
    $delete_query = "DELETE FROM Disponibilita WHERE teacher_email = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("s", $teacher_email);
    $stmt->execute();
    
    // Elimina solo le vecchie lezioni che sono ancora disponibili
    // NON eliminare lezioni prenotate!
    $delete_lessons_query = "DELETE FROM Lezioni WHERE teacher_email = ? AND stato = 'disponibile'";
    $stmt = $conn->prepare($delete_lessons_query);
    $stmt->bind_param("s", $teacher_email);
    $stmt->execute();
    
    // Salva le nuove disponibilità nel database
    $result = save_availability($conn, $teacher_email, $availability);
    
    if (!$result) {
        throw new Exception('Errore durante il salvataggio delle disponibilità');
    }
    
    // Add verification
    verify_saved_availability($conn, $teacher_email);
    
    // Success response - ensure output buffer is clean before sending
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Disponibilità generate con successo']);

} catch (Exception $e) {
    // Error response - ensure output buffer is clean before sending
    ob_end_clean();
    
    // Log the exception
    calendar_log("EXCEPTION: {$e->getMessage()}");
    
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
            calendar_log("Evento #$index importato: " . 
                      ($event['summary'] ?? 'Senza titolo') . " - " . 
                      $event['start']->format('Y-m-d H:i:s') . " - " . 
                      $event['end']->format('Y-m-d H:i:s'));
            
            $events[] = $event;
        }
    }
    
    // Ordina gli eventi per data di inizio (cronologicamente)
    usort($events, function($a, $b) {
        return $a['start'] <=> $b['start'];
    });
    
    calendar_log("Totale eventi importati: " . count($events) . " (ordinati cronologicamente)");
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
                calendar_log("Conversione UTC→Europe/Rome: $date_string → " . $date->format('Y-m-d H:i:s'));
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
    
    calendar_log("Formato data non riconosciuto: $date_string");
    return null;
}

// Funzione per generare le date delle prossime settimane
function get_next_weeks_dates($weeks) {
    $dates = [];
    $today = new DateTime('today');
    
    // Genera date per esattamente il numero di settimane specificato
    for ($day = 0; $day < ($weeks * 7); $day++) {
        $date = clone $today;
        $date->modify("+$day days");
        $dates[] = $date;
    }
    
    return $dates;
}

// Add this debug function to analyze events specifically for a given date
function debug_events_for_date($date_str, $events) {
    calendar_log("========== DEBUG EVENTI PER $date_str ==========");
    $found = 0;
    
    foreach ($events as $index => $event) {
        $event_start_date = $event['start']->format('Y-m-d');
        $event_end_date = $event['end']->format('Y-m-d');
        $event_summary = $event['summary'] ?? 'Senza titolo';
        
        // Check all possible ways this event could affect this date
        if ($event_start_date === $date_str || 
            $event_end_date === $date_str || 
            ($event_start_date < $date_str && $event_end_date > $date_str)) {
            
            $found++;
            calendar_log("EVENTO #$index: '$event_summary'");
            calendar_log(" - Start: " . $event['start']->format('Y-m-d H:i:s'));
            calendar_log(" - End: " . $event['end']->format('Y-m-d H:i:s'));
            
            // Check if this is a multi-day event
            if ($event_start_date !== $event_end_date) {
                calendar_log(" - MULTI-GIORNO: Inizia $event_start_date, Finisce $event_end_date");
            }
            
            // Calculate effective times for this day
            $effective_start = clone $event['start'];
            $effective_end = clone $event['end'];
            
            if ($event_start_date < $date_str) {
                $effective_start = new DateTime($date_str . ' 00:00:00');
                calendar_log(" - Orario inizio effettivo: " . $effective_start->format('H:i'));
            }
            
            if ($event_end_date > $date_str) {
                $effective_end = new DateTime($date_str . ' 23:59:59');
                calendar_log(" - Orario fine effettivo: " . $effective_end->format('H:i'));
            }
            
            calendar_log(" - Blocca orari tra: " . $effective_start->format('H:i') . " - " . $effective_end->format('H:i'));
        }
    }
    
    if ($found === 0) {
        calendar_log("NESSUN EVENTO TROVATO PER QUESTA DATA");
    } else {
        calendar_log("TOTALE EVENTI TROVATI PER QUESTA DATA: $found");
    }
    calendar_log("=================================================");
    
    return $found;
}

// Modified generate_availability function with better tracking of days
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
    
    // Log delle preferenze per debug
    calendar_log("Generazione disponibilità con preferenze: " . 
              "mattina=" . ($preferences['mattina'] ? 'true' : 'false') . 
              " (" . $preferences['ora_inizio_mattina'] . "-" . $preferences['ora_fine_mattina'] . "), " .
              "pomeriggio=" . ($preferences['pomeriggio'] ? 'true' : 'false') . 
              " (" . $preferences['ora_inizio_pomeriggio'] . "-" . $preferences['ora_fine_pomeriggio'] . ")");
    
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
        
        $date_str = $date->format('Y-m-d');
        calendar_log("\n\n======== Elaborazione disponibilità per $date_str ($day_name) ========");
        calendar_log("📅 SETTIMANA: " . date('W', strtotime($date_str)) . " - GIORNO: " . date('N', strtotime($date_str)));
        
        // Run our diagnostic tool for this day
        $events_count = debug_events_for_date($date_str, $events);
        calendar_log("Trovati $events_count eventi rilevanti per $date_str");
        
        $day_slots = 0;
        
        // MATTINA: Genera slot per orari mattutini
        if ($preferences['mattina'] && !empty($preferences['ora_inizio_mattina']) && !empty($preferences['ora_fine_mattina'])) {
            $morning_start_time = $preferences['ora_inizio_mattina'];
            $morning_end_time = $preferences['ora_fine_mattina'];
            
            calendar_log("Generazione slot mattutini: $morning_start_time - $morning_end_time");
            
            // Converti stringhe orario in oggetti DateTime
            $morning_start = new DateTime($date_str . ' ' . $morning_start_time);
            $morning_end = new DateTime($date_str . ' ' . $morning_end_time);
            
            // Genera slot orari da inizio a fine mattina
            $current = clone $morning_start;
            while ($current < $morning_end) {
                $slot_end = clone $current;
                $slot_end->modify("+{$slot_duration} minutes");
                
                // Se lo slot finale supererebbe l'orario di fine, lo tronca
                if ($slot_end > $morning_end) {
                    $slot_end = clone $morning_end;
                }
                
                $slot = [
                    'data' => $date_str,
                    'giorno_settimana' => $day_name,
                    'ora_inizio' => $current->format('H:i'),
                    'ora_fine' => $slot_end->format('H:i')
                ];
                
                // CRITICAL FIX: Pass $date_str instead of $date
                if (!is_slot_occupied($date_str, $slot, $events)) {
                    $availability[] = $slot;
                    $day_slots++;
                    $slots_by_day[$day_name]++;
                    calendar_log("Aggiunto slot mattutino: {$slot['ora_inizio']} - {$slot['ora_fine']}");
                } else {
                    calendar_log("Slot mattutino occupato: {$slot['ora_inizio']} - {$slot['ora_fine']}");
                }
                
                $current = $slot_end;
            }
        }
        
        // POMERIGGIO: Genera slot per orari pomeridiani
        if ($preferences['pomeriggio'] && !empty($preferences['ora_inizio_pomeriggio']) && !empty($preferences['ora_fine_pomeriggio'])) {
            $afternoon_start_time = $preferences['ora_inizio_pomeriggio'];
            $afternoon_end_time = $preferences['ora_fine_pomeriggio'];
            
            calendar_log("Generazione slot pomeridiani: $afternoon_start_time - $afternoon_end_time");
            
            // Converti stringhe orario in oggetti DateTime
            $afternoon_start = new DateTime($date_str . ' ' . $afternoon_start_time);
            $afternoon_end = new DateTime($date_str . ' ' . $afternoon_end_time);
            
            // Genera slot orari da inizio a fine pomeriggio
            $current = clone $afternoon_start;
            while ($current < $afternoon_end) {
                $slot_end = clone $current;
                $slot_end->modify("+{$slot_duration} minutes");
                
                // Se lo slot finale supererebbe l'orario di fine, lo tronca
                if ($slot_end > $afternoon_end) {
                    $slot_end = clone $afternoon_end;
                }
                
                $slot = [
                    'data' => $date_str,
                    'giorno_settimana' => $day_name,
                    'ora_inizio' => $current->format('H:i'),
                    'ora_fine' => $slot_end->format('H:i')
                ];
                
                // CRITICAL FIX: Pass $date_str instead of $date
                if (!is_slot_occupied($date_str, $slot, $events)) {
                    $availability[] = $slot;
                    $day_slots++;
                    $slots_by_day[$day_name]++;
                    calendar_log("Aggiunto slot pomeridiano: {$slot['ora_inizio']} - {$slot['ora_fine']}");
                } else {
                    calendar_log("Slot pomeridiano occupato: {$slot['ora_inizio']} - {$slot['ora_fine']}");
                }
                
                $current = $slot_end;
            }
        }
        
        calendar_log("📊 Giorno $date_str ($day_name): generati $day_slots slot");
    }
    
    // Display summary of slots generated by day
    calendar_log("\n===== RIEPILOGO SLOT GENERATI PER GIORNO =====");
    foreach ($slots_by_day as $day => $count) {
        calendar_log("$day: $count slot");
    }
    
    calendar_log("Generati " . count($availability) . " slot di disponibilità totali");
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
    
    calendar_log("🔍 VERIFICA SLOT: [$day_name] $date_str {$slot['ora_inizio']}-{$slot['ora_fine']}");
    
    // Debug variables to track processing
    $events_on_this_day = 0;
    
    // Recupera i buffer di tempo dalle preferenze
    $buffer_before = isset($preferences['ore_prima_evento']) ? floatval($preferences['ore_prima_evento']) : 0;
    $buffer_after = isset($preferences['ore_dopo_evento']) ? floatval($preferences['ore_dopo_evento']) : 0;
    
    if ($buffer_before > 0 || $buffer_after > 0) {
        calendar_log("🕒 Buffer configurati: prima=" . $buffer_before . "h, dopo=" . $buffer_after . "h");
    }
    
    foreach ($events as $index => $event) {
        $event_summary = $event['summary'] ?? 'Senza titolo';
        
        // Extract precise date information
        $event_start_date = $event['start']->format('Y-m-d');
        $event_end_date = $event['end']->format('Y-m-d');
        
        // Check if this event happens on our target date
        $event_happens_today = false;
        
        // Case 1: Event starts on this date
        if ($event_start_date === $date_str) {
            $event_happens_today = true;
            calendar_log("  📅 [$day_name] Evento '$event_summary' INIZIA oggi ($date_str)");
        } 
        // Case 2: Event ends on this date
        elseif ($event_end_date === $date_str) {
            $event_happens_today = true;
            calendar_log("  📅 [$day_name] Evento '$event_summary' FINISCE oggi ($date_str)");
        } 
        // Case 3: Event spans over this date (starts before, ends after)
        elseif ($event_start_date < $date_str && $event_end_date > $date_str) {
            $event_happens_today = true;
            calendar_log("  📅 [$day_name] Evento '$event_summary' ATTRAVERSA oggi ($date_str)");
        }
        
        if ($event_happens_today) {
            $events_on_this_day++;
            
            // Create clean copies of the event start/end times
            $check_start = clone $event['start'];
            $check_end = clone $event['end'];
            
            // Normalize multi-day events to this day's boundaries
            if ($event_start_date < $date_str) {
                $check_start = new DateTime($date_str . ' 00:00:00');
                calendar_log("    ⏰ Evento inizia prima: aggiustato a " . $check_start->format('Y-m-d H:i:s'));
            }
            
            if ($event_end_date > $date_str) {
                $check_end = new DateTime($date_str . ' 23:59:59');
                calendar_log("    ⏰ Evento finisce dopo: aggiustato a " . $check_end->format('Y-m-d H:i:s'));
            }
            
            // Applica i buffer di tempo prima e dopo l'evento
            $event_start_with_buffer = clone $check_start;
            $event_end_with_buffer = clone $check_end;
            
            if ($buffer_before > 0) {
                $minutes_before = $buffer_before * 60;
                $event_start_with_buffer->modify("-$minutes_before minutes");
                calendar_log("    🕒 Buffer prima: " . $minutes_before . " min, nuovo inizio " . 
                           $event_start_with_buffer->format('H:i'));
            }
            
            if ($buffer_after > 0) {
                $minutes_after = $buffer_after * 60;
                $event_end_with_buffer->modify("+$minutes_after minutes");
                calendar_log("    🕒 Buffer dopo: " . $minutes_after . " min, nuova fine " . 
                           $event_end_with_buffer->format('H:i'));
            }
            
            // DEBUG: Show exact times being compared
            calendar_log("    🕒 Confronto preciso:");
            calendar_log("      - Slot: " . $slot_start->format('Y-m-d H:i:s') . " - " . $slot_end->format('Y-m-d H:i:s'));
            calendar_log("      - Evento (con buffer): " . $event_start_with_buffer->format('Y-m-d H:i:s') . 
                       " - " . $event_end_with_buffer->format('Y-m-d H:i:s'));
            
            // Controllo sovrapposizione con l'evento considerando i buffer
            if (($event_start_with_buffer < $slot_end) && ($event_end_with_buffer > $slot_start)) {
                calendar_log("    ❌ SOVRAPPOSIZIONE TROVATA: Slot bloccato da '$event_summary'");
                return true; // Slot is occupied
            } else {
                calendar_log("    ✅ Nessuna sovrapposizione");
            }
        }
    }
    
    calendar_log("🆓 SLOT LIBERO: [$day_name] $date_str {$slot['ora_inizio']}-{$slot['ora_fine']} " .
              "(Controllati $events_on_this_day eventi su questo giorno)");
    
    return false; // Slot is free
}

// Enhanced save_availability function to track and verify saved slots by date AND day
function save_availability($conn, $teacher_email, $availability) {
    if (empty($availability)) {
        calendar_log("Nessuna disponibilità da salvare per $teacher_email");
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
    $saved_by_day_and_date = [];
    
    // Prepare statements for database operations
    $insert_query = "INSERT INTO Disponibilita (teacher_email, giorno_settimana, ora_inizio, ora_fine) 
                     VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    
    $lesson_query = "INSERT INTO Lezioni (teacher_email, titolo, start_time, end_time, stato) 
                     VALUES (?, ?, ?, ?, 'disponibile')";
    $lesson_stmt = $conn->prepare($lesson_query);
    
    // CRUCIAL FIX: Check if this slot is already booked before creating a new one
    $booked_slots = [];
    $booked_query = "SELECT DATE_FORMAT(start_time, '%Y-%m-%d') as date, 
                    DATE_FORMAT(start_time, '%H:%i') as start_time,
                    DATE_FORMAT(end_time, '%H:%i') as end_time
                    FROM Lezioni 
                    WHERE teacher_email = ? AND stato = 'prenotata'";
    $booked_stmt = $conn->prepare($booked_query);
    $booked_stmt->bind_param("s", $teacher_email);
    $booked_stmt->execute();
    $booked_result = $booked_stmt->get_result();
    
    while ($row = $booked_result->fetch_assoc()) {
        $key = $row['date'] . '_' . $row['start_time'] . '_' . $row['end_time'];
        $booked_slots[$key] = true;
        calendar_log("Slot già prenotato: {$row['date']} {$row['start_time']}-{$row['end_time']}");
    }
    
    $success = true;
    calendar_log("Salvataggio " . count($availability) . " disponibilità per $teacher_email");
    
    foreach ($availability as $slot) {
        $day_name = $slot['giorno_settimana'];
        $date_str = $slot['data'];
        
        // Track counts for verification
        $saved_by_day[$day_name]++;
        if (!isset($saved_by_date[$date_str])) {
            $saved_by_date[$date_str] = 0;
            $saved_by_day_and_date[$date_str] = [];
        }
        $saved_by_date[$date_str]++;
        
        // Track day-date relationship for verification
        if (!isset($saved_by_day_and_date[$date_str][$day_name])) {
            $saved_by_day_and_date[$date_str][$day_name] = 0;
        }
        $saved_by_day_and_date[$date_str][$day_name]++;
        
        // CRUCIAL FIX: Check if this slot is already booked before creating a new one
        $slot_key = $date_str . '_' . $slot['ora_inizio'] . '_' . $slot['ora_fine'];
        if (isset($booked_slots[$slot_key])) {
            calendar_log("Saltando slot già prenotato: {$date_str} {$slot['ora_inizio']}-{$slot['ora_fine']}");
            continue;  // Skip this slot as it's already booked
        }
        
        // Insert into database
        $stmt->bind_param("ssss", $teacher_email, $day_name, $slot['ora_inizio'], $slot['ora_fine']);
        if (!$stmt->execute()) {
            calendar_log("Errore nell'inserimento della disponibilità: " . $stmt->error);
            $success = false;
        } else {
            // Create corresponding lesson
            $check_query = "SELECT id FROM Lezioni 
                           WHERE teacher_email = ? 
                           AND start_time = ? 
                           AND (stato = 'disponibile' OR stato = 'prenotata')";
            $check_stmt = $conn->prepare($check_query);
            $start_time_str = $date_str . ' ' . $slot['ora_inizio'];
            $check_stmt->bind_param("ss", $teacher_email, $start_time_str);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            // If lesson doesn't exist, create it
            if ($check_result->num_rows == 0) {
                $titolo = "Disponibilità " . ucfirst($day_name);
                $formatted_date = date('Y-m-d', strtotime($date_str));
                
                $start_time = $slot['ora_inizio'];
                $end_time = $slot['ora_fine'];
                
                // Format times consistently
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
                
                calendar_log("Inserendo lezione: $titolo - $start_time_str - $end_time_str");
                $lesson_stmt->bind_param("ssss", $teacher_email, $titolo, $start_time_str, $end_time_str);
                
                if (!$lesson_stmt->execute()) {
                    calendar_log("Errore nell'inserimento della lezione: " . $lesson_stmt->error);
                    $success = false;
                }
            }
        }
    }
    
    // Display summary of slots saved by day
    calendar_log("\n===== RIEPILOGO SLOT SALVATI PER GIORNO =====");
    foreach ($saved_by_day as $day => $count) {
        calendar_log("$day: $count slot");
    }
    
    // Display summary of slots saved by date
    calendar_log("\n===== RIEPILOGO SLOT SALVATI PER DATA =====");
    foreach ($saved_by_date as $date => $count) {
        calendar_log("$date: $count slot");
    }
    
    // Verification of days and dates
    calendar_log("\n===== VERIFICA CORRISPONDENZA GIORNO-DATA =====");
    foreach ($saved_by_day_and_date as $date => $days) {
        $day_of_week = date('N', strtotime($date));
        $expected_day = '';
        switch ($day_of_week) {
            case 1: $expected_day = 'lunedi'; break;
            case 2: $expected_day = 'martedi'; break;
            case 3: $expected_day = 'mercoledi'; break;
            case 4: $expected_day = 'giovedi'; break;
            case 5: $expected_day = 'venerdi'; break;
            case 6: $expected_day = 'sabato'; break;
            case 7: $expected_day = 'domenica'; break;
        }
        
        // Verify if day matches expected
        $actual_days = array_keys($days);
        if (count($actual_days) == 1 && $actual_days[0] === $expected_day) {
            calendar_log("✅ $date: Correttamente salvato come '$expected_day'");
        } else {
            calendar_log("⚠️ $date: ERRORE - dovrebbe essere '$expected_day' ma è stato salvato come: " . 
                       implode(", ", $actual_days));
        }
    }
    
    return $success;
}

// Enhanced verification function to check for duplicate slots
function verify_saved_availability($conn, $teacher_email) {
    calendar_log("\n===== VERIFICA DISPONIBILITÀ NEL DATABASE =====");
    
    // Check unique day-hour combinations in Disponibilita
    $query = "SELECT giorno_settimana, ora_inizio, COUNT(*) as count_slots 
              FROM Disponibilita 
              WHERE teacher_email = ? 
              GROUP BY giorno_settimana, ora_inizio 
              ORDER BY giorno_settimana, ora_inizio";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $teacher_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $day = $row['giorno_settimana'];
        calendar_log("- $day: {$row['ora_inizio']}: {$row['count_slots']} slot");
    }
    
    // Check unique day-hour combinations in Lezioni
    $query = "SELECT DATE_FORMAT(start_time, '%Y-%m-%d') as date, 
             DATE_FORMAT(start_time, '%W') as day_name, 
             COUNT(*) as count 
             FROM Lezioni 
             WHERE teacher_email = ? AND stato = 'disponibile' 
             GROUP BY DATE_FORMAT(start_time, '%Y-%m-%d') 
             ORDER BY start_time";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $teacher_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $count_by_day_name = [];
    while ($row = $result->fetch_assoc()) {
        $day_name = $row['day_name'];
        if (!isset($count_by_day_name[$day_name])) $count_by_day_name[$day_name] = 0;
        $count_by_day_name[$day_name] += $row['count'];
        calendar_log("- {$row['date']} ({$row['day_name']}): {$row['count']} lezioni");
    }
    
    calendar_log("\nRiepilogo lezioni per giorno della settimana:");
    foreach ($count_by_day_name as $day => $count) {
        calendar_log("- $day: $count lezioni");
    }
    
    calendar_log("Disponibilità uniche per giorno e ora:");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        calendar_log("- {$row['giorno_settimana']} {$row['ora_inizio']}: {$row['count_slots']} slot");
    }
    
    calendar_log("\nDettaglio orari per ogni giorno:");
    $query = "SELECT DATE_FORMAT(start_time, '%Y-%m-%d') as date, 
             DATE_FORMAT(start_time, '%H:%i') as time, 
             titolo 
             FROM Lezioni 
             WHERE teacher_email = ? AND stato = 'disponibile' 
             ORDER BY start_time";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $teacher_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_date = '';
    $slots_count = 0;
    while ($row = $result->fetch_assoc()) {
        if ($row['date'] != $current_date) {
            if ($current_date != '') {
                calendar_log("  Totale: $slots_count slot");
            }
            $current_date = $row['date'];
            $slots_count = 1;
            calendar_log("- {$row['date']}: {$row['time']} ({$row['titolo']})");
        } else {
            calendar_log("  {$row['time']} ({$row['titolo']})");
            $slots_count++;
        }
    }
    if ($current_date != '') {
        calendar_log("  Totale: $slots_count slot");
    }
    
    calendar_log("=================================================");
}

?>