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
?>