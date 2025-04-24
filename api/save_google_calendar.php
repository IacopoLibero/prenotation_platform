<?php
session_start();
header('Content-Type: application/json');
require_once '../connessione.php';
require_once '../google_calendar/calendar_functions.php';

// Verifica se l'utente è autenticato
if (!isset($_SESSION['user_email']) || !isset($_SESSION['user_type'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
    exit;
}

$userEmail = $_SESSION['user_email'];
$userType = $_SESSION['user_type'];

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

// Ottieni i dati inviati
$data = json_decode(file_get_contents('php://input'), true);
$calendarId = isset($data['calendar_id']) ? $data['calendar_id'] : null;
$calendarName = isset($data['calendar_name']) ? $data['calendar_name'] : 'Calendario Lezioni';
$hoursBeforeEvent = isset($data['hours_before']) ? floatval($data['hours_before']) : 0;
$hoursAfterEvent = isset($data['hours_after']) ? floatval($data['hours_after']) : 0;

// Verifica che sia stato selezionato un calendario
if (empty($calendarId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID del calendario non specificato']);
    exit;
}

try {
    // Verifica la connessione a Google Calendar
    $connectionTest = testGoogleCalendarConnection($userEmail, $userType);
    
    if (!$connectionTest['success']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $connectionTest['message']]);
        exit;
    }
    
    // Operazione diversa in base al tipo di utente
    if ($userType === 'professore') {
        // Per i professori, aggiorna o inserisci nella tabella Calendari_Professori
        $query = "SELECT id FROM Calendari_Professori WHERE teacher_email = ?";
        $stmt = $connessione->prepare($query);
        $stmt->bind_param("s", $userEmail);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Aggiorna il calendario esistente
            $query = "UPDATE Calendari_Professori SET 
                      google_calendar_id = ?, 
                      nome_calendario = ?, 
                      ore_prima_evento = ?, 
                      ore_dopo_evento = ? 
                      WHERE teacher_email = ?";
            $stmt = $connessione->prepare($query);
            $stmt->bind_param("ssdds", $calendarId, $calendarName, $hoursBeforeEvent, $hoursAfterEvent, $userEmail);
        } else {
            // Inserisci un nuovo calendario
            $query = "INSERT INTO Calendari_Professori 
                      (teacher_email, google_calendar_link, google_calendar_id, nome_calendario, ore_prima_evento, ore_dopo_evento) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $calendarLink = "https://calendar.google.com/calendar/embed?src=" . urlencode($calendarId);
            $stmt = $connessione->prepare($query);
            $stmt->bind_param("ssssdd", $userEmail, $calendarLink, $calendarId, $calendarName, $hoursBeforeEvent, $hoursAfterEvent);
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Configurazione calendario salvata con successo',
                'data' => [
                    'calendar_id' => $calendarId,
                    'calendar_name' => $calendarName
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore nel salvare la configurazione: ' . $connessione->error]);
        }
    } else {
        // Per gli studenti, al momento non è richiesta alcuna tabella specifica
        // Possiamo aggiungere qui la logica se necessario in futuro
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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
}
?>
