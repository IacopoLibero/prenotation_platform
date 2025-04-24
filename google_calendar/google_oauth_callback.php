<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/google_config.php';
require_once __DIR__ . '/token_storage.php';

// Verifica che l'utente sia autenticato
if (!isset($_SESSION['oauth_user_email']) || !isset($_SESSION['oauth_user_type'])) {
    header('Location: /login/login.php');
    exit('Sessione non valida');
}

// Recupera i dati dell'utente dalla sessione
$userEmail = $_SESSION['oauth_user_email'];
$userType = $_SESSION['oauth_user_type'];

// Controlla se è presente un errore nella risposta
if (isset($_GET['error'])) {
    header('Location: /front-end/google_calendar_setup.php?status=auth_error&message=' . urlencode($_GET['error']));
    exit;
}

// Verifica il parametro di stato per prevenire attacchi CSRF
if (!isset($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    header('Location: /front-end/google_calendar_setup.php?status=auth_error&message=invalid_state');
    exit('Errore di validazione stato');
}

// Verifica che sia presente il codice di autorizzazione
if (!isset($_GET['code'])) {
    header('Location: /front-end/google_calendar_setup.php?status=auth_error&message=no_code');
    exit('Codice di autorizzazione mancante');
}

try {
    // Crea il client Google
    $client = createGoogleClient();
    
    // Scambia il codice di autorizzazione per un token di accesso
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    // Verifica se c'è stato un errore nel recupero del token
    if (isset($token['error'])) {
        error_log("Errore nel recupero del token: " . $token['error_description']);
        header('Location: /front-end/google_calendar_setup.php?status=token_error&message=' . urlencode($token['error']));
        exit;
    }
    
    // Salva i token nel database
    $success = saveOAuthTokens(
        $userEmail,
        $userType,
        $token['access_token'],
        isset($token['refresh_token']) ? $token['refresh_token'] : null,
        isset($token['expires_in']) ? time() + $token['expires_in'] : null
    );
    
    // Pulisce le variabili di sessione utilizzate per l'OAuth
    unset($_SESSION['oauth_state']);
    
    if ($success) {
        // Reindirizza alla pagina di configurazione con successo
        header('Location: /front-end/google_calendar_setup.php?status=success');
    } else {
        // Errore nel salvataggio dei token
        header('Location: /front-end/google_calendar_setup.php?status=db_error');
    }
    
} catch (Exception $e) {
    error_log("Errore nell'OAuth callback: " . $e->getMessage());
    header('Location: /front-end/google_calendar_setup.php?status=error&message=' . urlencode($e->getMessage()));
}
?>