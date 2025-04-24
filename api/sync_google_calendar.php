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

// Determina l'operazione da eseguire
$action = isset($_GET['action']) ? $_GET['action'] : 'test';

try {
    switch ($action) {
        case 'test':
            // Verifica la connessione a Google Calendar
            $result = testGoogleCalendarConnection($userEmail, $userType);
            echo json_encode($result);
            break;
            
        case 'list_calendars':
            // Recupera la lista dei calendari dell'utente
            $result = getUserCalendars($userEmail, $userType);
            echo json_encode($result);
            break;
            
        case 'revoke':
            // Revoca l'accesso a Google Calendar
            $success = revokeGoogleAccess($userEmail, $userType);
            
            if ($success) {
                // Se è un professore, rimuovi anche l'ID del calendario
                if ($userType === 'professore') {
                    $query = "UPDATE Calendari_Professori SET 
                              google_calendar_id = NULL 
                              WHERE teacher_email = ?";
                    $stmt = $connessione->prepare($query);
                    $stmt->bind_param("s", $userEmail);
                    $stmt->execute();
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Accesso a Google Calendar revocato con successo'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Errore durante la revoca dell\'accesso'
                ]);
            }
            break;
            
        case 'check_auth':
            // Verifica se l'utente ha già autorizzato Google Calendar
            $isAuthorized = hasValidOAuthTokens($userEmail, $userType);
            echo json_encode([
                'success' => true,
                'authorized' => $isAuthorized,
                'message' => $isAuthorized ? 'Google Calendar è già autorizzato' : 'Google Calendar non è stato ancora autorizzato'
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Azione non valida'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Errore: ' . $e->getMessage()
    ]);
}
?>