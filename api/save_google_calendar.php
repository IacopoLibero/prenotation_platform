<?php
// Attiva la gestione degli errori per il debug
ini_set('display_errors', 0); // Disabilita l'output di errori direttamente nella risposta
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');
require_once '../connessione.php';
require_once '../google_calendar/calendar_functions.php';

// Funzione di gestione errori per catturare errori fatali
function handleFatalErrors() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Errore PHP fatale: ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ]);
    }
}
register_shutdown_function('handleFatalErrors');

try {
    // Verifica se l'utente è autenticato
    if (!isset($_SESSION['email']) || !isset($_SESSION['tipo'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
        exit;
    }

    $userEmail = $_SESSION['email'];
    $userType = $_SESSION['tipo'];

    // Converti il tipo utente se necessario
    if ($userType === 'teacher') {
        $userType = 'professore';
    } elseif ($userType === 'student') {
        $userType = 'studente';
    }

    // Verifica se l'utente ha autorizzato Google Calendar
    if (!hasValidOAuthTokens($userEmail, $userType)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Google Calendar non è stato autorizzato']);
        exit;
    }

    // Gestione delle azioni speciali
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'remove':
                // Rimozione di un calendario specifico
                $calendarId = isset($_GET['id']) ? intval($_GET['id']) : 0;
                
                if ($calendarId <= 0) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'ID calendario non valido']);
                    exit;
                }
                
                try {
                    // Verifica che il calendario appartenga a questo utente
                    $query = "SELECT id FROM Calendari_Professori WHERE id = ? AND teacher_email = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("is", $calendarId, $userEmail);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows === 0) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Calendario non trovato o non autorizzato']);
                        exit;
                    }
                    
                    // Elimina il calendario
                    $query = "DELETE FROM Calendari_Professori WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $calendarId);
                    
                    if ($stmt->execute()) {
                        // Se questo calendario era selezionato nelle preferenze, imposta il campo a NULL
                        $query = "UPDATE Preferenze_Disponibilita SET calendario_selezionato_id = NULL 
                                  WHERE teacher_email = ? AND calendario_selezionato_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("si", $userEmail, $calendarId);
                        $stmt->execute();

                        echo json_encode(['success' => true, 'message' => 'Calendario rimosso con successo']);
                    } else {
                        throw new Exception("Errore nell'eliminazione del calendario: " . $stmt->error);
                    }
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
                }
                exit;
                break;
        }
    }

    // Ottieni i dati inviati
    $inputData = file_get_contents('php://input');
    if (empty($inputData)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nessun dato ricevuto']);
        exit;
    }
    
    $data = json_decode($inputData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Errore nel parsing JSON: ' . json_last_error_msg(),
            'input_data' => substr($inputData, 0, 200) . '...' // Mostra parte dei dati ricevuti per debug
        ]);
        exit;
    }

    // Verifica se ci sono calendari da salvare
    if (!isset($data['calendars']) || !is_array($data['calendars']) || count($data['calendars']) == 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nessun calendario fornito']);
        exit;
    }

    // Verifica la connessione a Google Calendar
    $connectionTest = testGoogleCalendarConnection($userEmail, $userType);

    if (!$connectionTest['success']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $connectionTest['message']]);
        exit;
    }

    // Inizia una transazione per garantire l'integrità dei dati
    $conn->begin_transaction();
    
    // Per i professori, aggiorna i calendari e le preferenze
    if ($userType === 'professore') {
        // Gestisci le preferenze di disponibilità
        if (isset($data['preferences'])) {
            $weekend = isset($data['preferences']['weekend']) ? intval($data['preferences']['weekend']) : 0;
            $mattina = isset($data['preferences']['mattina']) ? intval($data['preferences']['mattina']) : 1;
            $pomeriggio = isset($data['preferences']['pomeriggio']) ? intval($data['preferences']['pomeriggio']) : 1;
            $ora_inizio_mattina = isset($data['preferences']['ora_inizio_mattina']) ? $data['preferences']['ora_inizio_mattina'] : '08:00:00';
            $ora_fine_mattina = isset($data['preferences']['ora_fine_mattina']) ? $data['preferences']['ora_fine_mattina'] : '13:00:00';
            $ora_inizio_pomeriggio = isset($data['preferences']['ora_inizio_pomeriggio']) ? $data['preferences']['ora_inizio_pomeriggio'] : '14:00:00';
            $ora_fine_pomeriggio = isset($data['preferences']['ora_fine_pomeriggio']) ? $data['preferences']['ora_fine_pomeriggio'] : '19:00:00';
            
            // Gestisci il calendario selezionato per le nuove lezioni
            $calendario_selezionato_id = null;
            if (isset($data['preferences']['calendario_selezionato_id'])) {
                $calendario_selezionato_id = intval($data['preferences']['calendario_selezionato_id']);
            }
            
            // Verifica se esiste già un record di preferenze
            $query = "SELECT id FROM Preferenze_Disponibilita WHERE teacher_email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $userEmail);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Aggiorna le preferenze esistenti
                $preferenceId = $result->fetch_assoc()['id'];
                $query = "UPDATE Preferenze_Disponibilita SET 
                         weekend = ?, 
                         mattina = ?, 
                         pomeriggio = ?,
                         ora_inizio_mattina = ?,
                         ora_fine_mattina = ?,
                         ora_inizio_pomeriggio = ?,
                         ora_fine_pomeriggio = ?,
                         calendario_selezionato_id = ?
                         WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iiissssii", $weekend, $mattina, $pomeriggio, 
                                $ora_inizio_mattina, $ora_fine_mattina, 
                                $ora_inizio_pomeriggio, $ora_fine_pomeriggio, 
                                $calendario_selezionato_id, $preferenceId);
            } else {
                // Inserisci nuove preferenze
                $query = "INSERT INTO Preferenze_Disponibilita 
                         (teacher_email, weekend, mattina, pomeriggio, 
                          ora_inizio_mattina, ora_fine_mattina, 
                          ora_inizio_pomeriggio, ora_fine_pomeriggio,
                          calendario_selezionato_id) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("siiiisssi", $userEmail, $weekend, $mattina, $pomeriggio, 
                                $ora_inizio_mattina, $ora_fine_mattina, 
                                $ora_inizio_pomeriggio, $ora_fine_pomeriggio,
                                $calendario_selezionato_id);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Errore nel salvare le preferenze di disponibilità: " . $stmt->error);
            }
        }
        
        // Gestisci l'aggiornamento dei calendari
        $savedCalendars = [];
        foreach ($data['calendars'] as $calendar) {
            $calendarId = isset($calendar['id']) ? intval($calendar['id']) : null;
            $googleCalendarId = $calendar['calendar_id'];
            $calendarName = $calendar['calendar_name'];
            $hoursBefore = floatval($calendar['hours_before']);
            $hoursAfter = floatval($calendar['hours_after']);
            $isActive = isset($calendar['is_active']) ? intval($calendar['is_active']) : 1;
            
            // Validazione dei dati
            if (empty($googleCalendarId) || empty($calendarName)) {
                continue; // Salta i calendari senza ID o nome
            }
            
            if ($calendarId) {
                // Aggiorna il calendario esistente
                $query = "UPDATE Calendari_Professori SET 
                         google_calendar_id = ?, 
                         nome_calendario = ?, 
                         ore_prima_evento = ?, 
                         ore_dopo_evento = ?,
                         is_active = ?
                         WHERE id = ? AND teacher_email = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssddiss", $googleCalendarId, $calendarName, 
                                $hoursBefore, $hoursAfter, $isActive, 
                                $calendarId, $userEmail);
            } else {
                // Inserisce un nuovo calendario
                $calendarLink = "https://calendar.google.com/calendar/embed?src=" . urlencode($googleCalendarId);
                $query = "INSERT INTO Calendari_Professori 
                         (teacher_email, google_calendar_link, google_calendar_id, 
                          nome_calendario, ore_prima_evento, ore_dopo_evento, is_active) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssddi", $userEmail, $calendarLink, $googleCalendarId, 
                                $calendarName, $hoursBefore, $hoursAfter, $isActive);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Errore nel salvare il calendario: " . $stmt->error);
            }
            
            // Se è un nuovo inserimento, ottieni l'ID generato
            if (!$calendarId) {
                $calendarId = $conn->insert_id;
            }
            
            $savedCalendars[] = [
                'id' => $calendarId,
                'calendar_id' => $googleCalendarId,
                'calendar_name' => $calendarName
            ];
        }
        
        // Commit della transazione
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Configurazione calendario salvata con successo',
            'data' => [
                'calendars' => $savedCalendars
            ]
        ]);
    } else {
        // Per gli studenti, al momento non è richiesta alcuna tabella specifica
        echo json_encode([
            'success' => true, 
            'message' => 'Autorizzazione Google Calendar completata con successo',
            'data' => [
                'user_type' => 'studente',
                'calendar_connected' => true
            ]
        ]);
    }
} catch (Exception $e) {
    // Rollback in caso di errore
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Errore: ' . $e->getMessage()
    ]);
}
?>
