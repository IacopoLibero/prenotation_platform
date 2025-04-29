<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/google_config.php';
require_once __DIR__ . '/token_storage.php';

/**
 * Crea un client Google autenticato con i token dell'utente
 * 
 * @param string $userEmail Email dell'utente
 * @param string $userType Tipo di utente ('professore' o 'studente')
 * @return Google_Client|null Client autenticato o null in caso di errore
 */
function getAuthenticatedClient($userEmail, $userType) {
    try {
        $tokens = getOAuthTokens($userEmail, $userType);
        
        if (!$tokens || empty($tokens['refresh_token'])) {
            return null;
        }
        
        $client = createGoogleClient();
        $client->setAccessToken([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $tokens['expiry'] ? ($tokens['expiry'] - time()) : 3600
        ]);
        
        // Se il token è scaduto, aggiornalo
        if ($client->isAccessTokenExpired()) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($tokens['refresh_token']);
            
            if (isset($newToken['error'])) {
                error_log("Errore nel refresh del token: " . $newToken['error']);
                return null;
            }
            
            // Salva il nuovo access token nel database
            saveOAuthTokens(
                $userEmail,
                $userType,
                $newToken['access_token'],
                isset($newToken['refresh_token']) ? $newToken['refresh_token'] : null,
                isset($newToken['expires_in']) ? time() + $newToken['expires_in'] : null
            );
        }
        
        return $client;
        
    } catch (Exception $e) {
        error_log("Errore nel recupero del client autenticato: " . $e->getMessage());
        return null;
    }
}

/**
 * Verifica che la connessione con Google Calendar funzioni
 * 
 * @param string $userEmail Email dell'utente
 * @param string $userType Tipo di utente ('professore' o 'studente')
 * @return array Stato della connessione e messaggio
 */
function testGoogleCalendarConnection($userEmail, $userType) {
    try {
        $client = getAuthenticatedClient($userEmail, $userType);
        
        if (!$client) {
            return [
                'success' => false,
                'message' => 'Cliente non autenticato. Autorizzazione necessaria.'
            ];
        }
        
        $service = new Google_Service_Calendar($client);
        
        // Tenta di accedere alle informazioni sul calendario primario dell'utente
        $calendarList = $service->calendarList->listCalendarList(['maxResults' => 1]);
        
        return [
            'success' => true,
            'message' => 'Connessione a Google Calendar stabilita con successo!',
            'data' => [
                'calendar_count' => count($calendarList->getItems())
            ]
        ];
        
    } catch (Google_Service_Exception $e) {
        error_log("Errore di servizio Google Calendar: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Errore nell\'accesso a Google Calendar: ' . $e->getMessage()
        ];
    } catch (Exception $e) {
        error_log("Errore nel test di connessione Google Calendar: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Si è verificato un errore durante il test della connessione: ' . $e->getMessage()
        ];
    }
}

/**
 * Recupera la lista dei calendari dell'utente
 * 
 * @param string $userEmail Email dell'utente
 * @param string $userType Tipo di utente ('professore' o 'studente')
 * @return array Lista dei calendari o array con errore
 */
