<?php
session_start();
header('Content-Type: application/json');
require_once '../connessione.php';
require_once '../google_calendar/token_storage.php';  // Changed to directly include token_storage.php first
require_once '../google_calendar/calendar_functions.php';

// Verifica se l'utente è autenticato
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Ottieni l'email dell'utente richiesto (professore)
$teacher_email = isset($_GET['email']) ? $_GET['email'] : ($_SESSION['tipo'] === 'professore' ? $_SESSION['email'] : null);

// Validazione dell'email
if (empty($teacher_email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email professore mancante']);
    exit;
}

// Get current student email for checking bookings
$current_student_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$is_student = ($_SESSION['tipo'] === 'studente');

try {
    // Ottieni il tipo utente (professore) per accedere al suo calendario
    $userType = 'professore';
    
    // Verifica se l'utente ha autorizzato Google Calendar
    if (!hasValidOAuthTokens($teacher_email, $userType)) {
        // Se non ha autorizzato Google Calendar, mostra un errore
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Questo insegnante non ha configurato Google Calendar. La configurazione è necessaria per visualizzare la disponibilità.',
            'need_google_calendar' => true
        ]);
        exit;
    }
    
    // Ottieni client autenticato
    $client = getAuthenticatedClient($teacher_email, $userType);
    if (!$client) {
        throw new Exception("Impossibile ottenere un client autenticato per questo utente. Richiesta riconfigurazione di Google Calendar.");
    }
    
    // Recupera i calendari configurati da questo professore
    $query = "SELECT id, google_calendar_id, ore_prima_evento, ore_dopo_evento, is_active 
              FROM Calendari_Professori 
              WHERE teacher_email = ? AND is_active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $teacher_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $calendars = [];
    while ($row = $result->fetch_assoc()) {
        $calendars[] = $row;
    }
    
    if (count($calendars) === 0) {
        // Se non ci sono calendari configurati, mostra un errore
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Nessun calendario configurato. L\'insegnante deve configurare almeno un calendario in Google Calendar.',
            'no_calendars' => true
        ]);
        exit;
    }
    
    // Ottieni le preferenze di disponibilità
    $query = "SELECT weekend, mattina, pomeriggio, 
              ora_inizio_mattina, ora_fine_mattina, ora_inizio_pomeriggio, ora_fine_pomeriggio 
              FROM Preferenze_Disponibilita 
              WHERE teacher_email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $teacher_email);
    $stmt->execute();
    $preferences_result = $stmt->get_result();
    
    // Imposta preferenze di default se non presenti
    if ($preferences_result->num_rows === 0) {
        $preferences = [
            'weekend' => 0,
            'mattina' => 1,
            'pomeriggio' => 1,
            'ora_inizio_mattina' => '08:00:00',
            'ora_fine_mattina' => '13:00:00',
            'ora_inizio_pomeriggio' => '14:00:00',
            'ora_fine_pomeriggio' => '19:00:00'
        ];
    } else {
        $preferences = $preferences_result->fetch_assoc();
    }
    
    // Intervallo di tempo da considerare (4 settimane)
    $now = new DateTime('now', new DateTimeZone('Europe/Rome'));
    $fourWeeksLater = clone $now;
    $fourWeeksLater->modify('+4 weeks');
    
    // Ottieni il servizio Calendar
    $service = new Google_Service_Calendar($client);
    
    // Array per memorizzare gli eventi busy
    $busy_periods = [];
    $disponibilita = [];
    
    // Per ogni calendario, ottieni gli eventi
    foreach ($calendars as $calendar) {
        $calendarId = $calendar['google_calendar_id'];
        $hoursBefore = $calendar['ore_prima_evento'];
        $hoursAfter = $calendar['ore_dopo_evento'];
        
        // Ottieni gli eventi dal calendario
        $events = $service->events->listEvents(
            $calendarId,
            [
                'timeMin' => $now->format('c'),
                'timeMax' => $fourWeeksLater->format('c'),
                'singleEvents' => true,
                'orderBy' => 'startTime'
            ]
        );
        
        // Analizza gli eventi trovati
        foreach ($events->getItems() as $event) {
            // Saltiamo gli eventi in cui sei "free"
            if ($event->getTransparency() === 'transparent') continue;
            
            $start = new DateTime($event->getStart()->getDateTime() ?: $event->getStart()->getDate());
            $end = new DateTime($event->getEnd()->getDateTime() ?: $event->getEnd()->getDate());
            
            // Aggiungi i buffer prima e dopo l'evento
            if ($hoursBefore > 0) {
                $startWithBuffer = clone $start;
                $startWithBuffer->modify("-{$hoursBefore} hours");
                $start = $startWithBuffer;
            }
            
            if ($hoursAfter > 0) {
                $endWithBuffer = clone $end;
                $endWithBuffer->modify("+{$hoursAfter} hours");
                $end = $endWithBuffer;
            }
            
            $busy_periods[] = [
                'start' => $start,
                'end' => $end
            ];
        }
    }
    
    // Ottieni le lezioni già prenotate/programmate dal database
    $query = "SELECT 
              l.id, 
              l.titolo, 
              l.start_time, 
              l.end_time,
              l.stato,
              l.student_email,
              CASE 
                WHEN l.stato = 'prenotata' AND l.student_email = ? THEN 'prenotata_da_me' 
                ELSE l.stato 
              END as stato_effettivo
              FROM Lezioni l
              WHERE l.teacher_email = ? 
              AND l.start_time >= NOW()
              AND l.stato IN ('prenotata', 'completata')";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $current_student_email, $teacher_email);
    $stmt->execute();
    $booked_lessons = $stmt->get_result();
    
    // Aggiungi le lezioni prenotate ai periodi occupati
    while ($lesson = $booked_lessons->fetch_assoc()) {
        $start = new DateTime($lesson['start_time']);
        $end = new DateTime($lesson['end_time']);
        
        // Questo non è un periodo occupato, è una lezione già prenotata
        // La aggiungiamo direttamente alla disponibilità
        $giornoSettimana = getDayName($start);
        
        $disponibilita[] = [
            'id' => $lesson['id'],
            'titolo' => $lesson['titolo'],
            'data' => $start->format('Y-m-d'),
            'data_formattata' => $start->format('d/m/Y'),
            'giorno_settimana' => $giornoSettimana,
            'ora_inizio' => $start->format('H:i'),
            'ora_fine' => $end->format('H:i'),
            'stato' => $lesson['stato'],
            'stato_effettivo' => $lesson['stato_effettivo'],
            'student_email' => $lesson['student_email'],
            'from_google_calendar' => 1
        ];
    }
    
    // Genera slot disponibili in base alle preferenze
    // Per ogni giorno nelle prossime 4 settimane
    $currentDay = clone $now;
    $currentDay->setTime(0, 0, 0); // Inizio della giornata
    
    while ($currentDay < $fourWeeksLater) {
        $dayOfWeek = (int)$currentDay->format('N'); // 1 (lunedì) a 7 (domenica)
        $isWeekend = ($dayOfWeek >= 6);
        
        // Verifica se questo giorno è abilitato in base alle preferenze
        if (($isWeekend && !$preferences['weekend']) || (!$isWeekend && !($preferences['mattina'] || $preferences['pomeriggio']))) {
            // Salta al giorno successivo
            $currentDay->modify('+1 day');
            continue;
        }
        
        // Genera slot per la mattina
        if (!$isWeekend || ($isWeekend && $preferences['weekend'])) {
            if ($preferences['mattina']) {
                generateTimeSlots(
                    $currentDay, 
                    $preferences['ora_inizio_mattina'], 
                    $preferences['ora_fine_mattina'], 
                    $busy_periods, 
                    $disponibilita
                );
            }
            
            // Genera slot per il pomeriggio
            if ($preferences['pomeriggio']) {
                generateTimeSlots(
                    $currentDay, 
                    $preferences['ora_inizio_pomeriggio'], 
                    $preferences['ora_fine_pomeriggio'], 
                    $busy_periods, 
                    $disponibilita
                );
            }
        }
        
        // Passa al giorno successivo
        $currentDay->modify('+1 day');
    }
    
    // Ordinamento degli slot per data e ora
    usort($disponibilita, function($a, $b) {
        $dateComparison = strtotime($a['data']) - strtotime($b['data']);
        if ($dateComparison === 0) {
            return strtotime($a['ora_inizio']) - strtotime($b['ora_inizio']);
        }
        return $dateComparison;
    });
    
    // Recupera se l'insegnante usa Google Calendar
    $query = "SELECT COUNT(*) AS calendar_count FROM Calendari_Professori WHERE teacher_email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $teacher_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $calendar_count = $result->fetch_assoc()['calendar_count'];
    
    echo json_encode([
        'success' => true, 
        'availability' => $disponibilita, 
        'uses_google_calendar' => true, // Sempre true in questa versione
        'realtime' => true // Indica che i dati sono stati generati in tempo reale
    ]);
    
} catch (Exception $e) {
    error_log("Errore nel recupero della disponibilità da Google Calendar: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
}

/**
 * Genera slot di tempo per un determinato giorno e intervallo orario
 */
function generateTimeSlots($day, $startTime, $endTime, $busyPeriods, &$disponibilita) {
    // Slot di 1 ora
    $slotDurationMinutes = 60;
    
    // Converte stringhe orario in oggetti DateTime
    $currentSlotStart = clone $day;
    list($hours, $minutes) = sscanf($startTime, "%d:%d:%d");
    $currentSlotStart->setTime($hours, $minutes, 0);
    
    $dayEnd = clone $day;
    list($hours, $minutes) = sscanf($endTime, "%d:%d:%d");
    $dayEnd->setTime($hours, $minutes, 0);
    
    // Se il giorno è già passato, non generare slot
    $now = new DateTime();
    if ($currentSlotStart < $now) {
        // Se la giornata è oggi ma alcuni slot sono già passati
        if ($day->format('Y-m-d') === $now->format('Y-m-d')) {
            // Inizia dal prossimo slot disponibile
            $minutes = $now->format('i');
            $hours = $now->format('H');
            
            // Arrotonda all'ora successiva
            if ($minutes > 0) {
                $hours++;
            }
            
            $currentSlotStart->setTime($hours, 0, 0);
            
            // Se tutti gli slot della giornata sono passati, esci
            if ($currentSlotStart >= $dayEnd) {
                return;
            }
        } else {
            // Il giorno intero è già passato
            return;
        }
    }
    
    // Genera slot fino alla fine dell'intervallo orario
    while ($currentSlotStart < $dayEnd) {
        $currentSlotEnd = clone $currentSlotStart;
        $currentSlotEnd->modify("+{$slotDurationMinutes} minutes");
        
        // Se lo slot finisce dopo l'orario finale, termina
        if ($currentSlotEnd > $dayEnd) {
            break;
        }
        
        // Verifica se lo slot è libero
        $isSlotFree = true;
        foreach ($busyPeriods as $busyPeriod) {
            if (
                // Lo slot inizia durante un periodo occupato
                ($currentSlotStart >= $busyPeriod['start'] && $currentSlotStart < $busyPeriod['end']) ||
                // Lo slot finisce durante un periodo occupato
                ($currentSlotEnd > $busyPeriod['start'] && $currentSlotEnd <= $busyPeriod['end']) ||
                // Il periodo occupato è contenuto nello slot
                ($busyPeriod['start'] >= $currentSlotStart && $busyPeriod['end'] <= $currentSlotEnd)
            ) {
                $isSlotFree = false;
                break;
            }
        }
        
        // Se lo slot è libero, aggiungilo alla disponibilità
        if ($isSlotFree) {
            $giornoSettimana = getDayName($currentSlotStart);
            
            $disponibilita[] = [
                'id' => null, // Non ha un ID nel DB poiché generato dinamicamente
                'titolo' => 'Slot disponibile',
                'data' => $currentSlotStart->format('Y-m-d'),
                'data_formattata' => $currentSlotStart->format('d/m/Y'),
                'giorno_settimana' => $giornoSettimana,
                'ora_inizio' => $currentSlotStart->format('H:i'),
                'ora_fine' => $currentSlotEnd->format('H:i'),
                'stato' => 'disponibile',
                'stato_effettivo' => 'disponibile',
                'student_email' => null,
                'from_google_calendar' => 1
            ];
        }
        
        // Avanza all'inizio del prossimo slot
        $currentSlotStart = $currentSlotEnd;
    }
}

/**
 * Ottiene il nome del giorno in italiano
 */
function getDayName($date) {
    $dayNumber = (int)$date->format('N'); // 1 (lunedì) a 7 (domenica)
    $days = [
        1 => 'lunedi',
        2 => 'martedi',
        3 => 'mercoledi',
        4 => 'giovedi',
        5 => 'venerdi',
        6 => 'sabato',
        7 => 'domenica'
    ];
    return $days[$dayNumber];
}
?>