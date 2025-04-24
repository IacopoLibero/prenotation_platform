<?php
// Funzione di supporto per leggere le variabili d'ambiente o le variabili server
function getConfigValue($name) {
    // Prova a leggere da getenv()
    $value = getenv($name);
    
    // Se non funziona, prova da $_SERVER
    if ($value === false || empty($value)) {
        $serverKey = 'HTTP_' . strtoupper($name);
        if (isset($_SERVER[$serverKey])) {
            return $_SERVER[$serverKey];
        }
        
        // Prova anche senza il prefisso HTTP_
        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }
    }
    
    return $value;
}

// Configurazione client Google
$googleClientConfig = [
    'client_id' => getConfigValue('GOOGLE_CLIENT_ID'),
    'client_secret' => getConfigValue('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => getConfigValue('GOOGLE_REDIRECT_URI'),
    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
    'token_uri' => 'https://oauth2.googleapis.com/token',
    'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
    'project_id' => getConfigValue('GOOGLE_PROJECT_ID'),
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