function getUserCalendars($userEmail, $userType) {
    try {
        $client = getAuthenticatedClient($userEmail, $userType);
        
        if (!$client) {
            return [
                'success' => false,
                'message' => 'Cliente non autenticato. Autorizzazione necessaria.'
            ];
        }
        
        $service = new Google_Service_Calendar($client);
        $calendarList = $service->calendarList->listCalendarList();
        
        $calendars = [];
        foreach ($calendarList->getItems() as $calendar) {
            $calendars[] = [
                'id' => $calendar->getId(),
                'summary' => $calendar->getSummary(),
                'primary' => $calendar->getPrimary() ? true : false,
                'accessRole' => $calendar->getAccessRole()
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Calendari recuperati con successo',
            'data' => $calendars
        ];
        
    } catch (Exception $e) {
        error_log("Errore nel recupero dei calendari: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Errore nel recupero dei calendari: ' . $e->getMessage()
        ];
    }
}

/**
 * Revoca l'accesso a Google Calendar per un utente
 * 
 * @param string $userEmail Email dell'utente
 * @param string $userType Tipo di utente ('professore' o 'studente')
 * @return boolean Successo dell'operazione
 */
function revokeGoogleAccess($userEmail, $userType) {
    try {
        $tokens = getOAuthTokens($userEmail, $userType);
        
        if (!$tokens) {
            return true; // Non c'è niente da revocare
        }
        
        $client = createGoogleClient();
        
        // Imposta il token di accesso
        if (!empty($tokens['access_token'])) {
            $client->revokeToken($tokens['access_token']);
        }
        
        // Elimina i token dal database
        deleteOAuthTokens($userEmail, $userType);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Errore nella revoca dell'accesso Google: " . $e->getMessage());
        return false;
    }
}

/**
 * Creates a calendar event for a lesson in the user's Google Calendar
 * 
 * @param string $userEmail Email of the user (teacher or student)
 * @param string $userType Type of user ('professore' o 'studente')
 * @param string $title Event title
 * @param string $description Event description
 * @param string $startTime Start time (Y-m-d H:i:s format)
 * @param string $endTime End time (Y-m-d H:i:s format)
 * @param array $attendees List of emails for attendees
 * @param string $location Optional location
 * @return array Response with success status, message, and event ID if successful
 */
function createCalendarEvent($userEmail, $userType, $title, $description, $startTime, $endTime, $attendees = [], $location = null) {
    try {
        $client = getAuthenticatedClient($userEmail, $userType);
        
        if (!$client) {
            return [
                'success' => false,
                'message' => 'Utente non autenticato con Google Calendar'
            ];
        }
        
        $service = new Google_Service_Calendar($client);
        
        // Convert to DateTime objects
        $startDateTime = new DateTime($startTime);
        $endDateTime = new DateTime($endTime);
        
        // Create the event
        $event = new Google_Service_Calendar_Event([
            'summary' => $title,
            'description' => $description,
            'start' => [
                'dateTime' => $startDateTime->format('Y-m-d\TH:i:s'),
                'timeZone' => 'Europe/Rome',
            ],
            'end' => [
                'dateTime' => $endDateTime->format('Y-m-d\TH:i:s'),
                'timeZone' => 'Europe/Rome',
            ]
        ]);
        
        // Add location if provided
        if ($location) {
            $event->setLocation($location);
        }
        
        // Add attendees if provided
        if (!empty($attendees)) {
            $eventAttendees = [];
            foreach ($attendees as $attendeeEmail) {
                $eventAttendees[] = ['email' => $attendeeEmail];
            }
            $event->setAttendees($eventAttendees);
        }
        
        // Insert the event to the user's primary calendar
        $calendarId = 'primary';
        $createdEvent = $service->events->insert($calendarId, $event);
        
        return [
            'success' => true,
            'message' => 'Evento creato con successo nel calendario',
            'event_id' => $createdEvent->getId()
        ];
        
    } catch (Exception $e) {
        error_log("Errore nella creazione dell'evento: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Errore nella creazione dell\'evento: ' . $e->getMessage()
        ];
    }
}

/**
 * Deletes a calendar event from the user's Google Calendar
 * 
 * @param string $userEmail Email of the user (teacher or student)
 * @param string $userType Type of user ('professore' o 'studente')
 * @param string $eventId Google Calendar event ID
 * @return array Response with success status and message
 */
function deleteCalendarEvent($userEmail, $userType, $eventId) {
    try {
        $client = getAuthenticatedClient($userEmail, $userType);
        
        if (!$client) {
            return [
                'success' => false,
                'message' => 'Utente non autenticato con Google Calendar'
            ];
        }
        
        $service = new Google_Service_Calendar($client);
        
        // Delete the event from the user's primary calendar
        $service->events->delete('primary', $eventId);
        
        return [
            'success' => true,
            'message' => 'Evento eliminato con successo dal calendario'
        ];
        
    } catch (Exception $e) {
        error_log("Errore nell'eliminazione dell'evento: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Errore nell\'eliminazione dell\'evento: ' . $e->getMessage()
        ];
    }
}

/**
 * Saves Google Calendar event IDs for a lesson
 * 
 * @param int $lessonId ID of the lesson
 * @param string $teacherEventId Google Calendar event ID for teacher
 * @param string $studentEventId Google Calendar event ID for student
 * @param object $conn Database connection
 * @return bool Success status
 */
function saveCalendarEventIds($lessonId, $teacherEventId, $studentEventId, $conn) {
    try {
        $query = "UPDATE Lezioni SET 
                  teacher_event_id = ?, 
                  student_event_id = ? 
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $teacherEventId, $studentEventId, $lessonId);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Errore nel salvataggio degli ID evento: " . $e->getMessage());
        return false;
    }
}

/**
 * Retrieves Google Calendar event IDs for a lesson
 * 
 * @param int $lessonId ID of the lesson
 * @param object $conn Database connection
 * @return array|null Array with event IDs or null if not found
 */
function getCalendarEventIds($lessonId, $conn) {
    try {
        $query = "SELECT teacher_event_id, student_event_id 
                  FROM Lezioni 
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $lessonId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Errore nel recupero degli ID evento: " . $e->getMessage());
        return null;
    }
}
?>