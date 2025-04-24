<?php
// Funzione per leggere variabili d'ambiente su Altervista
// Si tenta di leggere le variabili in diversi modi per compatibilità massima
function getEnvVariable($name) {
    // Prima prova getenv standard
    $value = getenv($name);
    if ($value !== false && !empty($value)) {
        return $value;
    }
    
    // Poi prova apache_getenv se disponibile (spesso su hosting condivisi)
    if (function_exists('apache_getenv')) {
        $value = apache_getenv($name);
        if ($value !== false && !empty($value)) {
            return $value;
        }
    }
    
    // Prova $_SERVER che su alcuni hosting contiene le variabili d'ambiente
    if (isset($_SERVER[$name])) {
        return $_SERVER[$name];
    }
    
    // Su Altervista e altri hosting, le variabili d'ambiente potrebbero essere in $_ENV
    if (isset($_ENV[$name])) {
        return $_ENV[$name];
    }
    
    // Infine, leggi direttamente dal file .htaccess come fallback
    // Questo è l'ultimo tentativo e meno sicuro, ma potrebbe funzionare su Altervista
    $htaccessPath = __DIR__ . '/../.htaccess';
    if (file_exists($htaccessPath) && is_readable($htaccessPath)) {
        $htaccess = file_get_contents($htaccessPath);
        $pattern = '/SetEnv\s+' . preg_quote($name) . '\s+([^\s]+)/i';
        if (preg_match($pattern, $htaccess, $matches)) {
            return $matches[1];
        }
    }
    
    // Se proprio non si trova il valore, ritorna una stringa vuota
    return '';
}

// Configurazione client Google
$googleClientConfig = [
    'client_id' => getEnvVariable('GOOGLE_CLIENT_ID'),
    'client_secret' => getEnvVariable('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => getEnvVariable('GOOGLE_REDIRECT_URI'),
    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
    'token_uri' => 'https://oauth2.googleapis.com/token',
    'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
    'project_id' => getEnvVariable('GOOGLE_PROJECT_ID'),
    'scopes' => ['https://www.googleapis.com/auth/calendar'] // Accesso completo a Google Calendar
];

/**
 * Crea e configura un client Google
 * @return Google_Client
 */
function createGoogleClient() {
    global $googleClientConfig;
    
    $client = new Google_Client();
    $client->setClientId($googleClientConfig['client_id']);
    $client->setClientSecret($googleClientConfig['client_secret']);
    $client->setRedirectUri($googleClientConfig['redirect_uri']);
    $client->setScopes($googleClientConfig['scopes']);
    $client->setAccessType('offline');
    $client->setPrompt('consent'); // Forza la visualizzazione del consenso per ottenere sempre il refresh token
    
    return $client;
}
?>