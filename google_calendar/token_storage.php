<?php
require_once __DIR__ . '/../connessione.php';

/**
 * Salva o aggiorna i token di accesso a Google Calendar nel database
 * 
 * @param string $userEmail Email dell'utente
 * @param string $userType Tipo di utente ('professore' o 'studente')
 * @param string $accessToken Token di accesso
 * @param string $refreshToken Token di refresh
 * @param int $expiryTimestamp Timestamp di scadenza del token di accesso
 * @return bool Successo dell'operazione
 */
function saveOAuthTokens($userEmail, $userType, $accessToken, $refreshToken = null, $expiryTimestamp = null) {
    global $connessione;
    
    try {
        // Formatta la data di scadenza
        $expiryDate = null;
        if ($expiryTimestamp) {
            $expiryDate = date('Y-m-d H:i:s', $expiryTimestamp);
        }
        
        // Controlla se esiste già un record per questo utente
        $checkQuery = "SELECT id FROM OAuth_Tokens WHERE user_email = ? AND user_type = ?";
        $stmt = $connessione->prepare($checkQuery);
        $stmt->bind_param("ss", $userEmail, $userType);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Aggiorna il record esistente
            $query = "UPDATE OAuth_Tokens SET access_token = ?, updated_at = NOW()";
            $params = ["s", $accessToken];
            
            // Aggiorna refresh_token solo se ne è stato fornito uno nuovo
            if ($refreshToken) {
                $query .= ", refresh_token = ?";
                $params[0] .= "s";
                $params[] = $refreshToken;
            }
            
            // Aggiorna expiry_date solo se ne è stato fornito uno
            if ($expiryDate) {
                $query .= ", expiry_date = ?";
                $params[0] .= "s";
                $params[] = $expiryDate;
            }
            
            $query .= " WHERE user_email = ? AND user_type = ?";
            $params[0] .= "ss";
            $params[] = $userEmail;
            $params[] = $userType;
            
            $stmt = $connessione->prepare($query);
            $stmt->bind_param(...$params);
            
        } else {
            // Inserisci un nuovo record
            $query = "INSERT INTO OAuth_Tokens (user_email, user_type, access_token, refresh_token, expiry_date) VALUES (?, ?, ?, ?, ?)";
            $stmt = $connessione->prepare($query);
            $stmt->bind_param("sssss", $userEmail, $userType, $accessToken, $refreshToken, $expiryDate);
        }
        
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Errore nel salvataggio dei token OAuth: " . $e->getMessage());
        return false;
    }
}

/**
 * Recupera i token OAuth per un utente
 * 
 * @param string $userEmail Email dell'utente
 * @param string $userType Tipo di utente ('professore' o 'studente')
 * @return array|null Array con i token o null se non trovati
 */
function getOAuthTokens($userEmail, $userType) {
    global $connessione;
    
    try {
        $query = "SELECT access_token, refresh_token, expiry_date FROM OAuth_Tokens WHERE user_email = ? AND user_type = ?";
        $stmt = $connessione->prepare($query);
        $stmt->bind_param("ss", $userEmail, $userType);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Converti la data di scadenza in timestamp
            $expiryTimestamp = null;
            if ($row['expiry_date']) {
                $expiryTimestamp = strtotime($row['expiry_date']);
            }
            
            return [
                'access_token' => $row['access_token'],
                'refresh_token' => $row['refresh_token'],
                'expiry' => $expiryTimestamp
            ];
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Errore nel recupero dei token OAuth: " . $e->getMessage());
        return null;
    }
}

/**
 * Elimina i token OAuth per un utente
 * 
 * @param string $userEmail Email dell'utente
 * @param string $userType Tipo di utente ('professore' o 'studente')
 * @return bool Successo dell'operazione
 */
function deleteOAuthTokens($userEmail, $userType) {
    global $connessione;
    
    try {
        $query = "DELETE FROM OAuth_Tokens WHERE user_email = ? AND user_type = ?";
        $stmt = $connessione->prepare($query);
        $stmt->bind_param("ss", $userEmail, $userType);
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Errore nell'eliminazione dei token OAuth: " . $e->getMessage());
        return false;
    }
}

/**
 * Controlla se un utente ha già autorizzato Google Calendar
 * 
 * @param string $userEmail Email dell'utente
 * @param string $userType Tipo di utente ('professore' o 'studente')
 * @return bool True se l'utente ha già autorizzato
 */
function hasValidOAuthTokens($userEmail, $userType) {
    $tokens = getOAuthTokens($userEmail, $userType);
    return $tokens !== null && !empty($tokens['refresh_token']);
}
?>