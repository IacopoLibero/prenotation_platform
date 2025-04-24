<?php
// Configurazione client Google da variabili d'ambiente
$googleClientConfig = [
    'client_id' => getenv('GOOGLE_CLIENT_ID'),
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => getenv('GOOGLE_REDIRECT_URI'),
    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
    'token_uri' => 'https://oauth2.googleapis.com/token',
    'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
    'project_id' => getenv('GOOGLE_PROJECT_ID'),
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